<?php

namespace Mautic\LeadBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Helper\CsvHelper;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\LeadBundle\Entity\Import;
use Mautic\LeadBundle\Event\ImportInitEvent;
use Mautic\LeadBundle\Event\ImportMappingEvent;
use Mautic\LeadBundle\Event\ImportValidateEvent;
use Mautic\LeadBundle\Form\Type\LeadImportFieldType;
use Mautic\LeadBundle\Form\Type\LeadImportType;
use Mautic\LeadBundle\Helper\Progress;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\ImportModel;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Exception\LogicException;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ImportController extends FormController
{
    // Steps of the import
    const STEP_UPLOAD_CSV      = 1;
    const STEP_MATCH_FIELDS    = 2;
    const STEP_PROGRESS_BAR    = 3;
    const STEP_IMPORT_FROM_CSV = 4;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var ImportModel
     */
    private $importModel;

    public function initialize(FilterControllerEvent $event)
    {
        /** @var ImportModel $model */
        $model = $this->getModel($this->getModelName());

        $this->logger      = $this->container->get('monolog.logger.mautic');
        $this->session     = $this->container->get('session');
        $this->importModel = $model;

        parent::initialize($event);
    }

    /**
     * @param int $page
     *
     * @return JsonResponse|RedirectResponse
     */
    public function indexAction($page = 1)
    {
        $initEvent = $this->dispatchImportOnInit();
        $this->session->set('mautic.import.object', $initEvent->objectSingular);

        return $this->indexStandard($page);
    }

    /**
     * Get items for index list.
     *
     * @param int     $start
     * @param int     $limit
     * @param mixed[] $filter
     * @param string  $orderBy
     * @param string  $orderByDir
     * @param mixed[] $args
     *
     * @return array
     */
    protected function getIndexItems($start, $limit, $filter, $orderBy, $orderByDir, array $args = [])
    {
        $object = $this->session->get('mautic.import.object');

        $filter['force'][] = [
            'column' => $this->importModel->getRepository()->getTableAlias().'.object',
            'expr'   => 'eq',
            'value'  => $object,
        ];

        $items = $this->importModel->getEntities(
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
     * @param int $objectId
     *
     * @return array|JsonResponse|RedirectResponse|Response
     */
    public function viewAction($objectId)
    {
        return $this->viewStandard($objectId, 'import', 'lead');
    }

    /**
     * Cancel and unpublish the import during manual import.
     *
     * @return array|JsonResponse|RedirectResponse|Response
     */
    public function cancelAction()
    {
        $initEvent   = $this->dispatchImportOnInit();
        $object      = $initEvent->objectSingular;
        $fullPath    = $this->getFullCsvPath($object);
        $import      = $this->importModel->getEntity($this->session->get('mautic.lead.import.id', null));

        if ($import && $import->getId()) {
            $import->setStatus($import::STOPPED)
                ->setIsPublished(false);
            $this->importModel->saveEntity($import);
        }

        $this->resetImport($object);
        $this->removeImportFile($fullPath);
        $this->logger->log(LogLevel::INFO, "Import for file {$fullPath} was canceled.");

        return $this->indexAction();
    }

    /**
     * Schedules manual import to background queue.
     *
     * @return array|JsonResponse|RedirectResponse|Response
     */
    public function queueAction()
    {
        $initEvent   = $this->dispatchImportOnInit();
        $object      = $initEvent->objectSingular;
        $fullPath    = $this->getFullCsvPath($object);
        $import      = $this->importModel->getEntity($this->session->get('mautic.lead.import.id', null));

        if ($import) {
            $import->setStatus($import::QUEUED);
            $this->importModel->saveEntity($import);
        }

        $this->resetImport($object);
        $this->logger->log(LogLevel::INFO, "Import for file {$fullPath} moved to be processed in the background.");

        return $this->indexAction();
    }

    /**
     * @param int  $objectId
     * @param bool $ignorePost
     *
     * @return JsonResponse|Response
     */
    public function newAction($objectId = 0, $ignorePost = false)
    {
        //Auto detect line endings for the file to work around MS DOS vs Unix new line characters
        ini_set('auto_detect_line_endings', '1');

        $dispatcher = $this->container->get('event_dispatcher');

        try {
            $initEvent = $this->dispatchImportOnInit();
        } catch (AccessDeniedException $e) {
            return $this->accessDenied();
        }

        if (!$initEvent->objectSupported) {
            return $this->notFound();
        }

        $object = $initEvent->objectSingular;

        $this->session->set('mautic.import.object', $object);

        // Move the file to cache and rename it
        $forceStop = $this->request->get('cancel', false);
        $step      = ($forceStop) ? self::STEP_UPLOAD_CSV : $this->session->get('mautic.'.$object.'.import.step', self::STEP_UPLOAD_CSV);
        $fileName  = $this->getImportFileName($object);
        $importDir = $this->getImportDirName();
        $fullPath  = $this->getFullCsvPath($object);
        $fs        = new Filesystem();
        $complete  = false;

        if (!file_exists($fullPath) && self::STEP_UPLOAD_CSV !== $step) {
            // Force step one if the file doesn't exist
            $this->logger->log(LogLevel::WARNING, "File {$fullPath} does not exist anymore. Reseting import to step STEP_UPLOAD_CSV.");
            $this->addFlash('mautic.import.file.missing', ['%file%' => $this->getImportFileName($object)], FlashBag::LEVEL_ERROR);
            $step = self::STEP_UPLOAD_CSV;
            $this->session->set('mautic.'.$object.'.import.step', self::STEP_UPLOAD_CSV);
        }

        $progress = (new Progress())->bindArray($this->session->get('mautic.'.$object.'.import.progress', [0, 0]));
        $import   = $this->importModel->getEntity();
        $action   = $this->generateUrl('mautic_import_action', ['object' => $this->request->get('object'), 'objectAction' => 'new']);

        switch ($step) {
            case self::STEP_UPLOAD_CSV:
                if ($forceStop) {
                    $this->resetImport($object);
                    $this->removeImportFile($fullPath);
                    $this->logger->log(LogLevel::WARNING, "Import for file {$fullPath} was force-stopped.");
                }

                $form = $this->get('form.factory')->create(LeadImportType::class, [], ['action' => $action]);
                break;
            case self::STEP_MATCH_FIELDS:
                $mappingEvent = $dispatcher->dispatch(
                    LeadEvents::IMPORT_ON_FIELD_MAPPING,
                    new ImportMappingEvent($this->request->get('object'))
                );

                try {
                    $form = $this->get('form.factory')->create(
                        LeadImportFieldType::class,
                        [],
                        [
                            'object'           => $object,
                            'action'           => $action,
                            'all_fields'       => $mappingEvent->fields,
                            'import_fields'    => $this->session->get('mautic.'.$object.'.import.importfields', []),
                            'line_count_limit' => $this->getLineCountLimit(),
                        ]
                    );
                } catch (LogicException $e) {
                    $this->resetImport($object);
                    $this->removeImportFile($fullPath);
                    $this->logger->log(LogLevel::INFO, "Import for file {$fullPath} failed with: {$e->getMessage()}.");

                    return $this->newAction(0, true);
                }

                break;
            case self::STEP_PROGRESS_BAR:
                // Just show the progress form
                $this->session->set('mautic.'.$object.'.import.step', self::STEP_IMPORT_FROM_CSV);
                break;

            case self::STEP_IMPORT_FROM_CSV:
                ignore_user_abort(true);

                $inProgress = $this->session->get('mautic.'.$object.'.import.inprogress', false);
                $checks     = $this->session->get('mautic.'.$object.'.import.progresschecks', 1);
                if (!$inProgress || $checks > 5) {
                    $this->session->set('mautic.'.$object.'.import.inprogress', true);
                    $this->session->set('mautic.'.$object.'.import.progresschecks', 1);

                    $import = $this->importModel->getEntity($this->session->get('mautic.'.$object.'.import.id', null));

                    if (!$import->getDateStarted()) {
                        $import->setDateStarted(new \DateTime());
                    }

                    $this->importModel->process($import, $progress);

                    // Clear in progress
                    if ($progress->isFinished()) {
                        $import->setStatus($import::IMPORTED)
                            ->setDateEnded(new \DateTime());
                        $this->resetImport($object);
                        $this->removeImportFile($fullPath);
                        $complete = true;
                    } else {
                        $complete = false;
                        $this->session->set('mautic.'.$object.'.import.inprogress', false);
                        $this->session->set('mautic.'.$object.'.import.progress', $progress->toArray());
                    }

                    $this->importModel->saveEntity($import);

                    break;
                } else {
                    ++$checks;
                    $this->session->set('mautic.'.$object.'.import.progresschecks', $checks);
                }
        }

        ///Check for a submitted form and process it
        if (!$ignorePost && 'POST' == $this->request->getMethod()) {
            if (!isset($form) || $this->isFormCancelled($form)) {
                $this->resetImport($object);
                $this->removeImportFile($fullPath);
                $reason = isset($form) ? 'the form is empty' : 'the form was canceled';
                $this->logger->log(LogLevel::WARNING, "Import for file {$fullPath} was aborted because {$reason}.");

                return $this->newAction(0, true);
            }

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

                                $this->session->set('mautic.'.$object.'.import.config', $config);

                                if (false !== $file) {
                                    // Get the headers for matching
                                    $headers = $file->fgetcsv($config['delimiter'], $config['enclosure'], $config['escape']);

                                    // Get the number of lines so we can track progress
                                    $file->seek(PHP_INT_MAX);
                                    $linecount = $file->key();

                                    if (!empty($headers) && is_array($headers)) {
                                        $headers = CsvHelper::sanitizeHeaders($headers);

                                        $this->session->set('mautic.'.$object.'.import.headers', $headers);
                                        $this->session->set('mautic.'.$object.'.import.step', self::STEP_MATCH_FIELDS);
                                        $this->session->set('mautic.'.$object.'.import.importfields', CsvHelper::convertHeadersIntoFields($headers));
                                        $this->session->set('mautic.'.$object.'.import.progress', [0, $linecount]);
                                        $this->session->set('mautic.'.$object.'.import.original.file', $fileData->getClientOriginalName());

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
                    $validateEvent = new ImportValidateEvent($this->request->get('object'), $form);

                    $dispatcher->dispatch(LeadEvents::IMPORT_ON_VALIDATE, $validateEvent);

                    if ($validateEvent->hasErrors()) {
                        break;
                    }

                    $matchedFields = $validateEvent->getMatchedFields();

                    if (empty($matchedFields)) {
                        $this->resetImport($object);
                        $this->removeImportFile($fullPath);
                        $this->logger->log(LogLevel::WARNING, "Import for file {$fullPath} was aborted as there were no matched files found.");

                        return $this->newAction(0, true);
                    }

                    /** @var \Mautic\LeadBundle\Entity\Import $import */
                    $import = $this->importModel->getEntity();

                    $import->setMatchedFields($matchedFields)
                        ->setObject($object)
                        ->setDir($importDir)
                        ->setLineCount($this->getLineCount($object))
                        ->setFile($fileName)
                        ->setOriginalFile($this->session->get('mautic.'.$object.'.import.original.file'))
                        ->setDefault('owner', $validateEvent->getOwnerId())
                        ->setDefault('list', $validateEvent->getList())
                        ->setDefault('tags', $validateEvent->getTags())
                        ->setDefault('skip_if_exists', $matchedFields['skip_if_exists'] ?? false)
                        ->setHeaders($this->session->get('mautic.'.$object.'.import.headers'))
                        ->setParserConfig($this->session->get('mautic.'.$object.'.import.config'));

                    unset($matchedFields['skip_if_exists']);

                    // In case the user chose to import in browser
                    if ($this->importInBrowser($form, $object)) {
                        $import->setStatus($import::MANUAL);

                        $this->session->set('mautic.'.$object.'.import.step', self::STEP_PROGRESS_BAR);
                    }

                    $this->importModel->saveEntity($import);

                    $this->session->set('mautic.'.$object.'.import.id', $import->getId());

                    // In case the user decided to queue the import
                    if ($this->importInCli($form, $object)) {
                        $this->addFlash('mautic.'.$object.'.batch.import.created');
                        $this->resetImport($object);

                        return $this->indexAction();
                    }

                    return $this->newAction(0, true);
                default:
                    // Done or something wrong

                    $this->resetImport($object);
                    $this->removeImportFile($fullPath);
                    $this->logger->log(LogLevel::ERROR, "Import for file {$fullPath} was aborted for unknown step of '{$step}'");

                    break;
            }
        }

        if (self::STEP_UPLOAD_CSV === $step || self::STEP_MATCH_FIELDS === $step) {
            $contentTemplate = 'MauticLeadBundle:Import:new.html.php';
            $viewParameters  = [
                'form'       => $form->createView(),
                'objectName' => $initEvent->objectName,
            ];
        } else {
            $contentTemplate = 'MauticLeadBundle:Import:progress.html.php';
            $viewParameters  = [
                'progress'         => $progress,
                'import'           => $import,
                'complete'         => $complete,
                'failedRows'       => $this->importModel->getFailedRows($import->getId(), $import->getObject()),
                'objectName'       => $initEvent->objectName,
                'indexRoute'       => $initEvent->indexRoute,
                'indexRouteParams' => $initEvent->indexRouteParams,
            ];
        }

        if (!$complete && $this->request->query->has('importbatch')) {
            // Ajax request to batch process so just return ajax response unless complete

            return new JsonResponse(['success' => 1, 'ignore_wdt' => 1]);
        } else {
            $viewParameters['step'] = $step;

            return $this->delegateView(
                [
                    'viewParameters'  => $viewParameters,
                    'contentTemplate' => $contentTemplate,
                    'passthroughVars' => [
                        'activeLink'    => $initEvent->activeLink,
                        'mauticContent' => 'leadImport',
                        'route'         => $this->generateUrl(
                            'mautic_import_action',
                            [
                                'object'       => $initEvent->routeObjectName,
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
     * @param string $object
     *
     * @return int
     */
    protected function getLineCount($object)
    {
        $progress = $this->session->get('mautic.'.$object.'.import.progress', [0, 0]);

        return isset($progress[1]) ? $progress[1] : 0;
    }

    /**
     * Decide whether the import will be processed in client's browser.
     *
     * @param string $object
     *
     * @return bool
     */
    protected function importInBrowser(Form $form, $object)
    {
        $browserImportLimit = $this->getLineCountLimit();

        if ($browserImportLimit && $this->getLineCount($object) < $browserImportLimit) {
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
     * @param string $object
     *
     * @return bool
     */
    protected function importInCli(Form $form, $object)
    {
        $browserImportLimit = $this->getLineCountLimit();

        if ($browserImportLimit && $this->getLineCount($object) >= $browserImportLimit) {
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
        return $this->importModel->getImportDir();
    }

    /**
     * Generates unique import directory name inside the cache dir if not stored in the session.
     * If it exists in the session, returns that one.
     *
     * @param string $object
     *
     * @return string
     */
    protected function getImportFileName($object)
    {
        // Return the dir path from session if exists
        if ($fileName = $this->session->get('mautic.'.$object.'.import.file')) {
            return $fileName;
        }

        $fileName = $this->importModel->getUniqueFileName();

        // Set the dir path to session
        $this->session->set('mautic.'.$object.'.import.file', $fileName);

        return $fileName;
    }

    /**
     * Return full absolute path to the CSV file.
     *
     * @param string $object
     *
     * @return string
     */
    protected function getFullCsvPath($object)
    {
        return $this->getImportDirName().'/'.$this->getImportFileName($object);
    }

    private function resetImport(string $object): void
    {
        $this->session->set('mautic.'.$object.'.import.headers', []);
        $this->session->set('mautic.'.$object.'.import.file', null);
        $this->session->set('mautic.'.$object.'.import.step', self::STEP_UPLOAD_CSV);
        $this->session->set('mautic.'.$object.'.import.progress', [0, 0]);
        $this->session->set('mautic.'.$object.'.import.inprogress', false);
        $this->session->set('mautic.'.$object.'.import.importfields', []);
        $this->session->set('mautic.'.$object.'.import.original.file', null);
        $this->session->set('mautic.'.$object.'.import.id', null);
    }

    private function removeImportFile(string $filepath): void
    {
        if (file_exists($filepath) && is_readable($filepath)) {
            unlink($filepath);

            $this->logger->log(LogLevel::WARNING, "File {$filepath} was removed.");
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

                $args['viewParameters'] = array_merge(
                    $args['viewParameters'],
                    [
                        'failedRows'        => $this->importModel->getFailedRows($entity->getId(), $entity->getObject()),
                        'importedRowsChart' => $entity->getDateStarted() ? $this->importModel->getImportedRowsLineChartData(
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

    /**
     * @deprecated to be removed in 3.0
     */
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
        $initEvent = $this->dispatchImportOnInit();
        $object    = $initEvent->objectSingular;

        return $object.'.import'.(($objectId) ? '.'.$objectId : '');
    }

    protected function getPermissionBase()
    {
        return $this->getModel($this->getModelName())->getPermissionBase();
    }

    protected function getRouteBase()
    {
        return 'import';
    }

    /**
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

    private function dispatchImportOnInit(): ImportInitEvent
    {
        $event = new ImportInitEvent($this->request->get('object'));

        $this->container->get('event_dispatcher')->dispatch(LeadEvents::IMPORT_ON_INITIALIZE, $event);

        return $event;
    }
}
