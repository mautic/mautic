<?php

namespace Mautic\FormBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Controller\FormController as CommonFormController;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Factory\PageHelperFactoryInterface;
use Mautic\CoreBundle\Form\Type\DateRangeType;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\ThemeHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\CoreBundle\Twig\Helper\AnalyticsHelper;
use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Mautic\FormBundle\Collector\AlreadyMappedFieldCollectorInterface;
use Mautic\FormBundle\Collector\MappedObjectCollector;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\FormRepository;
use Mautic\FormBundle\Entity\SubmissionRepository;
use Mautic\FormBundle\Exception\ValidationException;
use Mautic\FormBundle\Helper\FormFieldHelper;
use Mautic\FormBundle\Model\FormModel;
use Mautic\FormBundle\Model\SubmissionModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class FormController extends CommonFormController
{
    public function __construct(
        FormFactoryInterface $formFactory,
        FormFieldHelper $fieldHelper,
        private readonly AlreadyMappedFieldCollectorInterface $alreadyMappedFieldCollector,
        private readonly MappedObjectCollector $mappedObjectCollector,
        ManagerRegistry $doctrine,
        ModelFactory $modelFactory,
        UserHelper $userHelper,
        CoreParametersHelper $coreParametersHelper,
        EventDispatcherInterface $dispatcher,
        Translator $translator,
        FlashBag $flashBag,
        RequestStack $requestStack,
        CorePermissions $security,
        private readonly FormModel $formModel,
        private readonly AuditLogModel $auditLogModel,
        private readonly SubmissionModel $submissionModel,
        private readonly SubmissionRepository $submissionRepository,
        private readonly FormRepository $formRepository,
    ) {
        parent::__construct($formFactory, $fieldHelper, $doctrine, $modelFactory, $userHelper, $coreParametersHelper, $dispatcher, $translator, $flashBag, $requestStack, $security);
    }

    public function indexAction(Request $request, PageHelperFactoryInterface $pageHelperFactory, int $page = 1): Response
    {
        // set some permissions
        $permissions = $this->security->isGranted(
            [
                'form:forms:viewown',
                'form:forms:viewother',
                'form:forms:create',
                'form:forms:editown',
                'form:forms:editother',
                'form:forms:deleteown',
                'form:forms:deleteother',
                'form:forms:publishown',
                'form:forms:publishother',
            ],
            'RETURN_ARRAY'
        );

        if (!$permissions['form:forms:viewown'] && !$permissions['form:forms:viewother']) {
            $this->throwAccessDenied();
        }

        $this->setListFilters();

        $session = $request->getSession();

        $pageHelper = $pageHelperFactory->make('mautic.form', $page);
        $limit      = $pageHelper->getLimit();
        $start      = $pageHelper->getStart();
        $search     = $request->get('search', $session->get('mautic.form.filter', ''));
        $filter     = ['string' => $search, 'force' => []];
        $session->set('mautic.form.filter', $search);

        if (!$permissions['form:forms:viewother']) {
            $filter['force'][] = ['column' => 'f.createdBy', 'expr' => 'eq', 'value' => $this->user->getId()];
        }

        $orderBy    = $session->get('mautic.form.orderby', 'f.dateModified');
        $orderByDir = $session->get('mautic.form.orderbydir', $this->getDefaultOrderDirection());
        $forms      = $this->formModel->getEntities(
            [
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir,
            ]
        );

        $count = count($forms);

        if ($count && $count < ($start + 1)) {
            // the number of entities are now less then the current page so redirect to the last page
            $lastPage = $pageHelper->countPage($count);
            $pageHelper->rememberPage($lastPage);
            $returnUrl = $this->generateUrl('mautic_form_index', ['page' => $lastPage]);

            return $this->postActionRedirect(
                [
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => ['page' => $lastPage],
                    'contentTemplate' => 'Mautic\FormBundle\Controller\FormController::indexAction',
                    'passthroughVars' => [
                        'activeLink'    => '#mautic_form_index',
                        'mauticContent' => 'form',
                    ],
                ]
            );
        }

        $pageHelper->rememberPage($page);

        return $this->delegateView(
            [
                'viewParameters'  => [
                    'searchValue' => $search,
                    'items'       => $forms,
                    'totalItems'  => $count,
                    'page'        => $page,
                    'limit'       => $limit,
                    'permissions' => $permissions,
                    'security'    => $this->security,
                    'tmpl'        => $request->get('tmpl', 'index'),
                ],
                'contentTemplate' => '@MauticForm/Form/list.html.twig',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_form_index',
                    'mauticContent' => 'form',
                    'route'         => $this->generateUrl('mautic_form_index', ['page' => $page]),
                ],
            ]
        );
    }

    /**
     * Loads a specific form into the detailed panel.
     *
     * @param int $objectId
     */
    public function viewAction(Request $request, $objectId): Response
    {
        $activeForm = $this->formModel->getEntity($objectId);

        // set the page we came from
        $page = $request->getSession()->get('mautic.form.page', 1);

        if (null === $activeForm) {
            // set the return URL
            $returnUrl = $this->generateUrl('mautic_form_index', ['page' => $page]);

            return $this->postActionRedirect(
                [
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => ['page' => $page],
                    'contentTemplate' => 'Mautic\FormBundle\Controller\FormController::indexAction',
                    'passthroughVars' => [
                        'activeLink'    => '#mautic_form_index',
                        'mauticContent' => 'form',
                    ],
                    'flashes' => [
                        [
                            'type'    => 'error',
                            'msg'     => 'mautic.form.error.notfound',
                            'msgVars' => ['%id%' => $objectId],
                        ],
                    ],
                ]
            );
        }
        if (!$this->security->hasEntityAccess(
            'form:forms:viewown',
            'form:forms:viewother',
            $activeForm->getCreatedBy()
        )
        ) {
            $this->throwAccessDenied();
        }

        $permissions = $this->security->isGranted(
            [
                'form:forms:viewown',
                'form:forms:viewother',
                'form:forms:create',
                'form:forms:editown',
                'form:forms:editother',
                'form:forms:deleteown',
                'form:forms:deleteother',
                'form:forms:publishown',
                'form:forms:publishother',
            ],
            'RETURN_ARRAY'
        );
        $logs = $this->auditLogModel->getLogForObject('form', $objectId, $activeForm->getDateAdded());

        // Init the date range filter form
        $dateRangeValues = $request->query->all()['daterange'] ?? $request->request->all()['daterange'] ?? [];
        $action          = $this->generateUrl('mautic_form_action', ['objectAction' => 'view', 'objectId' => $objectId]);
        $dateRangeForm   = $this->formFactory->create(DateRangeType::class, $dateRangeValues, ['action' => $action]);
        // Submission stats per time period
        $timeStats = $this->submissionModel->getSubmissionsLineChartData(
            null,
            new \DateTime($dateRangeForm->get('date_from')->getData()),
            new \DateTime($dateRangeForm->get('date_to')->getData()),
            null,
            ['form_id' => $objectId]
        );

        // Only show actions and fields that still exist
        $customComponents  = $this->formModel->getCustomComponents();
        $activeFormActions = [];
        foreach ($activeForm->getActions() as $formAction) {
            if (!isset($customComponents['actions'][$formAction->getType()])) {
                continue;
            }
            $type                          = explode('.', $formAction->getType());
            $activeFormActions[$type[0]][] = $formAction;
        }

        $activeFormFields = [];
        $availableFields  = array_flip($this->fieldHelper->getChoiceList($customComponents['fields']));
        foreach ($activeForm->getFields() as $field) {
            if (!isset($availableFields[$field->getType()])) {
                continue;
            }

            $activeFormFields[] = $field;
        }

        $submissionCounts = $this->submissionRepository->getSubmissionCounts($activeForm);

        return $this->delegateView(
            [
                'viewParameters' => [
                    'activeForm'       => $activeForm,
                    'submissionCounts' => $submissionCounts,
                    'page'             => $page,
                    'logs'             => $logs,
                    'permissions'      => $permissions,
                    'stats'            => [
                        'submissionsInTime' => $timeStats,
                    ],
                    'dateRangeForm'     => $dateRangeForm->createView(),
                    'activeFormActions' => $activeFormActions,
                    'activeFormFields'  => $activeFormFields,
                    'formScript'        => htmlspecialchars($this->formModel->getFormScript($activeForm), ENT_QUOTES, 'UTF-8'),
                    'formContent'       => htmlspecialchars($this->formModel->getContent($activeForm, false), ENT_QUOTES, 'UTF-8'),
                    'availableActions'  => $customComponents['actions'],
                ],
                'contentTemplate' => '@MauticForm/Form/details.html.twig',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_form_index',
                    'mauticContent' => 'form',
                    'route'         => $action,
                ],
            ]
        );
    }

    /**
     * Generates new form and processes post data.
     *
     * @throws \Exception
     */
    public function newAction(Request $request): Response
    {
        $entity  = $this->formModel->getEntity();
        $session = $request->getSession();

        if (!$this->security->isGranted('form:forms:create')) {
            $this->throwAccessDenied();
        }

        // set the page we came from
        $page       = $request->getSession()->get('mautic.form.page', 1);
        $mauticform = $request->request->all()['mauticform'] ?? [];
        $sessionId  = $mauticform['sessionId'] ?? 'mautic_'.sha1(uniqid(mt_rand(), true));

        // set added/updated fields
        $modifiedFields = $session->get('mautic.form.'.$sessionId.'.fields.modified', []);
        $deletedFields  = $session->get('mautic.form.'.$sessionId.'.fields.deleted', []);

        // set added/updated actions
        $modifiedActions = $session->get('mautic.form.'.$sessionId.'.actions.modified', []);
        $deletedActions  = $session->get('mautic.form.'.$sessionId.'.actions.deleted', []);

        $action = $this->generateUrl('mautic_form_action', ['objectAction' => 'new']);
        $form   = $this->formModel->createForm($entity, $action);

        // /Check for a submitted form and process it
        if ('POST' === $request->getMethod()) {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    // only save fields that are not to be deleted
                    $fields = array_diff_key($modifiedFields, array_flip($deletedFields));

                    // make sure that at least one field is selected
                    if ([] === $fields) {
                        // set the error
                        $form->addError(
                            new FormError(
                                $this->translator->trans('mautic.form.form.fields.notempty', [], 'validators')
                            )
                        );
                        $valid = false;
                    } else {
                        $this->formModel->setFields($entity, $fields);

                        try {
                            // Set alias to prevent SQL errors
                            $alias = $this->formModel->cleanAlias($entity->getName(), '', 10);
                            $entity->setAlias($alias);

                            // Set timestamps
                            $this->formModel->setTimestamps($entity, true, false);

                            // Save the form first and new actions so that new fields are available to actions.
                            // Using the repository function to not trigger the listeners twice.

                            $this->formRepository->saveEntity($entity);

                            // Only save actions that are not to be deleted
                            $actions = array_diff_key($modifiedActions, array_flip($deletedActions));

                            // Set and persist actions
                            $this->formModel->setActions($entity, $actions);

                            // Save and trigger listeners
                            $this->formModel->saveEntity($entity, $this->getFormButton($form, ['buttons', 'save'])->isClicked());

                            $this->addFlashMessage(
                                'mautic.core.notice.created',
                                [
                                    '%name%'      => $entity->getName(),
                                    '%menu_link%' => 'mautic_form_index',
                                    '%url%'       => $this->generateUrl(
                                        'mautic_form_action',
                                        [
                                            'objectAction' => 'edit',
                                            'objectId'     => $entity->getId(),
                                        ]
                                    ),
                                ]
                            );

                            if ($this->getFormButton($form, ['buttons', 'save'])->isClicked()) {
                                $viewParameters = [
                                    'objectAction' => 'view',
                                    'objectId'     => $entity->getId(),
                                ];
                                $returnUrl = $this->generateUrl('mautic_form_action', $viewParameters);
                                $template  = 'Mautic\FormBundle\Controller\FormController::viewAction';
                            } else {
                                // return edit view so that all the session stuff is loaded
                                return $this->editAction($request, $entity->getId(), true);
                            }
                        } catch (ValidationException $ex) {
                            $form->addError(
                                new FormError(
                                    $ex->getMessage()
                                )
                            );
                            $valid = false;
                        } catch (\Exception $e) {
                            $form['name']->addError(
                                new FormError($this->translator->trans('mautic.form.schema.failed', [], 'validators'))
                            );
                            $valid = false;

                            if ('dev' == $this->getParameter('kernel.environment')) {
                                throw $e;
                            }
                        }
                    }
                }
            } else {
                $viewParameters = ['page' => $page];
                $returnUrl      = $this->generateUrl('mautic_form_index', $viewParameters);
                $template       = 'Mautic\FormBundle\Controller\FormController::indexAction';
            }

            if ($cancelled || ($valid && $this->getFormButton($form, ['buttons', 'save'])->isClicked())) {
                // clear temporary fields
                $this->clearSessionComponents($request, $sessionId);

                return $this->postActionRedirect(
                    [
                        'returnUrl'       => $returnUrl,
                        'viewParameters'  => $viewParameters,
                        'contentTemplate' => $template,
                        'passthroughVars' => [
                            'activeLink'    => '#mautic_form_index',
                            'mauticContent' => 'form',
                        ],
                    ]
                );
            }
        } else {
            // clear out existing fields in case the form was refreshed, browser closed, etc
            $this->clearSessionComponents($request, $sessionId);
            $modifiedFields = $modifiedActions = $deletedActions = $deletedFields = [];

            $form->get('sessionId')->setData($sessionId);

            // add a submit button
            $keyId = 'new'.hash('sha1', uniqid(mt_rand()));
            $field = new Field();

            $modifiedFields[$keyId]                    = $field->convertToArray();
            $modifiedFields[$keyId]['label']           = $this->translator->trans('mautic.core.form.submit');
            $modifiedFields[$keyId]['alias']           = 'submit';
            $modifiedFields[$keyId]['showLabel']       = 1;
            $modifiedFields[$keyId]['type']            = 'button';
            $modifiedFields[$keyId]['id']              = $keyId;
            $modifiedFields[$keyId]['inputAttributes'] = 'class="btn btn-ghost"';
            $modifiedFields[$keyId]['formId']          = $sessionId;
            unset($modifiedFields[$keyId]['form']);
            $session->set('mautic.form.'.$sessionId.'.fields.modified', $modifiedFields);
        }

        // fire the form builder event
        $customComponents = $this->formModel->getCustomComponents();

        return $this->delegateView(
            [
                'viewParameters' => [
                    'fields'         => $this->fieldHelper->getChoiceList($customComponents['fields']),
                    'formFields'     => $modifiedFields,
                    'mappedFields'   => $this->mappedObjectCollector->buildCollection(...$entity->getMappedFieldObjects()),
                    'deletedFields'  => $deletedFields,
                    'viewOnlyFields' => $customComponents['viewOnlyFields'],
                    'actions'        => $customComponents['choices'],
                    'actionSettings' => $customComponents['actions'],
                    'formActions'    => $modifiedActions,
                    'deletedActions' => $deletedActions,
                    'tmpl'           => $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index',
                    'activeForm'     => $entity,
                    'form'           => $form->createView(),
                    'inBuilder'      => true,
                ],
                'contentTemplate' => '@MauticForm/Builder/index.html.twig',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_form_index',
                    'mauticContent' => 'form',
                    'route'         => $this->generateUrl(
                        'mautic_form_action',
                        [
                            'objectAction' => (!empty($valid) ? 'edit' : 'new'), // valid means a new form was applied
                            'objectId'     => $entity->getId(),
                        ]
                    ),
                ],
            ]
        );
    }

    /**
     * Generates edit form and processes post data.
     *
     * @param int|Form $objectId
     * @param bool     $ignorePost
     * @param bool     $forceTypeSelection
     */
    public function editAction(Request $request, $objectId, $ignorePost = false, $forceTypeSelection = false): Response
    {
        $formData         = $request->request->all()['mauticform'] ?? [];
        $sessionId        = $formData['sessionId'] ?? null;
        $customComponents = $this->formModel->getCustomComponents();
        $modifiedFields   = [];
        $deletedFields    = [];
        $modifiedActions  = [];
        $deletedActions   = [];

        if ($objectId instanceof Form) {
            $entity   = $objectId;
            $objectId = 'mautic_'.sha1(uniqid(mt_rand(), true));
        } else {
            $entity = $this->formModel->getEntity($objectId);

            // Process submit of cloned form
            if (null == $entity && $objectId == $sessionId) {
                $entity = $this->formModel->getEntity();
            }
        }

        $session    = $request->getSession();

        // set the page we came from
        $page = $request->getSession()->get('mautic.form.page', 1);

        // set the return URL
        $returnUrl = $this->generateUrl('mautic_form_index', ['page' => $page]);

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\FormBundle\Controller\FormController::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_form_index',
                'mauticContent' => 'form',
            ],
        ];

        // form not found
        if (null === $entity) {
            return $this->postActionRedirect(
                array_merge(
                    $postActionVars,
                    [
                        'flashes' => [
                            [
                                'type'    => 'error',
                                'msg'     => 'mautic.form.error.notfound',
                                'msgVars' => ['%id%' => $objectId],
                            ],
                        ],
                    ]
                )
            );
        }
        if (!$this->security->hasEntityAccess(
            'form:forms:editown',
            'form:forms:editother',
            $entity->getCreatedBy()
        )
        ) {
            $this->throwAccessDenied();
        } elseif ($this->formModel->isLocked($entity)) {
            // deny access if the entity is locked
            return $this->isLocked($postActionVars, $entity, 'form.form');
        }

        $action = $this->generateUrl('mautic_form_action', ['objectAction' => 'edit', 'objectId' => $objectId]);
        $form   = $this->formModel->createForm($entity, $action);

        // /Check for a submitted form and process it
        if (!$ignorePost && 'POST' === $request->getMethod()) {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                // set added/updated fields
                $modifiedFields = $session->get('mautic.form.'.$objectId.'.fields.modified', []);
                $deletedFields  = $session->get('mautic.form.'.$objectId.'.fields.deleted', []);
                $fields         = array_diff_key($modifiedFields, array_flip($deletedFields));

                // set added/updated actions
                $modifiedActions = $session->get('mautic.form.'.$objectId.'.actions.modified', []);
                $deletedActions  = $session->get('mautic.form.'.$objectId.'.actions.deleted', []);
                $actions         = array_diff_key($modifiedActions, array_flip($deletedActions));

                if ($valid = $this->isFormValid($form)) {
                    // make sure that at least one field is selected
                    if ([] === $fields) {
                        // set the error
                        $form->addError(
                            new FormError(
                                $this->translator->trans('mautic.form.form.fields.notempty', [], 'validators')
                            )
                        );
                        $valid = false;
                    } else {
                        $this->formModel->setFields($entity, $fields);
                        $this->formModel->deleteFields($entity, $deletedFields);

                        $alias = $entity->getAlias();

                        if (empty($alias)) {
                            $alias = $this->formModel->cleanAlias($entity->getName(), '', 10);
                            $entity->setAlias($alias);
                        }

                        if (!$entity->getId()) {
                            // Set timestamps because this is a new clone
                            $this->formModel->setTimestamps($entity, true, false);
                        }

                        // save the form first so that new fields are available to actions
                        // use the repository method to not trigger listeners twice
                        try {
                            $this->formRepository->saveEntity($entity);

                            if (count($actions)) {
                                // Now set and persist the actions
                                $this->formModel->setActions($entity, $actions);
                            }

                            // Delete deleted actions
                            $this->formModel->deleteActions($entity, $deletedActions);

                            // Persist and execute listeners
                            $this->formModel->saveEntity($entity, $this->getFormButton($form, ['buttons', 'save'])->isClicked());

                            // Reset objectId to entity ID (can be session ID in case of cloned entity)
                            $objectId = $entity->getId();

                            $this->addFlashMessage(
                                'mautic.core.notice.updated',
                                [
                                    '%name%'      => $entity->getName(),
                                    '%menu_link%' => 'mautic_form_index',
                                    '%url%'       => $this->generateUrl(
                                        'mautic_form_action',
                                        [
                                            'objectAction' => 'edit',
                                            'objectId'     => $entity->getId(),
                                        ]
                                    ),
                                ]
                            );

                            if ($this->getFormButton($form, ['buttons', 'save'])->isClicked()) {
                                $viewParameters = [
                                    'objectAction' => 'view',
                                    'objectId'     => $entity->getId(),
                                ];
                                $returnUrl = $this->generateUrl('mautic_form_action', $viewParameters);
                                $template  = 'Mautic\FormBundle\Controller\FormController::viewAction';
                            }
                        } catch (ValidationException $ex) {
                            $form->addError(
                                new FormError(
                                    $ex->getMessage()
                                )
                            );
                            $valid = false;
                        }
                    }
                }
            } else {
                // unlock the entity
                $this->formModel->unlockEntity($entity);

                $viewParameters = ['page' => $page];
                $returnUrl      = $this->generateUrl('mautic_form_index', $viewParameters);
                $template       = 'Mautic\FormBundle\Controller\FormController::indexAction';
            }

            if ($cancelled || ($valid && $this->getFormButton($form, ['buttons', 'save'])->isClicked())) {
                // remove fields from session
                $this->clearSessionComponents($request, $objectId);

                // Clear session items in case columns changed
                $session->remove('mautic.formresult.'.$entity->getId().'.orderby');
                $session->remove('mautic.formresult.'.$entity->getId().'.orderbydir');
                $session->remove('mautic.formresult.'.$entity->getId().'.filters');

                return $this->postActionRedirect(
                    array_merge(
                        $postActionVars,
                        [
                            'returnUrl'       => $returnUrl,
                            'viewParameters'  => $viewParameters,
                            'contentTemplate' => $template,
                        ]
                    )
                );
            }

            if ($valid && $this->isButtonClicked($form, 'apply')) {
                // Rebuild everything to include new ids
                $reorder    = true;

                // Rebuild the form with new action so that apply doesn't keep creating a clone
                $action = $this->generateUrl('mautic_form_action', ['objectAction' => 'edit', 'objectId' => $entity->getId()]);
                $form   = $this->formModel->createForm($entity, $action);
            }
        } else {
            // lock the entity
            $this->formModel->lockEntity($entity);
        }

        if (!$form->isSubmitted()) {
            $form->get('sessionId')->setData($objectId);
        }

        // Get field and action settings
        $availableFields = $this->fieldHelper->getChoiceList($customComponents['fields']);

        // clean slate
        $this->clearSessionComponents($request, $objectId);
        $this->alreadyMappedFieldCollector->removeAllForForm($objectId);

        // load existing fields into session
        $modifiedFields   = [];
        $existingFields   = $entity->getFields()->toArray();
        $fieldMap         = [];
        $submitButton     = false;

        foreach ($existingFields as $fieldId => $formField) {
            // Check to see if the field still exists

            if ('button' == $formField->getType()) {
                // submit button found
                $submitButton = true;
            }
            if ('button' !== $formField->getType() && !in_array($formField->getType(), $availableFields)) {
                continue;
            }

            $id    = $formField->getId();
            $field = $formField->convertToArray();

            if (!$id) {
                // Cloned entity
                $id = $field['id'] = $field['sessionId'] = $fieldMap[$fieldId] = 'new'.hash('sha1', uniqid(mt_rand()));
                if (isset($field['parent'])) {
                    $field['parent'] = $fieldMap[$field['parent']];
                }
            }

            unset($field['form']);

            if (isset($customComponents['fields'][$field['type']])) {
                // Set the custom parameters
                $field['customParameters'] = $customComponents['fields'][$field['type']];
            }

            $field['formId']     = $objectId;
            $modifiedFields[$id] = $field;

            if (!empty($field['mappedObject']) && !empty($field['mappedField']) && empty($field['parent'])) {
                $this->alreadyMappedFieldCollector->addField($objectId, $field['mappedObject'], $field['mappedField']);
            }
        }

        if (!$submitButton) { // means something deleted the submit button from the form
            // add a submit button
            $keyId = 'new'.hash('sha1', uniqid(mt_rand()));
            $field = new Field();

            $modifiedFields[$keyId]                    = $field->convertToArray();
            $modifiedFields[$keyId]['label']           = $this->translator->trans('mautic.core.form.submit');
            $modifiedFields[$keyId]['alias']           = 'submit';
            $modifiedFields[$keyId]['showLabel']       = 1;
            $modifiedFields[$keyId]['type']            = 'button';
            $modifiedFields[$keyId]['id']              = $keyId;
            $modifiedFields[$keyId]['inputAttributes'] = 'class="btn btn-ghost"';
            $modifiedFields[$keyId]['formId']          = $objectId;
            unset($modifiedFields[$keyId]['form']);
        }

        if (!empty($reorder)) {
            uasort(
                $modifiedFields,
                fn ($a, $b): int => $a['order'] <=> $b['order'] ?: $a['id'] <=> $b['id']
            );
        }

        $session->set('mautic.form.'.$objectId.'.fields.modified', $modifiedFields);
        $deletedFields = [];

        // Load existing actions into session
        $modifiedActions = [];
        $existingActions = $entity->getActions()->toArray();

        foreach ($existingActions as $formAction) {
            // Check to see if the action still exists
            if (!isset($customComponents['actions'][$formAction->getType()])) {
                continue;
            }

            $id     = $formAction->getId();
            $action = $formAction->convertToArray();

            if (!$id) {
                // Cloned entity so use a random Id instead
                $action['id'] = $id = 'new'.hash('sha1', uniqid(mt_rand()));
            }
            unset($action['form']);

            $modifiedActions[$id] = $action;
        }

        if (!empty($reorder)) {
            uasort(
                $modifiedActions,
                fn ($a, $b): int => $a['order'] <=> $b['order']
            );
        }

        $session->set('mautic.form.'.$objectId.'.actions.modified', $modifiedActions);
        $deletedActions = [];

        return $this->delegateView(
            [
                'viewParameters' => [
                    'fields'             => $availableFields,
                    'formFields'         => $modifiedFields,
                    'deletedFields'      => $deletedFields,
                    'mappedFields'       => $this->mappedObjectCollector->buildCollection(...$entity->getMappedFieldObjects()),
                    'formActions'        => $modifiedActions,
                    'deletedActions'     => $deletedActions,
                    'viewOnlyFields'     => $customComponents['viewOnlyFields'],
                    'actions'            => $customComponents['choices'],
                    'actionSettings'     => $customComponents['actions'],
                    'fieldSettings'      => $customComponents['fields'],
                    'tmpl'               => $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index',
                    'activeForm'         => $entity,
                    'form'               => $form->createView(),
                    'forceTypeSelection' => $forceTypeSelection,
                    'inBuilder'          => true,
                ],
                'contentTemplate' => '@MauticForm/Builder/index.html.twig',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_form_index',
                    'mauticContent' => 'form',
                    'route'         => $this->generateUrl(
                        'mautic_form_action',
                        [
                            'objectAction' => 'edit',
                            'objectId'     => $entity->getId(),
                        ]
                    ),
                ],
            ]
        );
    }

    /**
     * Clone an entity.
     *
     * @param int $objectId
     */
    public function cloneAction(Request $request, $objectId): Response
    {
        /** @var Form $entity */
        $entity = $this->formModel->getEntity($objectId);

        if (null != $entity) {
            if (!$this->security->isGranted('form:forms:create')
                || !$this->security->hasEntityAccess(
                    'form:forms:viewown',
                    'form:forms:viewother',
                    $entity->getCreatedBy()
                )
            ) {
                $this->throwAccessDenied();
            }

            $entity = clone $entity;
            $entity->setIsPublished(false);

            // Clone the forms's fields
            $fields = $entity->getFields()->toArray();
            /** @var Field $field */
            foreach ($fields as $field) {
                $fieldClone = clone $field;
                $fieldClone->setForm($entity);
                $fieldClone->setSessionId(null);
                $entity->addField($field->getId(), $fieldClone);
            }

            // Clone the forms's actions
            $actions = $entity->getActions()->toArray();
            /** @var \Mautic\FormBundle\Entity\Action $action */
            foreach ($actions as $action) {
                $actionClone = clone $action;
                $actionClone->setForm($entity);
                $entity->addAction($action->getId(), $actionClone);
            }
        }

        return $this->editAction($request, $entity, true);
    }

    /**
     * Gives a preview of the form.
     *
     * @param int $objectId
     */
    public function previewAction($objectId, ThemeHelper $themeHelper, AssetsHelper $assetsHelper, AnalyticsHelper $analyticsHelper): Response
    {
        $form  = $this->formModel->getEntity($objectId);

        if (null === $form) {
            $html =
                '<h1>'.
                $this->translator->trans('mautic.form.error.notfound', ['%id%' => $objectId], 'flashes').
                '</h1>';
        } elseif (!$this->security->hasEntityAccess(
            'form:forms:editown',
            'form:forms:editother',
            $form->getCreatedBy()
        )
        ) {
            $html = '<h1>'.$this->translator->trans('mautic.core.error.accessdenied', [], 'flashes').'</h1>';
        } else {
            $html = $this->formModel->getContent($form, true, false);
        }

        $this->formModel->populateValuesWithGetParameters($form, $html);

        $viewParams = [
            'content'     => $html,
            'stylesheets' => [],
            'name'        => $form->getName(),
            'metaRobots'  => '<meta name="robots" content="index">',
        ];

        if ($form->getNoIndex()) {
            $viewParams['metaRobots'] = '<meta name="robots" content="noindex">';
        }

        // Use form specific template or system-wide default theme
        $template = $form->getTemplate() ?? $this->coreParametersHelper->get('theme');
        if (!empty($template)) {
            $theme = $themeHelper->getTheme($template);
            if ($theme->getTheme() != $template) {
                $config = $theme->getConfig();
                if (in_array('form', $config['features'])) {
                    $template = $theme->getTheme();
                } else {
                    $template = null;
                }
            }
        }

        $viewParams['template'] = $template;

        if (!empty($template)) {
            $logicalName     = $themeHelper->checkForTwigTemplate('@themes/'.$template.'/html/form.html.twig');

            $analytics = $analyticsHelper->getCode();

            if (!empty($analytics)) {
                $assetsHelper->addCustomDeclaration($analytics);
            }
            if ($form->getNoIndex()) {
                $assetsHelper->addCustomDeclaration('<meta name="robots" content="noindex">');
            }

            return new Response($themeHelper->renderThemeTemplate($logicalName, $viewParams));
        }

        return $this->render('@MauticForm/form.html.twig', $viewParams);
    }

    /**
     * Deletes the entity.
     *
     * @param int $objectId
     */
    public function deleteAction(Request $request, $objectId): Response
    {
        $page      = $request->getSession()->get('mautic.form.page', 1);
        $returnUrl = $this->generateUrl('mautic_form_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\FormBundle\Controller\FormController::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_form_index',
                'mauticContent' => 'form',
            ],
        ];

        if (Request::METHOD_POST === $request->getMethod()) {
            $entity = $this->formModel->getEntity($objectId);

            if (null === $entity) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.form.error.notfound',
                    'msgVars' => ['%id%' => $objectId],
                ];
            } elseif (!$this->security->hasEntityAccess(
                'form:forms:deleteown',
                'form:forms:deleteother',
                $entity->getCreatedBy()
            )
            ) {
                $this->throwAccessDenied();
            } elseif ($this->formModel->isLocked($entity)) {
                return $this->isLocked($postActionVars, $entity, 'form.form');
            }

            $this->formModel->deleteEntity($entity);

            $identifier = $this->translator->trans($entity->getName());
            $flashes[]  = [
                'type'    => 'notice',
                'msg'     => 'mautic.core.notice.deleted',
                'msgVars' => [
                    '%name%' => $identifier,
                    '%id%'   => $objectId,
                ],
            ];
        } // else don't do anything

        return $this->postActionRedirect(
            array_merge(
                $postActionVars,
                [
                    'flashes' => $flashes,
                ]
            )
        );
    }

    /**
     * Deletes a group of entities.
     */
    public function batchDeleteAction(Request $request): Response
    {
        $page      = $request->getSession()->get('mautic.form.page', 1);
        $returnUrl = $this->generateUrl('mautic_form_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\FormBundle\Controller\FormController::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_form_index',
                'mauticContent' => 'form',
            ],
        ];

        if (Request::METHOD_POST === $request->getMethod()) {
            $ids       = json_decode($request->query->get('ids', ''));
            $deleteIds = [];

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $objectId = (int) $objectId;
                $entity   = $this->formModel->getEntity($objectId);

                if (null === $entity) {
                    $flashes[] = [
                        'type'    => 'error',
                        'msg'     => 'mautic.form.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ];
                } elseif (!$this->security->hasEntityAccess(
                    'form:forms:deleteown',
                    'form:forms:deleteother',
                    $entity->getCreatedBy()
                )
                ) {
                    $flashes[] = $this->getAccessDeniedFlash();
                } elseif ($this->formModel->isLocked($entity)) {
                    $flashes[] = $this->isLocked($postActionVars, $entity, 'form.form', true);
                } else {
                    $deleteIds[] = $objectId;
                }
            }

            // Delete everything we are able to
            if ([] !== $deleteIds) {
                $entities = $this->formModel->deleteEntities($deleteIds);

                $flashes[] = [
                    'type'    => 'notice',
                    'msg'     => 'mautic.form.notice.batch_deleted',
                    'msgVars' => [
                        '%count%' => count($entities),
                    ],
                ];
            }
        } // else don't do anything

        return $this->postActionRedirect(
            array_merge(
                $postActionVars,
                [
                    'flashes' => $flashes,
                ]
            )
        );
    }

    /**
     * Clear field and actions from the session.
     */
    public function clearSessionComponents(Request $request, $sessionId): void
    {
        $session = $request->getSession();
        $session->remove('mautic.form.'.$sessionId.'.fields.modified');
        $session->remove('mautic.form.'.$sessionId.'.fields.deleted');
        $session->remove('mautic.form.'.$sessionId.'.actions.modified');
        $session->remove('mautic.form.'.$sessionId.'.actions.deleted');

        $this->alreadyMappedFieldCollector->removeAllForForm((string) $sessionId);
    }

    public function batchRebuildHtmlAction(Request $request): Response
    {
        $page      = $request->getSession()->get('mautic.form.page', 1);
        $returnUrl = $this->generateUrl('mautic_form_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\FormBundle\Controller\FormController::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_form_index',
                'mauticContent' => 'form',
            ],
        ];

        if ('POST' === $request->getMethod()) {
            $ids   = json_decode($request->query->get('ids', ''));
            $count = 0;
            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $this->formModel->getEntity($objectId);

                if (null === $entity) {
                    $flashes[] = [
                        'type'    => 'error',
                        'msg'     => 'mautic.form.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ];
                } elseif (!$this->security->hasEntityAccess(
                    'form:forms:editown',
                    'form:forms:editother',
                    $entity->getCreatedBy()
                )
                ) {
                    $flashes[] = $this->getAccessDeniedFlash();
                } elseif ($this->formModel->isLocked($entity)) {
                    $flashes[] = $this->isLocked($postActionVars, $entity, 'form.form', true);
                } else {
                    $this->formModel->generateHtml($entity);
                    ++$count;
                }
            }

            $flashes[] = [
                'type'    => 'notice',
                'msg'     => 'mautic.form.notice.batch_html_generated',
                'msgVars' => [
                    '%count%'     => $count,
                ],
            ];
        } // else don't do anything

        return $this->postActionRedirect(
            array_merge(
                $postActionVars,
                [
                    'flashes' => $flashes,
                ]
            )
        );
    }

    public function getModelName(): string
    {
        return 'form';
    }

    protected function getDefaultOrderDirection(): string
    {
        return 'DESC';
    }
}
