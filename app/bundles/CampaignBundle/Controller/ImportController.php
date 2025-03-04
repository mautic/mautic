<?php

namespace Mautic\CampaignBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CampaignBundle\Form\Type\CampaignImportType;
use Mautic\CoreBundle\Controller\FormController;
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
use Mautic\LeadBundle\Helper\Progress;
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

class ImportController extends FormController
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
        UserHelper $userHelper,
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

        $forceStop = $request->get('cancel', false);
        $step      = ($forceStop) ? self::STEP_UPLOAD_ZIP : $this->requestStack->getSession()->get('mautic.campaign.import.step', self::STEP_UPLOAD_ZIP);
        $importDir = $this->getImportDirName();
        $fileName  = $this->getImportFileName();
        $fs        = new Filesystem();
        $complete  = false;

        $fullPath  = $this->getFullZipPath();

        if ($ignorePost) {
            dump($step, $fileName, $fullPath);
        }

        if (!file_exists($fullPath) && self::STEP_UPLOAD_ZIP !== $step) {
            // Force step one if the file doesn't exist
            $this->logger->log(LogLevel::WARNING, "File {$fullPath} does not exist anymore. Reseting import to step STEP_UPLOAD_ZIP.");
            $this->addFlashMessage('mautic.import.file.missing', ['%file%' => $fullPath], FlashBag::LEVEL_ERROR);
            $step = self::STEP_UPLOAD_ZIP;
            $this->requestStack->getSession()->set('mautic.campaign.import.step', self::STEP_UPLOAD_ZIP);
        }

        $progress = (new Progress())->bindArray($this->requestStack->getSession()->get('mautic.campaign.import.progress', [0, 0]));
        $action   = $this->generateUrl('mautic_campaign_import_action', ['objectAction' => 'new']);

        switch ($step) {
            case self::STEP_UPLOAD_ZIP:
                if ($forceStop) {
                    $this->resetImport();
                    $this->removeImportFile($fullPath);
                    $this->logger->log(LogLevel::WARNING, "Import for file {$fullPath} was force-stopped.");
                }

                $form = $this->formFactory->create(CampaignImportType::class, [], ['action' => $action]);
                break;
            case self::STEP_PROGRESS_BAR:
                // Just show the progress form
                $this->requestStack->getSession()->set('mautic.campaign.import.step', self::STEP_IMPORT_FROM_ZIP);
                break;
        }

        if (!$ignorePost && 'POST' === $request->getMethod()) {
            if (!isset($form) || $this->isFormCancelled($form)) {
                $this->resetImport();
                $this->removeImportFile($fullPath);
                $reason = isset($form) ? 'the form is empty' : 'the form was canceled';
                $this->logger->log(LogLevel::WARNING, "Import for file {$fullPath} was aborted because {$reason}.");

                return $this->newAction($request, true);
            }

            if (self::STEP_UPLOAD_ZIP === $step) {
                $valid = $this->isFormValid($form);

                if ($valid) {
                    if (file_exists($fullPath)) {
                        unlink($fullPath);
                    }

                    $fileData = $request->files->get('campaign_import')['campaignFile'] ?? null;

                    if (!empty($fileData)) {
                        $errorMessage    = null;
                        $errorParameters = [];
                        try {
                            $fs->mkdir($importDir);

                            $fileData->move($importDir, $fileName);

                            // $this->requestStack->getSession()->set('mautic.campaign.import.file', $fileName);
                            $this->requestStack->getSession()->set('mautic.campaign.import.step', self::STEP_PROGRESS_BAR);
                            // $this->requestStack->getSession()->set('mautic.campaign.import.original.file', $fileData->getClientOriginalName());

                            return $this->newAction($request, true);
                        } catch (FileException $e) {
                            if (str_contains($e->getMessage(), 'upload_max_filesize')) {
                                $errorMessage    = 'mautic.lead.import.filetoolarge';
                                $errorParameters = [
                                    '%upload_max_filesize%' => ini_get('upload_max_filesize'),
                                ];
                            } else {
                                $errorMessage = 'mautic.lead.import.filenotreadable';
                            }
                        } catch (\Exception) {
                            $errorMessage = 'mautic.lead.import.filenotreadable';
                        } finally {
                            if (!is_null($errorMessage)) {
                                $form->addError(
                                    new FormError(
                                        $this->translator->trans($errorMessage, $errorParameters, 'validators')
                                    )
                                );
                            }
                        }
                    }
                }
            } else {
                // Done or something wrong
                $this->resetImport();
                $this->removeImportFile($fullPath);
                $this->logger->log(LogLevel::ERROR, "Import for file {$fullPath} was aborted for unknown step of '{$step}'");
            }
        }

        if (self::STEP_UPLOAD_ZIP === $step) {
            $contentTemplate = '@MauticCampaign/Import/import.html.twig';
            $viewParameters  = [
                'form'          => $form->createView(),
                'mauticContent' => 'campaignImport',
            ];
        } else {
            $contentTemplate = '@MauticCampaign/Import/progress.html.twig';
            $viewParameters  = [
                'progress'         => $progress,
                'complete'         => $complete,
                'mauticContent'    => 'campaignImport',
            ];
            // $progress = $this->requestStack->getSession()->get('mautic.campaign.import.progress', [0, 100]);
        }

        $viewParameters['step'] = $step;

        $response = $this->delegateView(
            [
                'viewParameters'  => $viewParameters,
                'contentTemplate' => $contentTemplate,
                'passthroughVars' => [
                    // 'activeLink'    => $initEvent->activeLink,
                    'mauticContent' => 'campaignImport',
                    'route'         => $this->generateUrl(
                        'mautic_campaign_import_action',
                        [
                            'objectAction' => 'new',
                        ]
                    ),
                    'step'     => $step,
                    'progress' => $progress,
                ],
            ]
        );
        // For uploading file Keep-Alive should not be used.
        $response->headers->set('Connection', 'close');

        return $response;
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
        $this->requestStack->getSession()->set('mautic.campaign.import.headers', []);
        $this->requestStack->getSession()->set('mautic.campaign.import.file', null);
        $this->requestStack->getSession()->set('mautic.campaign.import.step', self::STEP_UPLOAD_ZIP);
        $this->requestStack->getSession()->set('mautic.campaign.import.progress', [0, 0]);
        $this->requestStack->getSession()->set('mautic.campaign.import.inprogress', false);
        $this->requestStack->getSession()->set('mautic.campaign.import.importfields', []);
        $this->requestStack->getSession()->set('mautic.campaign.import.original.file', null);
        $this->requestStack->getSession()->set('mautic.campaign.import.id', null);
    }

    private function removeImportFile(string $filepath): void
    {
        if (file_exists($filepath) && is_readable($filepath)) {
            unlink($filepath);

            $this->logger->log(LogLevel::WARNING, "File {$filepath} was removed.");
        }
    }

    protected function getImportDirName(): string
    {
        return $this->pathsHelper->getImportCampaignsPath();
    }

    /**
     * Generates unique import directory name inside the cache dir if not stored in the session.
     * If it exists in the session, returns that one.
     */
    protected function getImportFileName(): string
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
    protected function getFullZipPath(): string
    {
        return $this->getImportDirName().'/'.$this->getImportFileName();
    }
}
