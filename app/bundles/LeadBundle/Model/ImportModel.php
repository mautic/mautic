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

use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\LeadBundle\Entity\Import;
use Mautic\LeadBundle\Event\ImportEvent;
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
     * ImportModel constructor.
     *
     * @param PathsHelper $pathsHelper
     * @param LeadModel   $leadModel
     */
    public function __construct(
        PathsHelper $pathsHelper,
        LeadModel $leadModel
    ) {
        $this->pathsHelper = $pathsHelper;
        $this->leadModel   = $leadModel;
    }

    /**
     * Returns the Import entity which should be processed next.
     *
     * @return Import|null
     */
    public function getImportToProcess()
    {
        return $this->getRepository()->findOneBy(
            [
                'status'      => Import::CREATED,
                'isPublished' => 1,
            ],
            [
                'priority'  => 'ASC',
                'dateAdded' => 'DESC',
            ]
        );
    }

    /**
     * Import next pre-saved import.
     *
     * @return bool|null
     */
    public function processNext(Progress $progress)
    {
        $import = $this->getImportToProcess();

        if (!$import) {
            return;
        }

        if (!$import->canProceed()) {
            $this->saveEntity($import);

            return $import;
        }

        $progress->setTotal($import->getLineCount());

        $import->start();
        $this->saveEntity($import);

        $this->process($import, $progress);

        $import->end();
        $this->saveEntity($import);

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
        $config = $import->getParserConfig();
        $file   = new \SplFileObject($import->getFilePath());
        if ($file !== false) {
            $lineNumber = $progress->getDone();

            if ($lineNumber > 0) {
                $file->seek($lineNumber);
            }

            $batchSize = $config['batchlimit'];

            while ($batchSize && !$file->eof()) {
                $data = $file->fgetcsv($config['delimiter'], $config['enclosure'], $config['escape']);
                array_walk($data, create_function('&$val', '$val = trim($val);'));

                // Ignore the header row
                if ($lineNumber === 0) {
                    ++$lineNumber;
                    continue;
                }

                ++$lineNumber;

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
                            $msg = $this->get('translator')->trans('mautic.lead.import.error.header_mismatch');
                            $import->addFailure($lineNumber, $msg);

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
                                $import->getDefault('tags')
                            );
                            if ($merged) {
                                $import->increaseUpdatedCount();
                            } else {
                                $import->increaseInsertedCount();
                            }
                        } else {
                            $import->increaseIgnoredCount();
                            $msg = $this->get('translator')->trans('mautic.lead.import.error.line_empty');
                            $import->addFailure($lineNumber, $msg);
                        }
                    } catch (\Exception $e) {
                        // Email validation likely failed
                        $import->increaseIgnoredCount();
                        $import->addFailure($lineNumber, $e->getMessage());
                    }
                } else {
                    $import->increaseIgnoredCount();
                    $msg = $this->get('translator')->trans('mautic.lead.import.error.line_empty');
                    $import->addFailure($lineNumber, $msg);
                }

                if ($batchSize === 0 && $import->isBackgroundProcess()) {
                    $this->saveEntity($import);
                    $batchSize = $config['batchlimit'];
                }
            }
        }

        // Close the file
        $file = null;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticLeadBundle:Import');
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
     * Returns a full path to a dir with unique name based on time.
     *
     * @return string
     */
    public function getUniqueDir()
    {
        $tmpDir = $this->pathsHelper->getSystemPath('tmp', true);

        return $tmpDir.'/imports/'.(new DateTimeHelper())->toUtcString('YmdHis');
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
