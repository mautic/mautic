<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Form\Type\CampaignImportType;
use Mautic\CoreBundle\Controller\AbstractFormController;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\CoreBundle\Event\EntityImportUndoEvent;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Translation\Translator;
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

final class ImportController extends AbstractFormController
{
    // Steps of the import
    public const STEP_UPLOAD_ZIP      = 1;

    public const STEP_PROGRESS_BAR    = 2;

    public const STEP_IMPORT_FROM_ZIP = 3;

    public function __construct(
        ManagerRegistry $doctrine,
        CoreParametersHelper $coreParametersHelper,
        ModelFactory $modelFactory,
        private UserHelper $userHelper,
        EventDispatcherInterface $dispatcher,
        Translator $translator,
        FlashBag $flashBag,
        private RequestStack $requestStack,
        CorePermissions $security,
        private LoggerInterface $logger,
        private PathsHelper $pathsHelper,
        private FormFactoryInterface $formFactory,
    ) {
        parent::__construct($doctrine, $modelFactory, $userHelper, $coreParametersHelper, $dispatcher, $translator, $flashBag, $requestStack, $security);
    }

    public function newAction(Request $request): Response
    {
        if (!$this->security->isAdmin() && !$this->security->isGranted('campaign:imports:create')) {
            return $this->accessDenied();
        }

        $fullPath = $this->getFullZipPath();
        $step     = $this->getImportStep($request, $fullPath);

        $form = $this->formFactory->create(CampaignImportType::class, [], [
            'action' => $this->generateUrl('mautic_campaign_import_action', ['objectAction' => 'upload']),
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

    public function uploadAction(Request $request): ?Response
    {
        $fullPath = $this->getFullZipPath();
        $fileName = $this->getImportFileName();

        $importDir = $this->pathsHelper->getImportCampaignsPath();
        $form      = $this->formFactory->create(CampaignImportType::class, [], [
            'action' => $this->generateUrl('mautic_campaign_import_action', ['objectAction' => 'upload']),
        ]);

        // Handle cancel action
        if ($this->isFormCancelled($form)) {
            $this->resetImport();
            $this->removeImportFile($fullPath);
            $this->logger->log(LogLevel::WARNING, "Import for file {$fullPath} was canceled.");

            return $this->newAction($request, true);
        }

        // Validate form before processing
        if (!$this->isFormValid($form)) {
            $this->logger->error('No file uploaded.');
            $form->addError(new FormError($this->translator->trans('mautic.campaign.import.incorrectfile', [], 'validators')));

            return $this->delegateView([
                'viewParameters'  => [
                    'mauticContent' => 'campaignImport',
                    'form'          => $form->createView(),
                ],
                'contentTemplate' => '@MauticCampaign/Import/import.html.twig',
                'passthroughVars' => [
                    'mauticContent' => 'campaignImport',
                    'route'         => $this->generateUrl('mautic_campaign_import_action', ['objectAction' => 'new']),
                ],
            ]);
        }

        // Retrieve uploaded file
        $fileData = $request->files->get('campaign_import')['campaignFile'] ?? null;

        if (!$fileData) {
            $this->logger->error('No file uploaded.');
            $form->addError(new FormError($this->translator->trans('mautic.campaign.import.nofile', [], 'validators')));
            // $this->addFlashMessage('mautic.campaign.import.nofile', [], FlashBag::LEVEL_ERROR, 'validators');

            return $this->delegateView([
                'viewParameters'  => [
                    'mauticContent' => 'campaignImport',
                    'form'          => $form->createView(),
                ],
                'contentTemplate' => '@MauticCampaign/Import/import.html.twig',
                'passthroughVars' => [
                    'mauticContent' => 'campaignImport',
                    'route'         => $this->generateUrl('mautic_campaign_import_action', ['objectAction' => 'new']),
                ],
            ]);
        }
        // Set progress to 0 before import starts
        $this->requestStack->getSession()->set('mautic.campaign.import.step', self::STEP_PROGRESS_BAR);
        $this->requestStack->getSession()->set('mautic.campaign.import.progress', 0);
        $this->requestStack->getSession()->remove('mautic.campaign.import.summary');
        try {
            // Ensure the import directory exists
            (new Filesystem())->mkdir($importDir, 0755);

            // Remove existing file if it exists
            if (file_exists($fullPath)) {
                if (!unlink($fullPath)) {
                    $this->logger->error("Failed to delete existing file before new upload: {$fullPath}");
                }
            }

            // Move uploaded file
            $fileData->move($importDir, $fileName);

            // Update session with the new file and progress reset
            $this->requestStack->getSession()->set('mautic.campaign.import.file', $fullPath);
            $this->logger->info("File successfully uploaded: {$fullPath}");

            return $this->redirectToRoute('mautic_campaign_import_action', ['objectAction' => 'progress']);
        } catch (FileException $e) {
            $this->logger->error('File upload failed: '.$e->getMessage());

            $form->addError(new FormError(
                $this->translator->trans(
                    str_contains($e->getMessage(), 'upload_max_filesize')
                        ? 'mautic.lead.import.filetoolarge'
                        : 'mautic.lead.import.filenotreadable',
                    [],
                    'validators'
                )
            ));
        }

        return $this->delegateView([
            'viewParameters'  => ['mauticContent' => 'campaignImport'],
            'contentTemplate' => '@MauticCampaign/Import/import.html.twig',
            'passthroughVars' => [
                'mauticContent' => 'campaignImport',
                'route'         => $this->generateUrl('mautic_campaign_import_action', ['objectAction' => 'new']),
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
            $this->addFlashMessage('mautic.campaign.error.import.file.missing', ['%file%' => $fullPath], FlashBag::LEVEL_ERROR);
            $this->requestStack->getSession()->set('mautic.campaign.import.step', self::STEP_UPLOAD_ZIP);

            return self::STEP_UPLOAD_ZIP;
        }

        return $step;
    }

    public function progressAction(): Response
    {
        $session       = $this->requestStack->getSession();
        $progress      = $session->get('mautic.campaign.import.progress', 0);
        $importSummary = $session->get('mautic.campaign.import.summary', []);
        $this->requestStack->getSession()->set('mautic.campaign.import.step', self::STEP_IMPORT_FROM_ZIP);

        // If the import is already complete, return the final view
        if ($progress >= 100) {
            $campaignSummary = $importSummary['summary']['campaign'] ?? null;
            $campaignName    = $campaignSummary['name'][0] ?? 'Unknown';
            $campaignId      = $campaignSummary['id'][0] ?? 0;

            $this->addFlashMessage(
                'mautic.campaign.notice.import.finished',
                ['%id%' => $campaignId, '%name%' => $campaignName]
            );

            return $this->delegateView([
                'viewParameters' => [
                    'importProgress' => ['progress' => 100],
                    'importSummary'  => $importSummary,
                    'mauticContent'  => 'campaignImport',
                ],
                'contentTemplate' => '@MauticCampaign/Import/progress.html.twig',
            ]);
        }

        // Get the import file path from the session
        $fullPath = $session->get('mautic.campaign.import.file');

        // If there's no valid file, show an error
        if (!$fullPath || !file_exists($fullPath)) {
            $this->logger->error("Import file missing: {$fullPath}");
            $session->set('mautic.campaign.import.progress', 100);

            return $this->delegateView([
                'viewParameters' => [
                    'importProgress' => ['progress' => 100],
                    'importSummary'  => ['error' => 'File not found.'],
                    'mauticContent'  => 'campaignImport',
                ],
                'contentTemplate' => '@MauticCampaign/Import/progress.html.twig',
            ]);
        }

        // Process import and update progress
        $importSummary = $this->processImport($fullPath);
        $this->resetImport();

        return $this->delegateView([
            'viewParameters' => [
                'importProgress' => ['progress' => 100],
                'importSummary'  => $importSummary,
                'mauticContent'  => 'campaignImport',
            ],
            'contentTemplate' => '@MauticCampaign/Import/progress.html.twig',
        ]);
    }

    private function processImport(string $fullPath): array
    {
        $session = $this->requestStack->getSession();
        $session->set('mautic.campaign.import.progress', 25);

        $fileData = $this->readFile($fullPath);
        if (empty($fileData)) {
            $this->logger->error('Import failed: No data found in file.');

            return ['error' => 'Invalid file data.'];
        }

        $session->set('mautic.campaign.import.progress', 50);

        $userId = $this->userHelper->getUser()->getId();
        $event  = new EntityImportEvent(Campaign::ENTITY_NAME, $fileData, $userId);

        $session->set('mautic.campaign.import.progress', 75);

        $this->dispatcher->dispatch($event);

        $session->set('mautic.campaign.import.progress', 100);
        $importSummary = $event->getArgument('import_status') ?? ['error' => 'Unknown status'];

        $session->set('mautic.campaign.import.summary', $importSummary);

        $campaignSummary = $importSummary['summary']['campaign'] ?? null;
        $campaignName    = $campaignSummary['name'][0] ?? 'Unknown';
        $campaignId      = $campaignSummary['id'][0] ?? 0;

        $this->addFlashMessage(
            'mautic.campaign.notice.import.finished',
            ['%id%' => $campaignId, '%name%' => $campaignName]
        );

        return $importSummary;
    }

    public function undoAction(): JsonResponse
    {
        if (!$this->security->isAdmin() && !$this->security->isGranted('campaign:imports:delete')) {
            return $this->accessDenied();
        }
        $session       = $this->requestStack->getSession();
        $importSummary = $session->get('mautic.campaign.import.summary', []);

        if (!isset($importSummary['summary'])) {
            $this->addFlashMessage('mautic.campaign.notice.import.undo_no_data');

            return new JsonResponse(['flashes'    => $this->getFlashContent()]);
        }
        // Dispatch the undo import event
        $undoEvent = new EntityImportUndoEvent($importSummary['summary']);
        $undoEvent = $this->dispatcher->dispatch($undoEvent);

        $this->logger->info('Undo import triggered for Campaign.');

        $this->addFlashMessage('mautic.campaign.notice.import.undo');

        return new JsonResponse(['flashes'    => $this->getFlashContent()]);
    }
}
