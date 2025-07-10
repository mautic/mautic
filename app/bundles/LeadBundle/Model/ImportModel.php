<?php

namespace Mautic\LeadBundle\Model;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\CoreBundle\ProcessSignal\ProcessSignalService;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Import;
use Mautic\LeadBundle\Entity\ImportRepository;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Entity\LeadEventLogRepository;
use Mautic\LeadBundle\Event\ImportEvent;
use Mautic\LeadBundle\Event\ImportProcessEvent;
use Mautic\LeadBundle\Exception\ImportDelayedException;
use Mautic\LeadBundle\Exception\ImportFailedException;
use Mautic\LeadBundle\Helper\Progress;
use Mautic\LeadBundle\LeadEvents;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @extends FormModel<Import>
 */
class ImportModel extends FormModel
{
    protected LeadEventLogRepository $leadEventLogRepo;

    public function __construct(
        protected PathsHelper $pathsHelper,
        protected LeadModel $leadModel,
        protected NotificationModel $notificationModel,
        protected CoreParametersHelper $config,
        protected CompanyModel $companyModel,
        EntityManagerInterface $em,
        CorePermissions $security,
        EventDispatcherInterface $dispatcher,
        UrlGeneratorInterface $router,
        Translator $translator,
        UserHelper $userHelper,
        LoggerInterface $mauticLogger,
        private ProcessSignalService $processSignalService,
    ) {
        $this->leadEventLogRepo  = $leadModel->getEventLogRepository();

        parent::__construct($em, $security, $dispatcher, $router, $translator, $userHelper, $mauticLogger, $config);
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
     */
    public function checkParallelImportLimit(): bool
    {
        $parallelImportLimit = $this->getParallelImportLimit();
        $importsInProgress   = $this->getRepository()->countImportsInProgress();

        return !($importsInProgress >= $parallelImportLimit);
    }

    /**
     * Returns parallel import limit from the configuration.
     *
     * @param int $default
     *
     * @return int
     */
    public function getParallelImportLimit($default = 1)
    {
        return $this->config->get('parallel_import_limit', $default);
    }

    /**
     * Generates a HTML link to the import detail.
     */
    public function generateLink(Import $import): string
    {
        return '<a href="'.$this->router->generate(
            'mautic_import_action',
            ['objectAction' => 'view', 'object' => 'lead', 'objectId' => $import->getId()]
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

            if ($import->getCreatedBy()) {
                $this->notificationModel->addNotification(
                    $this->translator->trans(
                        'mautic.lead.import.result.info',
                        ['%import%' => $this->generateLink($import)]
                    ),
                    'info',
                    false,
                    $this->translator->trans('mautic.lead.import.failed', ['%reason%' =>  $import->getStatusInfo()]),
                    'ri-download-line',
                    null,
                    $this->em->getReference(\Mautic\UserBundle\Entity\User::class, $import->getCreatedBy())
                );
            }
        }

        $this->saveEntities($imports);
    }

    /**
     * Start import. This is meant for the CLI command since it will import
     * the whole file at once.
     *
     * @param int $limit Number of records to import before delaying the import. 0 will import all
     *
     * @throws ImportFailedException
     * @throws ImportDelayedException
     */
    public function beginImport(Import $import, Progress $progress, $limit = 0): void
    {
        $this->setGhostImportsAsFailed();

        if (!$import) {
            $msg = 'import is empty, closing the import process';
            $this->logDebug($msg, $import);
            throw new ImportFailedException($msg);
        }

        if (!$import->canProceed()) {
            $this->saveEntity($import);
            $msg = 'import cannot be processed because '.$import->getStatusInfo();
            $this->logDebug($msg, $import);
            throw new ImportFailedException($msg);
        }

        if (!$this->checkParallelImportLimit()) {
            $info = $this->translator->trans(
                'mautic.lead.import.parallel.limit.hit',
                ['%limit%' => $this->getParallelImportLimit()]
            );
            $import->setStatus($import::DELAYED)->setStatusInfo($info);
            $this->saveEntity($import);
            $msg = 'import is delayed because parrallel limit was hit. '.$import->getStatusInfo();
            $this->logDebug($msg, $import);
            throw new ImportDelayedException($msg);
        }

        $processed = $import->getProcessedRows();
        $total     = $import->getLineCount();
        $pending   = $total - $processed;

        if ($limit && $limit < $pending) {
            $processed = 0;
            $total     = $limit;
        }

        $progress->setTotal($total);
        $progress->setDone($processed);

        $import->start();

        // Save the start changes so the user could see it
        $this->saveEntity($import);
        $this->logDebug('The background import is about to start', $import);

        try {
            if (!$this->process($import, $progress, $limit)) {
                throw new ImportFailedException($import->getStatusInfo());
            }
        } catch (ORMException $e) {
            // The EntityManager is probably closed. The entity cannot be saved.
            $info = $this->translator->trans(
                'mautic.lead.import.database.exception',
                ['%message%' => $e->getMessage()]
            );

            $import->setStatus($import::DELAYED)->setStatusInfo($info);

            throw new ImportFailedException('Database had been overloaded');
        }

        $import->end();
        $this->logDebug('The background import has ended', $import);

        // Save the end changes so the user could see it
        $this->saveEntity($import);

        if ($import->getCreatedBy()) {
            $this->notificationModel->addNotification(
                $this->translator->trans(
                    'mautic.lead.import.result.info',
                    ['%import%' => $this->generateLink($import)]
                ),
                'info',
                false,
                $this->translator->trans('mautic.lead.import.completed'),
                'ri-download-line',
                null,
                $this->em->getReference(\Mautic\UserBundle\Entity\User::class, $import->getCreatedBy())
            );
        }
    }

    /**
     * Import the CSV file from configuration in the $import entity.
     *
     * @param int $limit Number of records to import before delaying the import
     */
    public function process(Import $import, Progress $progress, $limit = 0): bool
    {
        try {
            $file = new \SplFileObject($import->getFilePath());
        } catch (\Exception $e) {
            $import->setStatusInfo('SplFileObject cannot read the file. '.$e->getMessage());
            $import->setStatus(Import::FAILED);
            $this->logDebug('import cannot be processed because '.$import->getStatusInfo(), $import);

            return false;
        }

        $lastImportedLine = $import->getLastLineImported();
        $headers          = $import->getHeaders();
        $headerCount      = count($headers);
        $config           = $import->getParserConfig();
        $counter          = 0;

        $file->seek($lastImportedLine);

        $lineNumber = $lastImportedLine + 1;
        $this->logDebug('The import is starting on line '.$lineNumber, $import);

        $batchSize = $config['batchlimit'];

        // Convert to field names
        array_walk($headers, function (&$val): void {
            $val = strtolower(InputHelper::alphanum($val, false, '_'));
        });

        while ($batchSize && !$file->eof()) {
            $string = $file->current();
            $file->next();
            $data = str_getcsv($string, $config['delimiter'], $config['enclosure'], $config['escape']);
            $import->setLastLineImported($lineNumber);

            // Ignore the header row
            if (1 === $lineNumber) {
                ++$lineNumber;
                continue;
            }

            // Ensure the progress is changing
            ++$lineNumber;
            --$batchSize;
            $progress->increase();

            $errorMessage = null;
            $eventLog     = $this->initEventLog($import, $lineNumber);

            if ($this->isEmptyCsvRow($data)) {
                $errorMessage = 'mautic.lead.import.error.line_empty';
            }

            if ($this->hasMoreValuesThanColumns($data, $headerCount)) {
                $errorMessage = 'mautic.lead.import.error.header_mismatch';
            }

            if (!$errorMessage) {
                $data = $this->trimArrayValues($data);
                if (!array_filter($data)) {
                    continue;
                }

                $data = array_combine($headers, $data);

                try {
                    $event = new ImportProcessEvent($import, $eventLog, $data);

                    $this->dispatcher->dispatch($event, LeadEvents::IMPORT_ON_PROCESS);

                    if ($event->wasMerged()) {
                        $this->logDebug('Entity on line '.$lineNumber.' has been updated', $import);
                        $import->increaseUpdatedCount();
                    } else {
                        $this->logDebug('Entity on line '.$lineNumber.' has been created', $import);
                        $import->increaseInsertedCount();
                    }
                } catch (\Exception $e) {
                    // Email validation likely failed
                    $errorMessage = $e->getMessage();
                }
            }

            if ($errorMessage) {
                // Log the error first
                $import->increaseIgnoredCount();
                $this->logDebug('Line '.$lineNumber.' error: '.$errorMessage, $import);
                if (!$this->em->isOpen()) {
                    // Something bad must have happened if the entity manager is closed.
                    // We will not be able to save any entities.
                    throw new ORMException($errorMessage);
                }
                // This should be called only if the entity manager is open
                $this->logImportRowError($eventLog, $errorMessage);
            } else {
                $this->leadEventLogRepo->saveEntity($eventLog);
            }

            // Release entities in Doctrine's memory to prevent memory leak
            $this->em->detach($eventLog);
            if (null !== $leadEntity = $eventLog->getLead()) {
                $this->em->detach($leadEntity);

                $company        = $leadEntity->getCompany();
                $primaryCompany = $leadEntity->getPrimaryCompany();
                if ($company instanceof Company) {
                    $this->em->detach($company);
                }
                if ($primaryCompany instanceof Company) {
                    $this->em->detach($primaryCompany);
                }
            }
            $eventLog = null;
            $data     = null;

            // Save Import entity once per batch so the user could see the progress
            if (0 === $batchSize && $import->isBackgroundProcess()) {
                $isPublished = $this->getRepository()->getValue($import->getId(), 'is_published');

                if (!$isPublished) {
                    $import->setStatus($import::STOPPED);
                }

                $this->saveEntity($import);
                $this->dispatchEvent('batch_processed', $import);

                // Stop the import loop if the import got unpublished
                if (!$isPublished) {
                    $this->logDebug('The import has been unpublished. Stopping the import now.', $import);
                    break;
                }

                $batchSize = $config['batchlimit'];
            }

            if ($this->processSignalService->isSignalCaught()) {
                break;
            }

            ++$counter;
            if ($limit && $counter >= $limit) {
                break;
            }
        }

        if ($import->getLastLineImported() < $import->getLineCount()) {
            $import->setStatus($import::DELAYED);
            $this->saveEntity($import);
        }

        // Close the file
        $file = null;

        return true;
    }

    /**
     * Check if the CSV row has more values than the CSV header has columns.
     * If it is less, generate empty values for the rest of the missing values.
     * If it is more, return true.
     *
     * @param int $headerCount
     */
    public function hasMoreValuesThanColumns(array &$data, $headerCount): bool
    {
        $dataCount = count($data);

        if ($headerCount !== $dataCount) {
            $diffCount = ($headerCount - $dataCount);

            if ($diffCount > 0) {
                // Fill in the data with empty string
                $fill = array_fill($dataCount, $diffCount, '');
                $data = $data + $fill;
            } else {
                return true;
            }
        }

        return false;
    }

    /**
     * Trim all values in a one dymensional array.
     */
    public function trimArrayValues(array $data): array
    {
        return array_map('trim', $data);
    }

    /**
     * Decide whether the CSV row is empty.
     *
     * @param mixed $row
     */
    public function isEmptyCsvRow($row): bool
    {
        if (!is_array($row) || empty($row)) {
            return true;
        }

        if (1 === count($row) && ('' === $row[0] || null === $row[0])) {
            return true;
        }

        return !array_filter($row);
    }

    /**
     * Save log about errored line.
     *
     * @param string $errorMessage
     */
    public function logImportRowError(LeadEventLog $eventLog, $errorMessage): void
    {
        $eventLog->addProperty('error', $this->translator->trans($errorMessage))
            ->setAction('failed');

        $this->leadEventLogRepo->saveEntity($eventLog);
    }

    /**
     * Initialize LeadEventLog object and configure it as the import event.
     *
     * @param int $lineNumber
     */
    public function initEventLog(Import $import, $lineNumber): LeadEventLog
    {
        $eventLog = new LeadEventLog();
        $eventLog->setUserId($import->getModifiedBy())
            ->setUserName($import->getModifiedByUser())
            ->setBundle($import->getObject())
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
     * Get line chart data of imported rows.
     *
     * @param string $unit       {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}
     * @param string $dateFormat
     * @param array  $filter
     */
    public function getImportedRowsLineChartData($unit, \DateTimeInterface $dateFrom, \DateTimeInterface $dateTo, $dateFormat = null, $filter = []): array
    {
        $filter['object'] = 'import';
        $filter['bundle'] = 'lead';

        // Clear the times for display by minutes
        /** @var \DateTime $dateFrom */
        /** @var \DateTime $dateTo */
        $dateFrom->modify('-1 minute');
        $dateFrom->setTime($dateFrom->format('H'), $dateFrom->format('i'), 0);
        $dateTo->modify('+1 minute');
        $dateTo->setTime($dateTo->format('H'), $dateTo->format('i'), 0);

        $query = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo, $unit);
        $chart = new LineChart($unit, $dateFrom, $dateTo, $dateFormat);
        $data  = $query->fetchTimeData('lead_event_log', 'date_added', $filter);

        $chart->setDataset($this->translator->trans('mautic.lead.import.processed.rows'), $data);

        return $chart->render();
    }

    /**
     * Returns a list of failed rows for the import.
     *
     * @param int    $importId
     * @param string $object
     *
     * @return array|null
     */
    public function getFailedRows($importId = null, $object = 'lead')
    {
        if (!$importId) {
            return null;
        }

        return $this->getEventLogRepository()->getFailedRows($importId, ['select' => 'properties,id'], $object);
    }

    /**
     * @return ImportRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository(Import::class);
    }

    /**
     * @return LeadEventLogRepository
     */
    public function getEventLogRepository()
    {
        return $this->em->getRepository(LeadEventLog::class);
    }

    public function getPermissionBase(): string
    {
        return 'lead:imports';
    }

    /**
     * Returns a unique name of a CSV file based on time.
     */
    public function getUniqueFileName(): string
    {
        return (new DateTimeHelper())->toUtcString('YmdHis').'.csv';
    }

    /**
     * Returns a full path to the import dir.
     */
    public function getImportDir(): string
    {
        return $this->pathsHelper->getImportLeadsPath();
    }

    /**
     * Get a specific entity or generate a new one if id is empty.
     */
    public function getEntity($id = null): ?Import
    {
        if (null === $id) {
            return new Import();
        }

        return parent::getEntity($id);
    }

    /**
     * @throws MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null): ?Event
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
            case 'batch_processed':
                $name = LeadEvents::IMPORT_BATCH_PROCESSED;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new ImportEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($event, $name);

            return $event;
        } else {
            return null;
        }
    }

    /**
     * Logs a debug message if in dev environment.
     *
     * @param string $msg
     */
    protected function logDebug($msg, Import $import = null)
    {
        if (MAUTIC_ENV === 'dev') {
            $importId = $import ? '('.$import->getId().')' : '';
            $this->logger->debug(sprintf('IMPORT%s: %s', $importId, $msg));
        }
    }
}
