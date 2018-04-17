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

use Doctrine\ORM\ORMException;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Import;
use Mautic\LeadBundle\Entity\ImportRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Entity\LeadEventLogRepository;
use Mautic\LeadBundle\Event\ImportEvent;
use Mautic\LeadBundle\Exception\ImportDelayedException;
use Mautic\LeadBundle\Exception\ImportFailedException;
use Mautic\LeadBundle\Helper\Progress;
use Mautic\LeadBundle\LeadEvents;
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
     * @var CompanyModel
     */
    protected $companyModel;

    /**
     * @var NotificationModel
     */
    protected $notificationModel;

    /**
     * @var CoreParametersHelper
     */
    protected $config;

    /**
     * @var LeadEventLogRepository
     */
    protected $leadEventLogRepo;

    /**
     * ImportModel constructor.
     *
     * @param PathsHelper          $pathsHelper
     * @param LeadModel            $leadModel
     * @param NotificationModel    $notificationModel
     * @param CoreParametersHelper $config
     * @param CompanyModel         $companyModel
     */
    public function __construct(
        PathsHelper $pathsHelper,
        LeadModel $leadModel,
        NotificationModel $notificationModel,
        CoreParametersHelper $config,
        CompanyModel $companyModel
    ) {
        $this->pathsHelper       = $pathsHelper;
        $this->leadModel         = $leadModel;
        $this->notificationModel = $notificationModel;
        $this->config            = $config;
        $this->leadEventLogRepo  = $leadModel->getEventLogRepository();
        $this->companyModel      = $companyModel;
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
     *
     * @return bool
     */
    public function checkParallelImportLimit()
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
        return $this->config->getParameter('parallel_import_limit', $default);
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

            if ($import->getCreatedBy()) {
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
        }

        $this->saveEntities($imports);
    }

    /**
     * Start import. This is meant for the CLI command since it will import
     * the whole file at once.
     *
     * @deprecated in 2.13.0. To be removed in 3.0.0. Use beginImport instead
     *
     * @param Import   $import
     * @param Progress $progress
     * @param int      $limit    Number of records to import before delaying the import. 0 will import all
     *
     * @return bool
     */
    public function startImport(Import $import, Progress $progress, $limit = 0)
    {
        try {
            return $this->beginImport($import, $progress, $limit);
        } catch (\Exception $e) {
            $this->logDebug($e->getMessage());

            return false;
        }
    }

    /**
     * Start import. This is meant for the CLI command since it will import
     * the whole file at once.
     *
     * @param Import   $import
     * @param Progress $progress
     * @param int      $limit    Number of records to import before delaying the import. 0 will import all
     *
     * @throws ImportFailedException
     * @throws ImportDelayedException
     */
    public function beginImport(Import $import, Progress $progress, $limit = 0)
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
                'fa-download',
                null,
                $this->em->getReference('MauticUserBundle:User', $import->getCreatedBy())
            );
        }
    }

    /**
     * Import the CSV file from configuration in the $import entity.
     *
     * @param Import   $import
     * @param Progress $progress
     * @param int      $limit    Number of records to import before delaying the import
     *
     * @return bool
     */
    public function process(Import $import, Progress $progress, $limit = 0)
    {
        //Auto detect line endings for the file to work around MS DOS vs Unix new line characters
        ini_set('auto_detect_line_endings', true);

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

        if ($lastImportedLine > 0) {
            // Seek is zero-based line numbering and
            $file->seek($lastImportedLine - 1);
        }

        $lineNumber = $lastImportedLine + 1;
        $this->logDebug('The import is starting on line '.$lineNumber, $import);

        $batchSize = $config['batchlimit'];

        // Convert to field names
        array_walk($headers, function (&$val) {
            $val = strtolower(InputHelper::alphanum($val, false, '_'));
        });

        while ($batchSize && !$file->eof()) {
            $data = $file->fgetcsv($config['delimiter'], $config['enclosure'], $config['escape']);
            $import->setLastLineImported($lineNumber);

            // Ignore the header row
            if ($lineNumber === 1) {
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
                    $entityModel = $import->getObject() === 'company' ? $this->companyModel : $this->leadModel;

                    $merged = $entityModel->import(
                        $import->getMatchedFields(),
                        $data,
                        $import->getDefault('owner'),
                        $import->getDefault('list'),
                        $import->getDefault('tags'),
                        true,
                        $eventLog,
                        $import->getId()
                    );

                    if ($merged) {
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
                $import->increaseIgnoredCount();
                $this->logImportRowError($eventLog, $errorMessage);
                $this->logDebug('Line '.$lineNumber.' error: '.$errorMessage, $import);
            } else {
                $this->leadEventLogRepo->saveEntity($eventLog);
            }

            // Release entities in Doctrine's memory to prevent memory leak
            $this->em->detach($eventLog);
            $eventLog = null;
            $data     = null;
            $this->em->clear(Lead::class);
            $this->em->clear(Company::class);

            // Save Import entity once per batch so the user could see the progress
            if ($batchSize === 0 && $import->isBackgroundProcess()) {
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

            ++$counter;
            if ($limit && $counter >= $limit) {
                $import->setStatus($import::DELAYED);
                $this->saveEntity($import);
                break;
            }
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
     * @param array &$data
     * @param int   $headerCount
     *
     * @return bool
     */
    public function hasMoreValuesThanColumns(array &$data, $headerCount)
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
     *
     * @param array $data
     *
     * @return array
     */
    public function trimArrayValues(array $data)
    {
        return array_map('trim', $data);
    }

    /**
     * Decide whether the CSV row is empty.
     *
     * @param mixed $row
     *
     * @return bool
     */
    public function isEmptyCsvRow($row)
    {
        if (!is_array($row) || empty($row)) {
            return true;
        }

        if (count($row) === 1 && ($row[0] === '' || $row[0] === null)) {
            return true;
        }

        return false;
    }

    /**
     * Save log about errored line.
     *
     * @param LeadEventLog $eventLog
     * @param string       $errorMessage
     */
    public function logImportRowError(LeadEventLog $eventLog, $errorMessage)
    {
        $eventLog->addProperty('error', $this->translator->trans($errorMessage))
            ->setAction('failed');

        $this->leadEventLogRepo->saveEntity($eventLog);
    }

    /**
     * Initialize LeadEventLog object and configure it as the import event.
     *
     * @param Import $import
     * @param int    $lineNumber
     *
     * @return LeadEventLog
     */
    public function initEventLog(Import $import, $lineNumber)
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
     * Get line chart data of imported rows.
     *
     * @param string    $unit       {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param string    $dateFormat
     * @param array     $filter
     *
     * @return array
     */
    public function getImportedRowsLineChartData($unit, \DateTime $dateFrom, \DateTime $dateTo, $dateFormat = null, $filter = [])
    {
        $filter['object'] = 'import';
        $filter['bundle'] = 'lead';

        // Clear the times for display by minutes
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
     * @param int $importId
     *
     * @return array|null
     */
    public function getFailedRows($importId = null)
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

            $this->dispatcher->dispatch($name, $event);

            return $event;
        } else {
            return null;
        }
    }

    /**
     * Logs a debug message if in dev environment.
     *
     * @param string $msg
     * @param Import $import
     */
    protected function logDebug($msg, Import $import = null)
    {
        if (MAUTIC_ENV === 'dev') {
            $importId = $import ? '('.$import->getId().')' : '';
            $this->logger->debug(sprintf('IMPORT%s: %s', $importId, $msg));
        }
    }
}
