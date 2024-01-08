<?php

namespace Mautic\LeadBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\CsvHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\FormBundle\Helper\FormFieldHelper;
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
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Exception\LogicException;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ImportController extends FormController
{
    // Steps of the import
    public const STEP_UPLOAD_CSV      = 1;

    public const STEP_MATCH_FIELDS    = 2;

    public const STEP_PROGRESS_BAR    = 3;

    public const STEP_IMPORT_FROM_CSV = 4;

    private \Symfony\Component\HttpFoundation\Session\SessionInterface $session;

    private \Mautic\LeadBundle\Model\ImportModel $importModel;

    public function __construct(
        FormFactoryInterface $formFactory,
        FormFieldHelper $fieldHelper,
        private LoggerInterface $logger,
        ManagerRegistry $doctrine,
        MauticFactory $factory,
        ModelFactory $modelFactory,
        UserHelper $userHelper,
        CoreParametersHelper $coreParametersHelper,
        EventDispatcherInterface $dispatcher,
        Translator $translator,
        FlashBag $flashBag,
        RequestStack $requestStack,
        CorePermissions $security
    ) {
        /** @var ImportModel $model */
        $model = $modelFactory->getModel($this->getModelName());

        $this->session     = $requestStack->getMainRequest()->getSession();
        $this->importModel = $model;

        parent::__construct($formFactory, $fieldHelper, $doctrine, $factory, $modelFactory, $userHelper, $coreParametersHelper, $dispatcher, $translator, $flashBag, $requestStack, $security);
    }

    /**
     * @param int $page
     *
     * @return JsonResponse|RedirectResponse
     */
    public function indexAction(Request $request, $page = 1): Response
    {
        $initEvent = $this->dispatchImportOnInit();
        $this->session->set('mautic.import.object', $initEvent->objectSingular);

        return $this->indexStandard($request, $page);
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
     */
    protected function getIndexItems($start, $limit, $filter, $orderBy, $orderByDir, array $args = []): array
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
    public function viewAction(Request $request, $objectId)
    {
        return $this->viewStandard($request, $objectId, 'import', 'lead');
    }

    /**
     * Cancel and unpublish the import during manual import.
     *
     * @return JsonResponse|RedirectResponse
     */
    public function cancelAction(Request $request): Response
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

        return $this->indexAction($request);
    }

    /**
     * Schedules manual import to background queue.
     */
    public function queueAction(Request $request): Response
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

        return $this->indexAction($request);
    }

    /**
     * @param int  $objectId
     * @param bool $ignorePost
     */
    public function newAction(Request $request, $objectId = 0, $ignorePost = false): Response
    {
        $dispatcher = $this->dispatcher;

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
        $forceStop = $request->get('cancel', false);
        $step      = ($forceStop) ? self::STEP_UPLOAD_CSV : $this->session->get('mautic.'.$object.'.import.step', self::STEP_UPLOAD_CSV);
        $fileName  = $this->getImportFileName($object);
        $importDir = $this->getImportDirName();
        $fullPath  = $this->getFullCsvPath($object);
        $fs        = new Filesystem();
        $complete  = false;

        if (!file_exists($fullPath) && self::STEP_UPLOAD_CSV !== $step) {
            // Force step one if the file doesn't exist
            $this->logger->log(LogLevel::WARNING, "File {$fullPath} does not exist anymore. Reseting import to step STEP_UPLOAD_CSV.");
            $this->addFlashMessage('mautic.import.file.missing', ['%file%' => $this->getImportFileName($object)], FlashBag::LEVEL_ERROR);
            $step = self::STEP_UPLOAD_CSV;
            $this->session->set('mautic.'.$object.'.import.step', self::STEP_UPLOAD_CSV);
        }

        $progress = (new Progress())->bindArray($this->session->get('mautic.'.$object.'.import.progress', [0, 0]));
        $import   = $this->importModel->getEntity();
        $action   = $this->generateUrl('mautic_import_action', ['object' => $request->get('object'), 'objectAction' => 'new']);

        switch ($step) {
            case self::STEP_UPLOAD_CSV:
                if ($forceStop) {
                    $this->resetImport($object);
                    $this->removeImportFile($fullPath);
                    $this->logger->log(LogLevel::WARNING, "Import for file {$fullPath} was force-stopped.");
                }

                $form = $this->formFactory->create(LeadImportType::class, [], ['action' => $action]);
                break;
            case self::STEP_MATCH_FIELDS:
                $mappingEvent = $dispatcher->dispatch(
                    new ImportMappingEvent($request->get('object')),
                    LeadEvents::IMPORT_ON_FIELD_MAPPING
                );

                try {
                    $form = $this->formFactory->create(
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

                    return $this->newAction($request, 0, true);
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

        // /Check for a submitted form and process it
        if (!$ignorePost && 'POST' === $request->getMethod()) {
            if (!isset($form) || $this->isFormCancelled($form)) {
                $this->resetImport($object);
                $this->removeImportFile($fullPath);
                $reason = isset($form) ? 'the form is empty' : 'the form was canceled';
                $this->logger->log(LogLevel::WARNING, "Import for file {$fullPath} was aborted because {$reason}.");

                return $this->newAction($request, 0, true);
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

                                        return $this->newAction($request, 0, true);
                                    }
                                }
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
                    break;
                case self::STEP_MATCH_FIELDS:
                    $validateEvent = new ImportValidateEvent($request->get('object'), $form);

                    $dispatcher->dispatch($validateEvent, LeadEvents::IMPORT_ON_VALIDATE);

                    if ($validateEvent->hasErrors()) {
                        break;
                    }

                    $matchedFields = $validateEvent->getMatchedFields();

                    if (empty($matchedFields)) {
                        $this->resetImport($object);
                        $this->removeImportFile($fullPath);
                        $this->logger->log(LogLevel::WARNING, "Import for file {$fullPath} was aborted as there were no matched files found.");

                        return $this->newAction($request, 0, true);
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
                        ->setDefault('skip_if_exists', $validateEvent->getSkipIfExists())
                        ->setHeaders($this->session->get('mautic.'.$object.'.import.headers'))
                        ->setParserConfig($this->session->get('mautic.'.$object.'.import.config'));

                    // In case the user chose to import in browser
                    if ($this->importInBrowser($form, $object)) {
                        $import->setStatus($import::MANUAL);
                        $this->session->set('mautic.'.$object.'.import.step', self::STEP_PROGRESS_BAR);
                    }
                    $this->importModel->saveEntity($import);
                    $this->session->set('mautic.'.$object.'.import.id', $import->getId());
                    // In case the user decided to queue the import
                    if ($this->importInCli($form, $object)) {
                        $this->addFlashMessage('mautic.lead.batch.import.created');
                        $this->resetImport($object);

                        return $this->indexAction($request);
                    }

                    return $this->newAction($request, 0, true);
                default:
                    // Done or something wrong

                    $this->resetImport($object);
                    $this->removeImportFile($fullPath);
                    $this->logger->log(LogLevel::ERROR, "Import for file {$fullPath} was aborted for unknown step of '{$step}'");

                    break;
            }
        }

        if (self::STEP_UPLOAD_CSV === $step || self::STEP_MATCH_FIELDS === $step) {
            $contentTemplate = '@MauticLead/Import/new.html.twig';
            $viewParameters  = [
                'form'       => $form->createView(),
                'objectName' => $initEvent->objectName,
            ];
        } else {
            $contentTemplate = '@MauticLead/Import/progress.html.twig';
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

        if (!$complete && $request->query->has('importbatch')) {
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

        return $progress[1] ?? 0;
    }

    /**
     * Decide whether the import will be processed in client's browser.
     *
     * @param FormInterface<FormInterface> $form
     * @param string                       $object
     */
    protected function importInBrowser(FormInterface $form, $object): bool
    {
        $browserImportLimit = $this->getLineCountLimit();

        if ($browserImportLimit && $this->getLineCount($object) < $browserImportLimit) {
            return true;
        } elseif (!$browserImportLimit && $this->getFormButton($form, ['buttons', 'save'])->isClicked()) {
            return true;
        }

        return false;
    }

    protected function getLineCountLimit()
    {
        return $this->coreParametersHelper->get('background_import_if_more_rows_than', 0);
    }

    /**
     * Decide whether the import will be queued to be processed by the CLI command in the background.
     *
     * @param FormInterface<FormInterface> $form
     * @param string                       $object
     */
    protected function importInCli(FormInterface $form, $object): bool
    {
        $browserImportLimit = $this->getLineCountLimit();

        if ($browserImportLimit && $this->getLineCount($object) >= $browserImportLimit) {
            return true;
        } elseif (!$browserImportLimit && $this->getFormButton($form, ['buttons', 'apply'])->isClicked()) {
            return true;
        }

        return false;
    }

    /**
     * Generates import directory path.
     */
    protected function getImportDirName(): string
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
     */
    protected function getFullCsvPath($object): string
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
     * @return mixed[]
     */
    public function getViewArguments(array $args, $action): array
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
                            $entity->getDateEnded() ?: $entity->getDateModified(),
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
     */
    protected function generateUrl(string $route, array $parameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        if (!isset($parameters['object'])) {
            $request = $this->getCurrentRequest();
            \assert(null !== $request);
            $parameters['object'] = $request->get('object', 'contacts');
        }

        return parent::generateUrl($route, $parameters, $referenceType);
    }

    protected function getModelName(): string
    {
        return 'lead.import';
    }

    protected function getSessionBase($objectId = null): string
    {
        $initEvent = $this->dispatchImportOnInit();
        $object    = $initEvent->objectSingular;

        return $object.'.import'.(($objectId) ? '.'.$objectId : '');
    }

    protected function getPermissionBase()
    {
        return $this->getModel($this->getModelName())->getPermissionBase();
    }

    protected function getRouteBase(): string
    {
        return 'import';
    }

    protected function getTemplateBase(): string
    {
        return '@MauticLead/Import';
    }

    /**
     * Provide the name of the column which is used for default ordering.
     */
    protected function getDefaultOrderColumn(): string
    {
        return 'dateAdded';
    }

    /**
     * Provide the direction for default ordering.
     */
    protected function getDefaultOrderDirection(): string
    {
        return 'DESC';
    }

    private function dispatchImportOnInit(): ImportInitEvent
    {
        $request = $this->getCurrentRequest();
        \assert(null !== $request);
        $event = new ImportInitEvent($request->get('object'));

        $this->dispatcher->dispatch($event, LeadEvents::IMPORT_ON_INITIALIZE);

        return $event;
    }
}
