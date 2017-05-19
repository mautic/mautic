<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Model;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\LeadBundle\Entity\Import;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Event\ImportEvent;
use Mautic\LeadBundle\Helper\Progress;
use Mautic\LeadBundle\LeadEvents;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class ImportModel.
 */
class ImportModel extends FormModel
{
    /**
     * @var PathsHelper
     */
    protected $pathsHelper;

    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var NotificationModel
     */
    protected $notificationModel;

    /**
     * @var CoreParametersHelper
     */
    protected $config;

    /**
     * ImportModel constructor.
     *
     * @param PathsHelper          $pathsHelper
     * @param LeadModel            $leadModel
     * @param NotificationModel    $notificationModel
     * @param CoreParametersHelper $config
     */
    public function __construct(
        PathsHelper $pathsHelper,
        LeadModel $leadModel,
        NotificationModel $notificationModel,
        CoreParametersHelper $config
    ) {
        $this->pathsHelper       = $pathsHelper;
        $this->leadModel         = $leadModel;
        $this->notificationModel = $notificationModel;
        $this->config            = $config;
    }

    /**
     * Returns the Import entity which should be processed next.
     *
     * @return Import|null
     */
    public function getImportToProcess()
    {
        $result = $this->getRepository()->getImportsWithStatuses([Import::QUEUED, Import::DELAYED], 1);

        if (isset($result[0]) && $result[0] instanceof Import) {
            return $result[0];
        }

        return null;
    }

    /**
     * Compares current number of imports in progress with the limit from the configuration.
     * If the limit is hit, the import changes its status to delayed.
     *
     * @param Import $import
     *
     * @return bool
     */
    public function checkParallelImportLimit(Import $import)
    {
        $parallelImportLimit = $this->config->getParameter('parallel_import_limit', 1);
        $importsInProgress   = $this->getRepository()->countImportsWithStatuses([$import::IN_PROGRESS]);

        if ($importsInProgress > $parallelImportLimit) {
            $import->setStatus($import::DELAYED)
                ->setStatusInfo($this->translator->trans('mautic.lead.import.parallel.limit.hit', ['%limit%' => $parallelImportLimit]));

            return false;
        }

        return true;
    }

    /**
     * Generates a HTML link to the import detail.
     *
     * @param Import $import
     *
     * @return string
     */
    public function generateLink(Import $import)
    {
        return '<a href="'.$this->router->generate(
            'mautic_contact_import_action',
            ['objectAction' => 'view', 'objectId' => $import->getId()]
        ).'" data-toggle="ajax">'.$import->getOriginalFile().' ('.$import->getId().')</a>';
    }

    /**
     * Check if there are some IN_PROGRESS imports which got stuck for a while.
     * Set those as failed.
     */
    public function setGhostImportsAsFailed()
    {
        $ghostDelay = 2;
        $imports    = $this->getRepository()->getGhostImports($ghostDelay, 5);

        if (empty($imports)) {
            return null;
        }

        foreach ($imports as $import) {
            $import->setStatus($import::FAILED)
                ->setStatusInfo($this->translator->trans('mautic.lead.import.ghost.limit.hit', ['%limit%' => $ghostDelay]))
                ->removeFile();

            $this->notificationModel->addNotification(
                $this->translator->trans(
                    'mautic.lead.import.result.info',
                    ['%import%' => $this->generateLink($import)]
                ),
                'info',
                false,
                $this->translator->trans('mautic.lead.import.failed'),
                'fa-download',
                null,
                $this->em->getReference('MauticUserBundle:User', $import->getCreatedBy())
            );
        }

        $this->saveEntities($imports);
    }

    /**
     * Import next pre-saved import.
     *
     * @return bool|null
     */
    public function processNext(Progress $progress)
    {
        $this->setGhostImportsAsFailed();

        $import = $this->getImportToProcess();

        if (!$import) {
            return;
        }

        if (!$import->canProceed()) {
            $this->saveEntity($import);

            return $import;
        }

        if (!$this->checkParallelImportLimit($import)) {
            $this->saveEntity($import);

            return $import;
        }

        $progress->setTotal($import->getLineCount());
        $progress->setDone($import->getProcessedRows());

        $import->start();
        $this->saveEntity($import);

        $this->process($import, $progress);

        $import->end();
        $this->saveEntity($import);
        $this->notificationModel->addNotification(
            $this->translator->trans(
                'mautic.lead.import.result.info',
                ['%import%' => $this->generateLink($import)]
            ),
            'info',
            false,
            $this->translator->trans('mautic.lead.import.completed'),
            'fa-download',
            null,
            $this->em->getReference('MauticUserBundle:User', $import->getCreatedBy())
        );

        return $import;
    }

