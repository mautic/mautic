<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Form\Type\CampaignImportType;
use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\FormBundle\Helper\FormFieldHelper;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

final class ImportController extends FormController
{
    // Steps of the import
    public const STEP_UPLOAD_ZIP      = 1;

    public const STEP_PROGRESS_BAR    = 2;

    public const STEP_IMPORT_FROM_ZIP = 3;

    public function __construct(
        FormFactoryInterface $formFactory,
        FormFieldHelper $fieldHelper,
        CoreParametersHelper $coreParametersHelper,
        ManagerRegistry $doctrine,
        MauticFactory $factory,
        ModelFactory $modelFactory,
        private UserHelper $userHelper,
        EventDispatcherInterface $dispatcher,
        Translator $translator,
        FlashBag $flashBag,
        private RequestStack $requestStack,
        CorePermissions $security,
        private LoggerInterface $logger,
        private PathsHelper $pathsHelper,
    ) {
        parent::__construct($formFactory, $fieldHelper, $doctrine, $factory, $modelFactory, $userHelper, $coreParametersHelper, $dispatcher, $translator, $flashBag, $requestStack, $security);
    }

    public function newAction(Request $request, $ignorePost = false): Response
    {
        if (!$this->security->isAdmin() && !$this->security->isGranted('campaign:imports:create')) {
            return $this->accessDenied();
        }

        $fullPath = $this->getFullZipPath();
        $step     = $this->getImportStep($request, $fullPath);

        if (!$ignorePost && $request->isMethod('POST')) {
            $response = $this->handleFileUpload($request, $this->getImportFileName(), $fullPath);
            if ($response) {
                return $response;
            }
        }

        if (self::STEP_IMPORT_FROM_ZIP === $step) {
            $importSummary = $this->processImport($fullPath);

            if (empty($importSummary['errors'])) {
                $this->addFlashMessage('mautic.campaign.notice.import.finished', [], FlashBag::LEVEL_NOTICE);
            }

            $this->resetImport();
            $this->removeImportFile($fullPath);

            return $this->delegateView([
                'viewParameters'  => [
                    'importSummary' => $importSummary,
                    'mauticContent' => 'campaignImport',
                    'step'          => $step,
                ],
                'contentTemplate' => '@MauticCampaign/Import/progress.html.twig',
                'passthroughVars' => [
                    'mauticContent' => 'campaignImport',
                    'route'         => $this->generateUrl('mautic_campaign_import_action', ['objectAction' => 'new']),
                    'step'          => $step,
                ],
            ]);
        }

        $form = $this->formFactory->create(CampaignImportType::class, [], [
            'action' => $this->generateUrl('mautic_campaign_import_action', ['objectAction' => 'new']),
        ]);

        return $this->delegateView([
            'viewParameters'  => [
                'form'          => $form->createView(),
                'mauticContent' => 'campaignImport',
                'step'          => $step,
            ],
            'contentTemplate' => '@MauticCampaign/Import/import.html.twig',
            'passthroughVars' => [
                'mauticContent' => 'campaignImport',
                'route'         => $this->generateUrl('mautic_campaign_import_action', ['objectAction' => 'new']),
                'step'          => $step,
            ],
        ]);
    }

    /**
     * Cancels import by removing the uploaded file.
     */
    public function cancelAction(): JsonResponse
    {
        $filePath = $this->requestStack->getSession()->get('mautic.campaign.import.file');

        if ($filePath && file_exists($filePath)) {
            unlink($filePath);
            $this->logger->info("Campaign import file removed: {$filePath}");
        }

        $this->requestStack->getSession()->remove('mautic.campaign.import.file');

        return new JsonResponse(['message' => 'Campaign import canceled successfully.']);
    }

    private function resetImport(): void
    {
        $this->requestStack->getSession()->set('mautic.campaign.import.file', null);
        $this->requestStack->getSession()->set('mautic.campaign.import.step', self::STEP_UPLOAD_ZIP);
    }

    private function removeImportFile(string $filepath): void
    {
        if (file_exists($filepath) && is_readable($filepath)) {
            unlink($filepath);

            $this->logger->log(LogLevel::WARNING, "File {$filepath} was removed.");
        }
    }

    /**
     * Generates unique import directory name inside the cache dir if not stored in the session.
     * If it exists in the session, returns that one.
     */
    private function getImportFileName(): string
    {
        $session  = $this->requestStack->getSession();
        $fileName = $session->get('mautic.campaign.import.file');

        if ($fileName && !str_contains($fileName, '/')) {
            return $fileName;
        }

        $fileName = sprintf('%s.zip', (new DateTimeHelper())->toUtcString('YmdHis'));

        $session->set('mautic.campaign.import.file', $fileName);

        return $fileName;
    }

