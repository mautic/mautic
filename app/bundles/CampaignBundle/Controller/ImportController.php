<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Form\Type\CampaignImportType;
use Mautic\CoreBundle\Controller\AbstractFormController;
use Mautic\CoreBundle\Event\EntityImportAnalyzeEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\CoreBundle\Event\EntityImportUndoEvent;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\ImportHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\FormBundle\Entity\Action;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
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

    public function newAction(): Response
    {
        if (!$this->security->isGranted('campaign:imports:create')) {
            return $this->accessDenied();
        }

        $session  = $this->requestStack->getSession();
        $filePath = $session->get('mautic.campaign.import.file');

        if ($filePath && file_exists($filePath)) {
            @unlink($filePath);
            $this->logger->info("Removed leftover import file on refresh: {$filePath}");
        }

        $this->resetImport();

        $form = $this->formFactory->create(CampaignImportType::class, [], [
            'action' => $this->generateUrl('mautic_campaign_import_action', ['objectAction' => 'upload']),
        ]);

        return $this->delegateView([
            'viewParameters'  => [
                'form'          => $form->createView(),
                'mauticContent' => 'campaignImport',
            ],
            'contentTemplate' => '@MauticCampaign/Import/import.html.twig',
        ]);
    }

    public function uploadAction(Request $request): Response
    {
        if (!$this->security->isGranted('campaign:imports:create')) {
            return $this->accessDenied();
        }

        $fullPath = $this->pathsHelper->getImportCampaignsPath().'/'.$this->getImportFileName();
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

            return $this->newAction();
        }

        // Validate form before processing
        if (!$this->isFormValid($form)) {
            $this->logger->error('No file uploaded.');
            $form->addError(new FormError($this->translator->trans('mautic.campaign.import.incorrectfile', [], 'validators')));
        } else {
            // Retrieve uploaded file
            $fileData = $request->files->get('campaign_import')['campaignFile'] ?? null;

            if (!$fileData) {
                $this->logger->error('No file uploaded.');
                $form->addError(new FormError($this->translator->trans('mautic.campaign.import.nofile', [], 'validators')));
            } else {
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
            }
        }

        return $this->delegateView([
            'viewParameters'  => [
                'mauticContent' => 'campaignImport',
                'form'          => $form->createView(),
            ],
            'contentTemplate' => '@MauticCampaign/Import/import.html.twig',
        ]);
    }

    /**
     * Cancels import by removing the uploaded file.
     */
    public function cancelAction(): Response
    {
        if (!$this->security->isGranted('campaign:imports:create')) {
            return $this->accessDenied();
        }

        $filePath = $this->requestStack->getSession()->get('mautic.campaign.import.file');

        if (is_string($filePath)) {
            $this->removeImportFile($filePath);
        }

        $this->resetImport();
        $this->addFlashMessage('mautic.campaign.notice.import.canceled', [], FlashBag::LEVEL_NOTICE);

        return $this->redirectToRoute('mautic_campaign_import_action', ['objectAction' => 'new']);
    }

    private function resetImport(): void
    {
        $this->requestStack->getSession()->set('mautic.campaign.import.file', null);
        $this->requestStack->getSession()->set('mautic.campaign.import.step', self::STEP_UPLOAD_ZIP);
        $this->requestStack->getSession()->set('mautic.campaign.import.progress', 0);
        $this->requestStack->getSession()->remove('mautic.campaign.import.analyzeSummary');
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

        $uniqueId = bin2hex(random_bytes(8));
        $fileName = sprintf('%s_%s.zip', (new DateTimeHelper())->toUtcString('YmdHis'), $uniqueId);

        $session->set('mautic.campaign.import.file', $fileName);

        return $fileName;
    }

    public function progressAction(ImportHelper $importHelper): Response
    {
        $session       = $this->requestStack->getSession();
        $session->get('mautic.campaign.import.progress', 0);
        $step          = $session->get('mautic.campaign.import.step', self::STEP_PROGRESS_BAR);
        $fullPath      = $session->get('mautic.campaign.import.file');

        // If there's no valid file, show an error
        if (!$fullPath || !file_exists($fullPath)) {
            if (self::STEP_UPLOAD_ZIP !== $step) {
                $this->addFlashMessage('mautic.campaign.import.nofile', [], FlashBag::LEVEL_ERROR, 'validators');
            }
            $this->resetImport();

            return $this->redirectToRoute('mautic_campaign_import_action', ['objectAction' => 'new']);
        }

        if (self::STEP_PROGRESS_BAR === $step) {
            $analyzeSummary = $this->analyzeData($importHelper, $fullPath);

            if (empty($analyzeSummary)) {
                $this->addFlashMessage('mautic.campaign.import.nofile', [], FlashBag::LEVEL_ERROR, 'validators');
                $this->removeImportFile($fullPath);
                $this->resetImport();

                return $this->redirectToRoute('mautic_campaign_import_action', ['objectAction' => 'new']);
            }
            $session->set('mautic.campaign.import.step', self::STEP_IMPORT_FROM_ZIP);
            $session->set('mautic.campaign.import.analyzeSummary', $analyzeSummary);

            return $this->delegateView([
                'viewParameters' => [
                    'importProgress'  => 50,
                    'analyzeSummary'  => $analyzeSummary,
                    'mauticContent'   => 'campaignImport',
                ],
                'contentTemplate' => '@MauticCampaign/Import/progress.html.twig',
            ]);
        } else {
            try {
                $fileData      = $importHelper->readZipFile($fullPath);
                $userId        = $this->userHelper->getUser()->getId();
                $importSummary = [];

                $importActions = $this->requestStack->getCurrentRequest()->get('importAction', []);

                $importHelper->recursiveRemoveEmailaddress($fileData);

                // Loop through importActions and clean UUIDs for 'create' actions
                foreach ($fileData as &$group) {
                    foreach ($importActions as $entityType => $entities) {
                        if (in_array($entityType, [Event::ENTITY_NAME, Field::ENTITY_NAME, Action::ENTITY_NAME], true)) {
                            continue;
                        }
                        if (!isset($group[$entityType])) {
                            continue;
                        }

                        foreach ($entities as $entityUuid => $action) {
                            if ('create' !== $action) {
                                continue;
                            }

                            foreach ($group[$entityType] as &$item) {
                                if (isset($item['uuid']) && (int) $item['uuid'] === (int) $entityUuid) {
                                    if (Campaign::ENTITY_NAME == $entityType) {
                                        foreach ($group[Event::ENTITY_NAME] as &$eventItem) {
                                            $eventItem['uuid'] = '';
                                        }
                                    }
                                    if (Form::ENTITY_NAME == $entityType) {
                                        if (isset($group[Field::ENTITY_NAME])) {
                                            foreach ($group[Field::ENTITY_NAME] as &$fieldItem) {
                                                $fieldItem['uuid'] = '';
                                            }
                                        }
                                        if (isset($group[Action::ENTITY_NAME])) {
                                            foreach ($group[Action::ENTITY_NAME] as &$actionItem) {
                                                $actionItem['uuid'] = '';
                                            }
                                        }
                                    }
                                    $item['uuid'] = '';
                                    break;
                                }
                            }
                        }
                    }
                }

                foreach ($fileData as $entity) {
                    $event  = new EntityImportEvent(Campaign::ENTITY_NAME, $entity, $userId);
                    $this->dispatcher->dispatch($event);
                    $summary = $event->getStatus();
                    if (!empty($summary)) {
                        $importSummary[] = $summary;
                    }
                }

                foreach ($importSummary as $summary) {
                    foreach ([EntityImportEvent::NEW, EntityImportEvent::UPDATE] as $status) {
                        if (!isset($summary[$status][Campaign::ENTITY_NAME])) {
                            continue;
                        }

                        $campaignData    = $summary[$status][Campaign::ENTITY_NAME];
                        $campaignName    = $campaignData['names'][0] ?? 'Unknown';
                        $campaignId      = $campaignData['ids'][0] ?? 0;

                        $this->addFlashMessage(
                            'mautic.campaign.notice.import.finished',
                            [
                                '%id%'   => $campaignId,
                                '%name%' => htmlspecialchars($campaignName, ENT_QUOTES, 'UTF-8'),
                            ]
                        );
                    }
                }

                $this->removeImportFile($fullPath);
                $session->set('mautic.campaign.import.summary', $importSummary);
                $this->resetImport();
            } catch (\RuntimeException $e) {
                $this->logger->error($e->getMessage());
                $this->addFlashMessage('mautic.campaign.import.nofile', [], FlashBag::LEVEL_ERROR, 'validators');

                $this->removeImportFile($fullPath);
                $importSummary = [
                    EntityImportEvent::ERRORS => [$e->getMessage()],
                ];
            }

            return $this->delegateView([
                'viewParameters' => [
                    'importProgress'  => 100,
                    'importSummary'   => $importSummary,
                    'mauticContent'   => 'campaignImport',
                ],
                'contentTemplate' => '@MauticCampaign/Import/progress.html.twig',
            ]);
        }
    }

    /**
     * @return array<int|string, array<string, mixed>>
     */
    private function analyzeData(ImportHelper $importHelper, string $fullPath): array
    {
        try {
            $fileData = $importHelper->readZipFile($fullPath);
        } catch (\RuntimeException $e) {
            $this->logger->error($e->getMessage());
            $this->removeImportFile($fullPath);

            return [
                [
                    'errors' => [
                        'messages' => [$e->getMessage()],
                    ],
                ],
            ];
        }

        $allData = [];
        foreach ($fileData as $entityData) {
            $mergedSummary = [];
            foreach ($entityData as $key => $data) {
                if (empty($data)) {
                    continue;
                }

                $event = new EntityImportAnalyzeEvent($key, $data);
                $this->dispatcher->dispatch($event);
                $summary = $event->getSummary();

                foreach ($summary as $status => $entities) {
                    if ('errors' === $status) {
                        // Accumulate errors into a flat array
                        $mergedSummary['errors'] = array_merge(
                            $mergedSummary['errors'] ?? [],
                            is_array($entities) ? $entities : [$entities]
                        );
                        continue;
                    }
                    foreach ($entities as $entityName => $info) {
                        if (!isset($mergedSummary[$status][$entityName])) {
                            $mergedSummary[$status][$entityName] = [
                                'names'   => [],
                                'uuids'   => [],
                            ];
                        }

                        $mergedSummary[$status][$entityName]['names'] = array_merge(
                            $mergedSummary[$status][$entityName]['names'],
                            $info['names'] ?? []
                        );
                        $mergedSummary[$status][$entityName]['uuids'] = array_merge(
                            $mergedSummary[$status][$entityName]['uuids'],
                            $info['uuids'] ?? []
                        );
                    }
                }
            }
            if (!empty($mergedSummary)) {
                $allData[] = $mergedSummary;
            }
        }

        return $allData;
    }

    public function undoAction(): JsonResponse
    {
        if (!$this->security->isGranted('campaign:imports:delete')) {
            return $this->accessDenied();
        }

        $session         = $this->requestStack->getSession();
        $importSummaries = $session->get('mautic.campaign.import.summary', []);

        $hasUndoData = false;

        foreach ($importSummaries as $summary) {
            $updates  = $summary[EntityImportEvent::UPDATE] ?? [];
            $newItems = $summary[EntityImportEvent::NEW] ?? [];

            // Only trigger undo if there are no updates and we have new items
            if (empty($updates) && !empty($newItems)) {
                foreach ($newItems as $entityType => $data) {
                    if (!empty($data['ids'])) {
                        $undoEvent = new EntityImportUndoEvent($entityType, $data);
                        $this->dispatcher->dispatch($undoEvent);
                        $hasUndoData = true;
                    }
                }
            }
        }

        if ($hasUndoData) {
            $this->logger->info('Undo import triggered for Campaign.');
            $this->addFlashMessage('mautic.campaign.notice.import.undo');
        } else {
            $this->addFlashMessage('mautic.campaign.notice.import.undo_no_data');
        }

        return new JsonResponse(['flashes' => $this->getFlashContent()]);
    }
}
