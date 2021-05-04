<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Helper\CsvHelper;
use Mautic\LeadBundle\Entity\Import;
use Mautic\LeadBundle\Form\Type\LeadImportFieldType;
use Mautic\LeadBundle\Form\Type\LeadImportType;
use Mautic\LeadBundle\Helper\Progress;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Exception\LogicException;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ImportController extends FormController
{
    // Steps of the import
    const STEP_UPLOAD_CSV      = 1;
    const STEP_MATCH_FIELDS    = 2;
    const STEP_PROGRESS_BAR    = 3;
    const STEP_IMPORT_FROM_CSV = 4;

    /**
     * @param int $page
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function indexAction($page = 1)
    {
        $this->get('session')->set('mautic.import.object', $this->getObjectFromRequest());

        return $this->indexStandard($page);
    }

    /**
     * Get items for index list.
     *
     * @param $start
     * @param $limit
     * @param $filter
     * @param $orderBy
     * @param $orderByDir
     * @param $args
     *
     * @return array
     */
    protected function getIndexItems($start, $limit, $filter, $orderBy, $orderByDir, array $args = [])
    {
        $object = $this->get('session')->get('mautic.import.object');
        $model  = $this->getModel($this->getModelName());

        $filter['force'][] = [
            'column' => $model->getRepository()->getTableAlias().'.object',
            'expr'   => 'eq',
            'value'  => $object,
        ];

        $items = $model->getEntities(
            array_merge(
                [
                    'start'      => $start,
                    'limit'      => $limit,
                    'filter'     => $filter,
                    'orderBy'    => $orderBy,
                    'orderByDir' => $orderByDir,
                ],
                $args
            )
        );

        $count = count($items);

        return [$count, $items];
    }

    /**
     * @param $objectId
     *
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function viewAction($objectId)
    {
        return $this->viewStandard($objectId, 'import', 'lead');
    }

    /**
     * Cancel and unpublish the import during manual import.
     *
     * @param $objectId
     *
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function cancelAction($objectId)
    {
        $session     = $this->get('session');
        $fullPath    = $this->getFullCsvPath();
        $importModel = $this->getModel($this->getModelName());
        $import      = $importModel->getEntity($session->get('mautic.lead.import.id', null));

        if ($import && $import->getId()) {
            $import->setStatus($import::STOPPED)
                ->setIsPublished(false);
            $importModel->saveEntity($import);
        }

        $this->resetImport($fullPath);

        return $this->indexAction();
    }

    /**
     * Schedules manual import to background queue.
     *
     * @param $objectId
     *
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function queueAction($objectId)
    {
        $session     = $this->get('session');
        $fullPath    = $this->getFullCsvPath();
        $importModel = $this->getModel($this->getModelName());
        $import      = $importModel->getEntity($session->get('mautic.lead.import.id', null));

        if ($import) {
            $import->setStatus($import::QUEUED);
            $importModel->saveEntity($import);
        }

        $this->resetImport($fullPath, false);

        return $this->indexAction();
    }

    /**
     * @param int  $objectId
     * @param bool $ignorePost
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction($objectId = 0, $ignorePost = false)
    {
        //Auto detect line endings for the file to work around MS DOS vs Unix new line characters
        ini_set('auto_detect_line_endings', true);

        $object = $this->getObjectFromRequest();

        $this->get('session')->set('mautic.import.object', $object);

        /** @var \Mautic\LeadBundle\Model\ImportModel $importModel */
        $importModel = $this->getModel($this->getModelName());

        $session = $this->get('session');

        if (!$this->get('mautic.security')->isGranted('lead:imports:create')) {
            return $this->accessDenied();
        }

        // Move the file to cache and rename it
        $forceStop = $this->request->get('cancel', false);
        $step      = ($forceStop) ? 1 : $session->get('mautic.'.$object.'.import.step', self::STEP_UPLOAD_CSV);
        $fileName  = $this->getImportFileName();
        $importDir = $this->getImportDirName();
        $fullPath  = $this->getFullCsvPath();
        $fs        = new Filesystem();
        $complete  = false;

        if (!file_exists($fullPath)) {
            // Force step one if the file doesn't exist
            $step = 1;
            $session->set('mautic.'.$object.'.import.step', self::STEP_UPLOAD_CSV);
        }

        $progress = (new Progress())->bindArray($session->get('mautic.'.$object.'.import.progress', [0, 0]));
        $import   = $importModel->getEntity();
        $action   = $this->generateUrl('mautic_import_action', ['object' => $this->request->get('object'), 'objectAction' => 'new']);

        switch ($step) {
            case self::STEP_UPLOAD_CSV:

                if ($forceStop) {
                    $this->resetImport($fullPath);
                }

                $form = $this->get('form.factory')->create(LeadImportType::class, [], ['action' => $action]);
                break;
            case self::STEP_MATCH_FIELDS:

                /** @var \Mautic\LeadBundle\Model\FieldModel $fieldModel */
                $fieldModel    = $this->getModel('lead.field');
                $leadFields    = $fieldModel->getFieldList(false, false);
                $importFields  = $session->get('mautic.'.$object.'.import.importfields', []);
                $companyFields = $fieldModel->getFieldList(false, false, ['isPublished' => true, 'object' => 'company']);

                try {
                    $form = $this->get('form.factory')->create(
                        LeadImportFieldType::class,
                        [],
                        [
                            'object'           => $object,
                            'action'           => $action,
                            'lead_fields'      => $leadFields,
                            'company_fields'   => $companyFields,
                            'import_fields'    => $importFields,
                            'line_count_limit' => $this->getLineCountLimit(),
                        ]
                    );
                } catch (LogicException $e) {
                    $this->resetImport($fullPath);

                    return $this->newAction(0, true);
                }

                break;
            case self::STEP_PROGRESS_BAR:
                // Just show the progress form
                $session->set('mautic.'.$object.'.import.step', self::STEP_IMPORT_FROM_CSV);
                break;

            case self::STEP_IMPORT_FROM_CSV:
                ignore_user_abort(true);

                $inProgress = $session->get('mautic.'.$object.'.import.inprogress', false);
                $checks     = $session->get('mautic.'.$object.'.import.progresschecks', 1);
                if (!$inProgress || $checks > 5) {
                    $session->set('mautic.'.$object.'.import.inprogress', true);
                    $session->set('mautic.'.$object.'.import.progresschecks', 1);

                    $import = $importModel->getEntity($session->get('mautic.'.$object.'.import.id', null));

                    if (!$import->getDateStarted()) {
                        $import->setDateStarted(new \DateTime());
                    }

                    $importModel->process($import, $progress);

                    // Clear in progress
                    if ($progress->isFinished()) {
                        $import->setStatus($import::IMPORTED)
                            ->setDateEnded(new \DateTime());
                        $this->resetImport($fullPath);
                        $complete = true;
                    } else {
                        $complete = false;
                        $session->set('mautic.'.$object.'.import.inprogress', false);
                        $session->set('mautic.'.$object.'.import.progress', $progress->toArray());
                    }

                    $importModel->saveEntity($import);

                    break;
                } else {
                    ++$checks;
                    $session->set('mautic.'.$object.'.import.progresschecks', $checks);
                }
        }

        ///Check for a submitted form and process it
        if (!$ignorePost && 'POST' == $this->request->getMethod()) {
            if (isset($form) && !$this->isFormCancelled($form)) {
                $valid = $this->isFormValid($form);
                switch ($step) {
                    case self::STEP_UPLOAD_CSV:
                        if ($valid) {
                            if (file_exists($fullPath)) {
                                unlink($fullPath);
                            }

                            $fileData = $form['file']->getData();
                            if (!empty($fileData)) {
                                $errorMessage    = null;
                                $errorParameters = [];
                                try {
                                    // Create the import dir recursively
                                    $fs->mkdir($importDir);

                                    $fileData->move($importDir, $fileName);

                                    $file = new \SplFileObject($fullPath);

                                    $config = $form->getData();
                                    unset($config['file']);
                                    unset($config['start']);

                                    foreach ($config as $key => &$c) {
                                        $c = htmlspecialchars_decode($c);

                                        if ('batchlimit' == $key) {
                                            $c = (int) $c;
                                        }
                                    }

                                    $session->set('mautic.'.$object.'.import.config', $config);

                                    if (false !== $file) {
                                        // Get the headers for matching
                                        $headers = $file->fgetcsv($config['delimiter'], $config['enclosure'], $config['escape']);

                                        // Get the number of lines so we can track progress
                                        $file->seek(PHP_INT_MAX);
                                        $linecount = $file->key();

                                        if (!empty($headers) && is_array($headers)) {
                                            $headers = CsvHelper::sanitizeHeaders($headers);

                                            $session->set('mautic.'.$object.'.import.headers', $headers);
                                            $session->set('mautic.'.$object.'.import.step', self::STEP_MATCH_FIELDS);
                                            $session->set('mautic.'.$object.'.import.importfields', CsvHelper::convertHeadersIntoFields($headers));
                                            $session->set('mautic.'.$object.'.import.progress', [0, $linecount]);
                                            $session->set('mautic.'.$object.'.import.original.file', $fileData->getClientOriginalName());

                                            return $this->newAction(0, true);
                                        }
                                    }
                                } catch (FileException $e) {
                                    if (false !== strpos($e->getMessage(), 'upload_max_filesize')) {
                                        $errorMessage    = 'mautic.lead.import.filetoolarge';
                                        $errorParameters = [
                                            '%upload_max_filesize%' => ini_get('upload_max_filesize'),
                                        ];
                                    } else {
                                        $errorMessage = 'mautic.lead.import.filenotreadable';
                                    }
                                } catch (\Exception $e) {
                                    $errorMessage = 'mautic.lead.import.filenotreadable';
                                } finally {
                                    if (!is_null($errorMessage)) {
                                        $form->addError(
                                            new FormError(
                                                $this->get('translator')->trans($errorMessage, $errorParameters, 'validators')
                                            )
                                        );
                                    }
                                }
                            }
                        }
                        break;
                    case self::STEP_MATCH_FIELDS:
                        // Save matched fields
                        $matchedFields = $form->getData();

                        if (empty($matchedFields)) {
                            $this->resetImport($fullPath);

                            return $this->newAction(0, true);
                        }

                        $owner = $matchedFields['owner'];
                        unset($matchedFields['owner']);

                        $list = null;
                        if (array_key_exists('list', $matchedFields)) {
                            $list = $matchedFields['list'];
                            unset($matchedFields['list']);
                        }

                        $tags = [];
                        if (array_key_exists('tags', $matchedFields)) {
                            $tagCollection = $matchedFields['tags'];
                            $tags          = [];
                            foreach ($tagCollection as $tag) {
                                $tags[] = $tag->getTag();
                            }
                            unset($matchedFields['tags']);
                        }

                        foreach ($matchedFields as $k => $f) {
                            if (empty($f)) {
                                unset($matchedFields[$k]);
                            } else {
                                $matchedFields[$k] = trim($matchedFields[$k]);
                            }
                        }

                        if (empty($matchedFields)) {
                            $form->addError(
                                new FormError(
                                    $this->get('translator')->trans('mautic.lead.import.matchfields', [], 'validators')
                                )
                            );
                        } else {
                            $defaultOwner = ($owner) ? $owner->getId() : null;

                            /** @var \Mautic\LeadBundle\Entity\Import $import */
                            $import = $importModel->getEntity();

                            $import->setMatchedFields($matchedFields)
                                ->setObject($object)
                                ->setDir($importDir)
                                ->setLineCount($this->getLineCount())
                                ->setFile($fileName)
                                ->setOriginalFile($session->get('mautic.'.$object.'.import.original.file'))
                                ->setDefault('owner', $defaultOwner)
                                ->setDefault('list', $list)
                                ->setDefault('tags', $tags)
                                ->setHeaders($session->get('mautic.'.$object.'.import.headers'))
                                ->setParserConfig($session->get('mautic.'.$object.'.import.config'));

                            // In case the user chose to import in browser
                            if ($this->importInBrowser($form)) {
                                $import->setStatus($import::MANUAL);

                                $session->set('mautic.'.$object.'.import.step', self::STEP_PROGRESS_BAR);
                            }

                            $importModel->saveEntity($import);

                            $session->set('mautic.'.$object.'.import.id', $import->getId());

                            // In case the user decided to queue the import
                            if ($this->importInCli($form)) {
                                $this->addFlash('mautic.'.$object.'.batch.import.created');
                                $this->resetImport($fullPath, false);

                                return $this->indexAction();
                            }

                            return $this->newAction(0, true);
                        }
                        break;

                    default:
                        // Done or something wrong

                        $this->resetImport($fullPath);

                        break;
                }
            } else {
                $this->resetImport($fullPath);

                return $this->newAction(0, true);
            }
        }

        if (self::STEP_UPLOAD_CSV === $step || self::STEP_MATCH_FIELDS === $step) {
            $contentTemplate = 'MauticLeadBundle:Import:new.html.php';
            $viewParameters  = ['form' => $form->createView()];
        } else {
            $contentTemplate = 'MauticLeadBundle:Import:progress.html.php';
            $viewParameters  = [
                'progress'   => $progress,
                'import'     => $import,
                'complete'   => $complete,
                'failedRows' => $importModel->getFailedRows($import->getId()),
            ];
        }

        if (!$complete && $this->request->query->has('importbatch')) {
            // Ajax request to batch process so just return ajax response unless complete

            return new JsonResponse(['success' => 1, 'ignore_wdt' => 1]);
        } else {
            $activeLink = 'lead' === $object ? '#mautic_contact_index' : '#mautic_company_index';

            return $this->delegateView(
                [
                    'viewParameters'  => $viewParameters,
                    'contentTemplate' => $contentTemplate,
                    'passthroughVars' => [
                        'activeLink'    => $activeLink,
                        'mauticContent' => 'leadImport',
                        'route'         => $this->generateUrl(
                            'mautic_import_action',
                            [
                                'object'       => 'lead' === $object ? 'contacts' : 'companies',
                                'objectAction' => 'new',
                            ]
                        ),
                        'step'     => $step,
                        'progress' => $progress,
                    ],
                ]
            );
        }
    }

    /**
     * Returns line count from the session.
     *
     * @return int
     */
    protected function getLineCount()
    {
        $object = $this->getObjectFromRequest();

        $progress = $this->get('session')->get('mautic.'.$object.'.import.progress', [0, 0]);

        return isset($progress[1]) ? $progress[1] : 0;
    }

    /**
     * Decide whether the import will be processed in client's browser.
     *
     * @return bool
     */
    protected function importInBrowser(Form $form)
    {
        $browserImportLimit = $this->getLineCountLimit();

        if ($browserImportLimit && $this->getLineCount() < $browserImportLimit) {
            return true;
        } elseif (!$browserImportLimit && $form->get('buttons')->get('save')->isClicked()) {
            return true;
        }

        return false;
    }

    protected function getLineCountLimit()
    {
        return $this->get('mautic.helper.core_parameters')->get('background_import_if_more_rows_than', 0);
    }

    /**
     * Decide whether the import will be queued to be processed by the CLI command in the background.
     *
     * @return bool
     */
    protected function importInCli(Form $form)
    {
        $browserImportLimit = $this->getLineCountLimit();

        if ($browserImportLimit && $this->getLineCount() >= $browserImportLimit) {
            return true;
        } elseif (!$browserImportLimit && $form->get('buttons')->get('apply')->isClicked()) {
            return true;
        }

        return false;
    }

    /**
     * Generates import directory path.
     *
     * @return string
     */
    protected function getImportDirName()
    {
        /** @var \Mautic\LeadBundle\Model\ImportModel $importModel */
        $importModel = $this->getModel('lead.import');

        return $importModel->getImportDir();
    }

    /**
     * Generates unique import directory name inside the cache dir if not stored in the session.
     * If it exists in the session, returns that one.
     *
     * @return string
     */
    protected function getImportFileName()
    {
        $session = $this->get('session');
        $object  = $this->getObjectFromRequest();

        // Return the dir path from session if exists
        if ($fileName = $session->get('mautic.'.$object.'.import.file')) {
            return $fileName;
        }

        /** @var \Mautic\LeadBundle\Model\ImportModel $importModel */
        $importModel = $this->getModel('lead.import');
        $fileName    = $importModel->getUniqueFileName();

        // Set the dir path to session
        $session->set('mautic.'.$object.'.import.file', $fileName);

        return $fileName;
    }

    /**
     * Return full absolute path to the CSV file.
     *
     * @return string
     */
    protected function getFullCsvPath()
    {
        return $this->getImportDirName().'/'.$this->getImportFileName();
    }

    /**
     * @param $filepath
     */
    private function resetImport($filepath, $removeCsv = true)
    {
        $object  = $this->getObjectFromRequest();
        $session = $this->get('session');
        $session->set('mautic.'.$object.'.import.headers', []);
        $session->set('mautic.'.$object.'.import.file', null);
        $session->set('mautic.'.$object.'.import.step', self::STEP_UPLOAD_CSV);
        $session->set('mautic.'.$object.'.import.progress', [0, 0]);
        $session->set('mautic.'.$object.'.import.inprogress', false);
        $session->set('mautic.'.$object.'.import.importfields', []);
        $session->set('mautic.'.$object.'.import.original.file', null);
        $session->set('mautic.'.$object.'.import.id', null);

        if ($removeCsv && file_exists($filepath) && is_readable($filepath)) {
            unlink($filepath);
        }
    }

    /**
     * @param $action
     *
     * @return array
     */
    public function getViewArguments(array $args, $action)
    {
        switch ($action) {
            case 'view':
                /** @var Import $entity */
                $entity = $args['entity'];

                /** @var \Mautic\LeadBundle\Model\ImportModel $model */
                $model = $this->getModel($this->getModelName());

                $args['viewParameters'] = array_merge(
                    $args['viewParameters'],
                    [
                        'failedRows'        => $model->getFailedRows($entity->getId()),
                        'importedRowsChart' => $entity->getDateStarted() ? $model->getImportedRowsLineChartData(
                            'i',
                            $entity->getDateStarted(),
                            $entity->getDateEnded() ? $entity->getDateEnded() : $entity->getDateModified(),
                            null,
                            [
                                'object_id' => $entity->getId(),
                            ]
                        ) : [],
                    ]
                );

                break;
        }

        return $args;
    }

    /**
     * Support non-index pages such as modal forms.
     *
     * @return bool|string
     */
    protected function generateUrl(string $route, array $parameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        if (!isset($parameters['object'])) {
            $parameters['object'] = $this->request->get('object', 'contacts');
        }

        return parent::generateUrl($route, $parameters, $referenceType);
    }

    protected function getObjectFromRequest()
    {
        $objectInRequest = $this->request->get('object');

        switch ($objectInRequest) {
            case 'companies':
                return 'company';
            case 'contacts':
            default:
                return 'lead';
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getModelName()
    {
        return 'lead.import';
    }

    /***
     * @param null $objectId
     *
     * @return string
     */
    protected function getSessionBase($objectId = null)
    {
        $object = $this->getObjectFromRequest();

        return $object.'.import'.(($objectId) ? '.'.$objectId : '');
    }

    /**
     * {@inheritdoc}
     */
    protected function getPermissionBase()
    {
        return $this->getModel($this->getModelName())->getPermissionBase();
    }

    /**
     * {@inheritdoc}
     */
    protected function getRouteBase()
    {
        return 'import';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getTemplateBase()
    {
        return 'MauticLeadBundle:Import';
    }

    /**
     * Provide the name of the column which is used for default ordering.
     *
     * @return string
     */
    protected function getDefaultOrderColumn()
    {
        return 'dateAdded';
    }

    /**
     * Provide the direction for default ordering.
     *
     * @return string
     */
    protected function getDefaultOrderDirection()
    {
        return 'DESC';
    }
}