    /**
     * Return full absolute path to the ZIP file.
     */
    private function getFullZipPath(): string
    {
        return $this->pathsHelper->getImportCampaignsPath().'/'.$this->getImportFileName();
    }

    private function readFile(string $filePath): ?array
    {
        if ('zip' === pathinfo($filePath, PATHINFO_EXTENSION)) {
            $tempDir = sys_get_temp_dir();
            $zip     = new \ZipArchive();

            if (true === $zip->open($filePath)) {
                $zip->extractTo($tempDir);
                $jsonFilePath = null;
                $mediaPath    = $this->pathsHelper->getSystemPath('media').'/files/';

                for ($i = 0; $i < $zip->numFiles; ++$i) {
                    $filename        = $zip->getNameIndex($i);
                    $sourcePath      = $tempDir.'/'.$filename;
                    $destinationPath = $mediaPath.substr($filename, strlen('assets/'));

                    if (str_starts_with($filename, 'assets/')) {
                        if (is_dir($sourcePath)) {
                            if (!is_dir($destinationPath)) {
                                mkdir($destinationPath, 0755, true);
                            }
                        } else {
                            $dirPath = dirname($destinationPath);
                            if (!is_dir($dirPath)) {
                                mkdir($dirPath, 0755, true);
                            }
                            copy($sourcePath, $destinationPath);
                        }
                    } elseif ('json' === pathinfo($filename, PATHINFO_EXTENSION)) {
                        $jsonFilePath = $tempDir.'/'.$filename;
                    }
                }

                $zip->close();
                if ($jsonFilePath) {
                    $filePath = $jsonFilePath;
                } else {
                    return null;
                }
            } else {
                return null;
            }
        }

        $fileContents = file_get_contents($filePath);

        return json_decode($fileContents, true);
    }

    private function getImportStep(Request $request, string $fullPath): int
    {
        if ($request->get('cancel', false)) {
            return self::STEP_UPLOAD_ZIP;
        }

        $step = $this->requestStack->getSession()->get('mautic.campaign.import.step', self::STEP_UPLOAD_ZIP);

        if (!file_exists($fullPath) && self::STEP_UPLOAD_ZIP !== $step) {
            $this->logger->log(LogLevel::WARNING, "File {$fullPath} does not exist. Resetting import to STEP_UPLOAD_ZIP.");
            $this->addFlashMessage('mautic.campaign.import.file.missing=', ['%file%' => $fullPath], FlashBag::LEVEL_ERROR);
            $this->requestStack->getSession()->set('mautic.campaign.import.step', self::STEP_UPLOAD_ZIP);

            return self::STEP_UPLOAD_ZIP;
        }

        return $step;
    }

    private function handleFileUpload(Request $request, string $fileName, string $fullPath): ?Response
    {
        $importDir = $this->pathsHelper->getImportCampaignsPath();
        $form      = $this->formFactory->create(CampaignImportType::class, [], ['action' => $this->generateUrl('mautic_campaign_import_action', ['objectAction' => 'new'])]);

        if ($this->isFormCancelled($form)) {
            $this->resetImport();
            $this->removeImportFile($fullPath);
            $this->logger->log(LogLevel::WARNING, "Import for file {$fullPath} was canceled.");

            return $this->newAction($request, true);
        }

        if (!$this->isFormValid($form)) {
            return null;
        }

        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        $fileData = $request->files->get('campaign_import')['campaignFile'] ?? null;

        if ($fileData) {
            try {
                (new Filesystem())->mkdir($importDir);
                $fileData->move($importDir, $fileName);
                $this->requestStack->getSession()->set('mautic.campaign.import.step', self::STEP_IMPORT_FROM_ZIP);

                return $this->newAction($request, true);
            } catch (FileException $e) {
                $form->addError(new FormError(
                    $this->translator->trans(
                        str_contains($e->getMessage(), 'upload_max_filesize') ? 'mautic.lead.import.filetoolarge' : 'mautic.lead.import.filenotreadable', [], 'validators'
                    )
                ));
            }
        }

        return null;
    }

    private function processImport(string $fullPath): array
    {
        $fileData = $this->readFile($fullPath);
        $userId   = $this->userHelper->getUser()->getId();
        $event    = new EntityImportEvent(Campaign::ENTITY_NAME, $fileData, $userId);

        $this->dispatcher->dispatch($event);

        return $event->getArgument('import_status') ?? ['error' => 'Unknown status'];
    }
}