    /**
     * Import the CSV file from configuration in the $import entity.
     *
     * @param Import   $import
     * @param Progress $progress
     */
    public function process(Import $import, Progress $progress)
    {
        $leadEventLogRepo = $this->leadModel->getEventLogRepository();
        $config           = $import->getParserConfig();
        $file             = new \SplFileObject($import->getFilePath());
        if ($file !== false) {
            $lineNumber = $progress->getDone();

            if ($lineNumber > 0) {
                $file->seek($lineNumber);
            }

            $batchSize = $config['batchlimit'];

            while ($batchSize && !$file->eof()) {
                $errorMessage = null;
                $data         = $file->fgetcsv($config['delimiter'], $config['enclosure'], $config['escape']);
                array_walk($data, create_function('&$val', '$val = trim($val);'));

                // Ignore the header row
                if ($lineNumber === 0) {
                    ++$lineNumber;
                    continue;
                }

                ++$lineNumber;

                $eventLog = $this->initEventLog($import, $lineNumber);
                $progress->increase();

                // Decrease batch count
                --$batchSize;

                if (is_array($data) && $dataCount = count($data)) {
                    // Ensure the number of headers are equal with data
                    $headerCount = count($import->getHeaders());

                    if ($headerCount !== $dataCount) {
                        $diffCount = ($headerCount - $dataCount);

                        if ($diffCount < 0) {
                            $import->increaseIgnoredCount();
                            $msg = $this->translator->trans('mautic.lead.import.error.header_mismatch');

                            continue;
                        }
                        // Fill in the data with empty string
                        $fill = array_fill($dataCount, $diffCount, '');
                        $data = $data + $fill;
                    }

                    $data = array_combine($import->getHeaders(), $data);

                    try {
                        $prevent = false;
                        foreach ($data as $key => $value) {
                            if ($value != '') {
                                $prevent = true;
                                break;
                            }
                        }
                        if ($prevent) {
                            $merged = $this->leadModel->importLead(
                                $import->getMatchedFields(),
                                $data,
                                $import->getDefault('owner'),
                                $import->getDefault('list'),
                                $import->getDefault('tags'),
                                true,
                                $eventLog
                            );
                            if ($merged) {
                                $import->increaseUpdatedCount();
                            } else {
                                $import->increaseInsertedCount();
                            }
                        } else {
                            $errorMessage = $this->translator->trans('mautic.lead.import.error.line_empty');
                        }
                    } catch (\Exception $e) {
                        // Email validation likely failed
                        $errorMessage = $e->getMessage();
                    }
                } else {
                    $errorMessage = $this->translator->trans('mautic.lead.import.error.line_empty');
                }

                if ($errorMessage) {
                    // Inform Import entity about the failed row
                    $import->increaseIgnoredCount();

                    // Save log about errored line
                    $eventLog->addProperty('error', $errorMessage)
                        ->setAction('failed');
                    $leadEventLogRepo->saveEntity($eventLog);
                }

                // Save Import entity once per batch so the user could see the progress
                if ($batchSize === 0 && $import->isBackgroundProcess()) {
                    $isPublished = $this->getRepository()->getValue($import->getId(), 'is_published');

                    if (!$isPublished) {
                        $import->setStatus($import::STOPPED);
                    }

                    $this->saveEntity($import);

                    // Stop the import loop if the import got unpublished
                    if (!$isPublished) {
                        break;
                    }

                    $batchSize = $config['batchlimit'];
                }
            }
        }

        // Close the file
        $file = null;
    }

    /**
     * Initialize LeadEventLog object and configure it as the import event.
     *
     * @param Import $import
     * @param int    $lineNumber
     *
     * @return LeadEventLog
     */
    public function initEventLog(Import $import, int $lineNumber)
    {
        $eventLog = new LeadEventLog();
        $eventLog->setUserId($import->getCreatedBy())
            ->setUserName($import->getCreatedByUser())
            ->setBundle('lead')
            ->setObject('import')
            ->setObjectId($import->getId())
            ->setProperties(
                [
                    'line' => $lineNumber,
                    'file' => $import->getOriginalFile(),
                ]
            );

        return $eventLog;
    }

    /**
     * Returns a list of failed rows for the import.
     *
     * @param int $importId
     *
     * @return array|null
     */
    public function getFailedRows(int $importId = null)
    {
        if (!$importId) {
            return null;
        }

        return $this->getEventLogRepository()->getFailedRows($importId, ['select' => 'properties,id']);
    }

    /**
     * @return ImportRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticLeadBundle:Import');
    }

    /**
     * @return LeadEventLogRepository
     */
    public function getEventLogRepository()
    {
        return $this->em->getRepository('MauticLeadBundle:LeadEventLog');
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getPermissionBase()
    {
        return 'lead:imports';
    }

    /**
     * Returns a unique name of a CSV file based on time.
     *
     * @return string
     */
    public function getUniqueFileName()
    {
        return (new DateTimeHelper())->toUtcString('YmdHis').'.csv';
    }

    /**
     * Returns a full path to the import dir.
     *
     * @return string
     */
    public function getImportDir()
    {
        $tmpDir = $this->pathsHelper->getSystemPath('tmp', true);

        return $tmpDir.'/imports';
    }

    /**
     * Get a specific entity or generate a new one if id is empty.
     *
     * @param $id
     *
     * @return null|object
     */
    public function getEntity($id = null)
    {
        if ($id === null) {
            return new Import();
        }

        return parent::getEntity($id);
    }

    /**
     * {@inheritdoc}
     *
     * @param $action
     * @param $event
     * @param $entity
     * @param $isNew
     *
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null)
    {
        if (!$entity instanceof Import) {
            throw new MethodNotAllowedHttpException(['Import']);
        }

        switch ($action) {
            case 'pre_save':
                $name = LeadEvents::IMPORT_PRE_SAVE;
                break;
            case 'post_save':
                $name = LeadEvents::IMPORT_POST_SAVE;
                break;
            case 'pre_delete':
                $name = LeadEvents::IMPORT_PRE_DELETE;
                break;
            case 'post_delete':
                $name = LeadEvents::IMPORT_POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new ImportEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($name, $event);

            return $event;
        } else {
            return null;
        }
    }
}
