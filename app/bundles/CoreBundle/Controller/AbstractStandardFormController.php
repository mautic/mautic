<?php

namespace Mautic\CoreBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Form\Type\DateRangeType;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\AbstractCommonModel;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\FormBundle\Helper\FormFieldHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

abstract class AbstractStandardFormController extends AbstractFormController
{
    use FormErrorMessagesTrait;

    public function __construct(
        protected FormFactoryInterface $formFactory,
        protected FormFieldHelper $fieldHelper,
        ManagerRegistry $managerRegistry,
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
        parent::__construct($managerRegistry, $factory, $modelFactory, $userHelper, $coreParametersHelper, $dispatcher, $translator, $flashBag, $requestStack, $security);
    }

    /**
     * Get this controller's model name.
     */
    abstract protected function getModelName(): string;

    /**
     * Support non-index pages such as modal forms.
     */
    protected function generateUrl(string $route, array $parameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        if (false === $route) {
            return false;
        }

        return parent::generateUrl($route, $parameters, $referenceType);
    }

    /**
     * Modify the cloned entity prior to sending through editAction.
     *
     * @return array of arguments for editAction
     */
    protected function afterEntityClone($newEntity, $entity)
    {
        return [$newEntity, true];
    }

    /**
     * Called after the entity has been persisted allowing for custom preperation of $entity prior to viewAction.
     */
    protected function afterEntitySave($entity, Form $form, $action, $pass = null)
    {
    }

    /**
     * Called after the form is validated on POST.
     */
    protected function afterFormProcessed($isValid, $entity, Form $form, $action, $isClone = false)
    {
    }

    /**
     * Deletes a group of entities.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function batchDeleteStandard(Request $request)
    {
        $page      = $request->getSession()->get('mautic.'.$this->getSessionBase().'.page', 1);
        $returnUrl = $this->generateUrl($this->getIndexRoute(), ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => $this->getControllerBase().'::'.$this->getPostActionControllerAction('batchDelete').'Action',
            'passthroughVars' => [
                'mauticContent' => $this->getJsLoadMethodPrefix(),
            ],
        ];

        if ('POST' == $request->getMethod()) {
            $model     = $this->getModel($this->getModelName());
            $ids       = json_decode($request->query->get('ids', ''));
            $deleteIds = [];

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $model->getEntity($objectId);

                if (null === $entity) {
                    $flashes[] = [
                        'type'    => 'error',
                        'msg'     => $this->getTranslatedString('error.notfound'),
                        'msgVars' => ['%id%' => $objectId],
                    ];
                } elseif (!$this->checkActionPermission('batchDelete', $entity)) {
                    $flashes[] = $this->accessDenied(true);
                } elseif ($model->isLocked($entity)) {
                    $flashes[] = $this->isLocked($postActionVars, $entity, $this->getModelName(), true);
                } else {
                    $deleteIds[] = $objectId;
                }
            }

            // Delete everything we are able to
            if (!empty($deleteIds)) {
                $entities = $model->deleteEntities($deleteIds);

                $flashes[] = [
                    'type'    => 'notice',
                    'msg'     => $this->getTranslatedString('notice.batch_deleted'),
                    'msgVars' => [
                        '%count%' => count($entities),
                    ],
                ];
            }
        } // else don't do anything

        return $this->postActionRedirect(
            $this->getPostActionRedirectArguments(
                array_merge(
                    $postActionVars,
                    [
                        'flashes' => $flashes,
                    ]
                ),
                'batchDelete'
            )
        );
    }

    /**
     * Modify entity prior to persisting or perform custom validation on the form.
     *
     * @return mixed Whatever is returned will be passed into afterEntitySave; pass false to fail validation
     */
    protected function beforeEntitySave($entity, Form $form, $action, $objectId = null, $isClone = false): bool
    {
        return true;
    }

    /**
     * Do anything necessary before the form is checked for POST and processed.
     */
    protected function beforeFormProcessed($entity, Form $form, $action, $isPost, $objectId = null, $isClone = false)
    {
    }

    /**
     * @return bool|mixed
     */
    protected function checkActionPermission($action, $entity = null, $objectId = null)
    {
        $permissionUser = 0;

        if ($entity) {
            $permissionUser = method_exists($entity, 'getPermissionUser') ? $entity->getPermissionUser() : $entity->getCreatedBy();
        }

        if ($entity) {
            return match ($action) {
                'new' => $this->security->isGranted($this->getPermissionBase().':create'),
                'view', 'index' => ($entity) ? $this->security->hasEntityAccess(
                    $this->getPermissionBase().':viewown',
                    $this->getPermissionBase().':viewother',
                    $permissionUser
                ) : $this->security->isGranted($this->getPermissionBase().':view'),
                'clone' => $this->security->isGranted($this->getPermissionBase().':create')
                && $this->security->hasEntityAccess(
                    $this->getPermissionBase().':viewown',
                    $this->getPermissionBase().':viewother',
                    $permissionUser
                ),
                'delete', 'batchDelete' => $this->security->hasEntityAccess(
                    $this->getPermissionBase().':deleteown',
                    $this->getPermissionBase().':deleteother',
                    $permissionUser
                ),
                default => $this->security->hasEntityAccess(
                    $this->getPermissionBase().':'.$action.'own',
                    $this->getPermissionBase().':'.$action.'other',
                    $permissionUser
                ),
            };
        } else {
            return match ($action) {
                'new' => $this->security->isGranted($this->getPermissionBase().':create'),
                'view', 'index' => $this->security->isGranted($this->getPermissionBase().':view'),
                'clone' => $this->security->isGranted($this->getPermissionBase().':create')
                && $this->security->isGranted($this->getPermissionBase().':view'),
                'delete', 'batchDelete' => $this->security->isGranted($this->getPermissionBase().':delete'),
                default => $this->security->isGranted($this->getPermissionBase().':'.$action),
            };
        }
    }

    /**
     * Clone an entity.
     *
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function cloneStandard(Request $request, $objectId)
    {
        $model  = $this->getModel($this->getModelName());
        $entity = $model->getEntity($objectId);

        if (null != $entity) {
            if (!$this->checkActionPermission('clone', $entity)) {
                return $this->accessDenied();
            }

            $newEntity = clone $entity;

            if ($arguments = $this->afterEntityClone($newEntity, $entity)) {
                array_unshift($arguments, $request);

                return call_user_func_array([$this, 'editAction'], $arguments);
            } else {
                return $this->editAction($request, $newEntity, true);
            }
        }

        return $this->newAction($request);
    }

    /**
     * Deletes the entity.
     *
     * @param int $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function deleteStandard(Request $request, $objectId)
    {
        $page      = $request->getSession()->get('mautic.'.$this->getSessionBase().'.page', 1);
        $returnUrl = $this->generateUrl($this->getIndexRoute(), ['page' => $page]);
        $flashes   = [];
        $model     = $this->getModel($this->getModelName());
        $entity    = $model->getEntity($objectId);

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => $this->getControllerBase().'::'.$this->getPostActionControllerAction('delete').'Action',
            'passthroughVars' => [
                'mauticContent' => $this->getJsLoadMethodPrefix(),
            ],
            'entity' => $entity,
        ];

        if ('POST' == $request->getMethod()) {
            if (null === $entity) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => $this->getTranslatedString('error.notfound'),
                    'msgVars' => ['%id%' => $objectId],
                ];
            } elseif (!$this->checkActionPermission('delete', $entity)) {
                return $this->accessDenied();
            } elseif ($model->isLocked($entity)) {
                return $this->isLocked($postActionVars, $entity, $this->getModelName());
            }

            $model->deleteEntity($entity);

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
            $this->getPostActionRedirectArguments(
                array_merge(
                    $postActionVars,
                    [
                        'flashes' => $flashes,
                    ]
                ),
                'delete'
            )
        );
    }

    /**
     * @param bool $ignorePost
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     *
     * @throws \Exception
     */
    protected function editStandard(Request $request, $objectId, $ignorePost = false)
    {
        $isClone = false;
        $model   = $this->getModel($this->getModelName());
        if (!$model instanceof FormModel) {
            throw new \Exception($model::class.' must extend '.FormModel::class);
        }

        $entity = $this->getFormEntity('edit', $objectId, $isClone);

        // set the return URL
        $returnUrl      = $this->generateUrl($this->getIndexRoute());
        $page           = $request->getSession()->get('mautic.'.$this->getSessionBase().'.page', 1);
        $viewParameters = ['page' => $page];

        $template = $this->getControllerBase().'::'.$this->getPostActionControllerAction('edit').'Action';

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => $viewParameters,
            'contentTemplate' => $template,
            'passthroughVars' => [
                'mauticContent' => $this->getJsLoadMethodPrefix(),
            ],
            'entity' => $entity,
        ];

        // form not found
        if (null === $entity) {
            return $this->postActionRedirect(
                $this->getPostActionRedirectArguments(
                    array_merge(
                        $postActionVars,
                        [
                            'flashes' => [
                                [
                                    'type'    => 'error',
                                    'msg'     => $this->getTranslatedString('error.notfound'),
                                    'msgVars' => ['%id%' => $objectId],
                                ],
                            ],
                        ]
                    ),
                    'edit'
                )
            );
        } elseif ((!$isClone && !$this->checkActionPermission('edit', $entity)) || ($isClone && !$this->checkActionPermission('create'))) {
            // deny access if the entity is not a clone and don't have permission to edit or is a clone and don't have permission to create
            return $this->accessDenied();
        } elseif (!$isClone && $model->isLocked($entity)) {
            // deny access if the entity is locked
            return $this->isLocked($postActionVars, $entity, $this->getModelName());
        }

        $options = $this->getEntityFormOptions();
        $action  = $this->generateUrl($this->getActionRoute(), ['objectAction' => 'edit', 'objectId' => $objectId]);
        $form    = $model->createForm($entity, $this->formFactory, $action, $options);

        $isPost = !$ignorePost && 'POST' == $request->getMethod();
        $this->beforeFormProcessed($entity, $form, 'edit', $isPost, $objectId, $isClone);

        // /Check for a submitted form and process it
        if ($isPost) {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    if ($valid = $this->beforeEntitySave($entity, $form, 'edit', $objectId, $isClone)) {
                        $model->saveEntity($entity, $this->getFormButton($form, ['buttons', 'save'])->isClicked());

                        $this->afterEntitySave($entity, $form, 'edit', $valid);

                        $this->addFlashMessage(
                            'mautic.core.notice.updated',
                            [
                                '%name%'      => $entity->getName(),
                                '%menu_link%' => $this->getIndexRoute(),
                                '%url%'       => $this->generateUrl(
                                    $this->getActionRoute(),
                                    [
                                        'objectAction' => 'edit',
                                        'objectId'     => $entity->getId(),
                                    ]
                                ),
                            ]
                        );

                        if ($entity->getId() !== $objectId) {
                            // No longer a clone - this is important for Apply
                            $objectId = $entity->getId();
                        }

                        if (!$this->isFormApplied($form) && method_exists($this, 'viewAction')) {
                            $viewParameters                    = ['objectId' => $objectId, 'objectAction' => 'view'];
                            $returnUrl                         = $this->generateUrl($this->getActionRoute(), $viewParameters);
                            $postActionVars['contentTemplate'] = $this->getControllerBase().'::viewAction';
                        }
                    }

                    $this->afterFormProcessed($valid, $entity, $form, 'edit', $isClone);
                }
            } else {
                if (!$isClone) {
                    // unlock the entity
                    $model->unlockEntity($entity);
                }

                $returnUrl = $this->generateUrl($this->getIndexRoute(), $viewParameters);
            }

            if ($cancelled || ($valid && !$this->isFormApplied($form))) {
                return $this->postActionRedirect(
                    $this->getPostActionRedirectArguments(
                        array_merge(
                            $postActionVars,
                            [
                                'returnUrl'      => $returnUrl,
                                'viewParameters' => $viewParameters,
                            ]
                        ),
                        'edit'
                    )
                );
            } elseif ($valid) {
                // Rebuild the form with new action so that apply doesn't keep creating a clone
                $action = $this->generateUrl($this->getActionRoute(), ['objectAction' => 'edit', 'objectId' => $entity->getId()]);
                $form   = $model->createForm($entity, $this->formFactory, $action);
                $this->beforeFormProcessed($entity, $form, 'edit', false, $isClone);
            }
        } elseif (!$isClone) {
            $model->lockEntity($entity);
        }

        $delegateArgs = [
            'viewParameters' => [
                'permissionBase'  => $this->getPermissionBase(),
                'mauticContent'   => $this->getJsLoadMethodPrefix(),
                'actionRoute'     => $this->getActionRoute(),
                'indexRoute'      => $this->getIndexRoute(),
                'tablePrefix'     => $model->getRepository()->getTableAlias(),
                'modelName'       => $this->getModelName(),
                'translationBase' => $this->getTranslationBase(),
                'tmpl'            => $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index',
                'entity'          => $entity,
                'form'            => $this->getFormView($form, 'edit'),
            ],
            'contentTemplate' => $this->getTemplateName('form.html.twig'),
            'passthroughVars' => [
                'mauticContent' => $this->getJsLoadMethodPrefix(),
                'route'         => $this->generateUrl(
                    $this->getActionRoute(),
                    [
                        'objectAction' => 'edit',
                        'objectId'     => $entity->getId(),
                    ]
                ),
                'validationError' => $this->getFormErrorForBuilder($form),
            ],
            'objectId' => $objectId,
            'entity'   => $entity,
        ];

        return $this->delegateView(
            $this->getViewArguments($delegateArgs, 'edit')
        );
    }

    /**
     * Get action route.
     *
     * @return string
     */
    protected function getActionRoute()
    {
        return 'mautic_'.str_replace('mautic_', '', $this->getRouteBase().'_action');
    }

    /**
     * Get controller base if different than MauticCoreBundle:Standard.
     *
     * @return string
     */
    protected function getControllerBase()
    {
        return static::class;
    }

    /**
     * @param bool $isClone
     */
    protected function getFormEntity($action, &$objectId = null, &$isClone = false)
    {
        $model = $this->getModel($this->getModelName());

        switch ($action) {
            case 'new':
                $entity = $model->getEntity();
                break;
            case 'edit':
                /* @var $entity FormEntity */
                if (is_object($objectId)) {
                    $entity   = $objectId;
                    $isClone  = true;
                    $objectId = (!empty($this->sessionId)) ? $this->sessionId : 'mautic_'.sha1(uniqid(mt_rand(), true));
                } elseif (str_contains($objectId, 'mautic_')) {
                    $isClone = true;
                    $entity  = $model->getEntity();
                } else {
                    $entity = $model->getEntity($objectId);
                }
                break;
        }

        return $entity;
    }

    /**
     * Set custom form themes, etc.
     */
    protected function getFormView(FormInterface $form, $action): FormView
    {
        return $form->createView();
    }

    /**
     * Get items for index list.
     */
    protected function getIndexItems($start, $limit, $filter, $orderBy, $orderByDir, array $args = [])
    {
        $items = $this->getModel($this->getModelName())->getEntities(
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
     * Get index route.
     *
     * @return string
     */
    protected function getIndexRoute()
    {
        return 'mautic_'.str_replace('mautic_', '', $this->getRouteBase().'_index');
    }

    /**
     * Get the name of the JS onLoad and onUnload methods for ajax.
     *
     * @return mixed
     */
    protected function getJsLoadMethodPrefix()
    {
        return $this->getModelName();
    }

    /**
     * Get the permission base from the model.
     *
     * @return string
     */
    protected function getPermissionBase()
    {
        return $this->getModel($this->getModelName())->getPermissionBase();
    }

    /**
     * Amend the parameters sent through postActionRedirect.
     */
    protected function getPostActionRedirectArguments(array $args, $action): array
    {
        return $args;
    }

    /**
     * @return string
     */
    protected function getPostActionControllerAction($action)
    {
        return 'index';
    }

    /**
     * Get the route base for getIndexRoute() and getActionRoute() if they do not meet the mautic_*_index and mautic_*_action standards.
     *
     * @return mixed
     */
    protected function getRouteBase()
    {
        return $this->getModelName();
    }

    /**
     * Provide the name of the column which is used for default ordering.
     *
     * @return string
     */
    protected function getDefaultOrderColumn()
    {
        return 'name';
    }

    /**
     * Provide the direction for default ordering.
     *
     * @return string
     */
    protected function getDefaultOrderDirection()
    {
        return 'ASC';
    }

    /**
     * @return mixed
     */
    protected function getSessionBase($objectId = null)
    {
        $base = $this->getModelName();

        if (null !== $objectId) {
            $base .= '.'.$objectId;
        }

        return $base;
    }

    /**
     * Get the template file.
     *
     * @return string
     */
    protected function getTemplateName($file)
    {
        $namespaces = [
            $this->getTemplateBase(),
            '@MauticCore/Standard',
        ];

        foreach ($namespaces as $namespace) {
            if ($this->get('twig')->getLoader()->exists($namespace.'/'.$file)) {
                return $namespace.'/'.$file;
            }
        }

        throw new \Exception("Template {$file} not found in any of the following places: ".implode(', ', $namespaces).'.');
    }

    /**
     * Get template base different than @MauticCore/Standard.
     *
     * @return string
     */
    protected function getTemplateBase()
    {
        return '@MauticCore/Standard';
    }

    /**
     * Get custom or core translation.
     *
     * @return string
     */
    protected function getTranslatedString($string)
    {
        return $this->translator->hasId($this->getTranslationBase().'.'.$string) ? $this->getTranslationBase()
            .'.'.$string : 'mautic.core.'.$string;
    }

    /**
     * Get the base to override core translation keys.
     *
     * @return string
     */
    protected function getTranslationBase()
    {
        return 'mautic.'.$this->getModelName();
    }

    /**
     * Amend the parameters sent through delegateView.
     */
    protected function getViewArguments(array $args, $action): array
    {
        return $args;
    }

    /**
     * Return array of options for the form when it's being created.
     *
     * @return array
     */
    protected function getEntityFormOptions()
    {
        return [];
    }

    /**
     * Return array of options update select response.
     *
     * @param string $updateSelect HTML id of the select
     * @param object $entity
     * @param string $nameMethod   name of the entity method holding the name
     * @param string $groupMethod  name of the entity method holding the select group
     *
     * @return array
     */
    protected function getUpdateSelectParams($updateSelect, $entity, $nameMethod = 'getName', $groupMethod = 'getLanguage')
    {
        $options = [
            'updateSelect' => $updateSelect,
            'id'           => $entity->getId(),
            'name'         => $entity->$nameMethod(),
        ];

        if ($groupMethod) {
            $options['group'] = $entity->$groupMethod();
        }

        return $options;
    }

    /**
     * @param string $timezone
     *
     * @return array
     */
    protected function getViewDateRange(Request $request, $objectId, $returnUrl, $timezone = 'local', &$dateRangeForm = null)
    {
        $name            = $this->getSessionBase($objectId).'.view.daterange';
        $method          = ('POST' === $request->getMethod()) ? 'request' : 'query';
        $dateRangeValues = $request->$method->get('daterange', $request->getSession()->get($name, []));
        $request->getSession()->set($name, $dateRangeValues);

        $dateRangeForm = $this->formFactory->create(DateRangeType::class, $dateRangeValues, ['action' => $returnUrl]);
        $dateFrom      = new \DateTime($dateRangeForm['date_from']->getData());
        $dateFrom->setTime(0, 0, 0);
        $dateTo = new \DateTime($dateRangeForm['date_to']->getData());
        $dateTo->setTime(24, 59, 59);

        if ('utc' === $timezone) {
            $dateFrom = clone $dateFrom;
            $dateFrom->setTimezone(new \DateTimeZone('utc'));
            $dateTo = clone $dateTo;
            $dateTo->setTimezone(new \DateTimeZone('utc'));
        }

        return [$dateFrom, $dateTo];
    }

    /**
     * @param int $page
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    protected function indexStandard(Request $request, $page = null): Response
    {
        // set some permissions
        $permissions = $this->security->isGranted(
            [
                $this->getPermissionBase().':view',
                $this->getPermissionBase().':viewown',
                $this->getPermissionBase().':viewother',
                $this->getPermissionBase().':create',
                $this->getPermissionBase().':edit',
                $this->getPermissionBase().':editown',
                $this->getPermissionBase().':editother',
                $this->getPermissionBase().':delete',
                $this->getPermissionBase().':deleteown',
                $this->getPermissionBase().':deleteother',
                $this->getPermissionBase().':publish',
                $this->getPermissionBase().':publishown',
                $this->getPermissionBase().':publishother',
            ],
            'RETURN_ARRAY',
            null,
            true
        );

        if (!$this->checkActionPermission('index')) {
            return $this->accessDenied();
        }

        $this->setListFilters();

        $session = $request->getSession();
        if (empty($page)) {
            $page = $session->get('mautic.'.$this->getSessionBase().'.page', 1);
        }

        // set limits
        $limit = $session->get('mautic.'.$this->getSessionBase().'.limit', $this->coreParametersHelper->get('default_pagelimit'));
        $start = (1 === $page) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search = $request->get('search', $session->get('mautic.'.$this->getSessionBase().'.filter', ''));
        $session->set('mautic.'.$this->getSessionBase().'.filter', $search);

        $filter = ['string' => $search, 'force' => []];

        $model = $this->getModel($this->getModelName());
        $repo  = $model->getRepository();

        if (!$permissions[$this->getPermissionBase().':viewother']) {
            $filter['force'][] = ['column' => $repo->getTableAlias().'.createdBy', 'expr' => 'eq', 'value' => $this->user->getId()];
        }

        $orderBy    = $session->get('mautic.'.$this->getSessionBase().'.orderby', $repo->getTableAlias().'.'.$this->getDefaultOrderColumn());
        $orderByDir = $session->get('mautic.'.$this->getSessionBase().'.orderbydir', $this->getDefaultOrderDirection());

        [$count, $items] = $this->getIndexItems($start, $limit, $filter, $orderBy, $orderByDir);

        if ($count && $count < ($start + 1)) {
            // the number of entities are now less then the current page so redirect to the last page
            $lastPage = (1 === $count) ? 1 : (((ceil($count / $limit)) ?: 1) ?: 1);

            $session->set('mautic.'.$this->getSessionBase().'.page', $lastPage);
            $returnUrl = $this->generateUrl($this->getIndexRoute(), ['page' => $lastPage]);

            return $this->postActionRedirect(
                $this->getPostActionRedirectArguments(
                    [
                        'returnUrl'       => $returnUrl,
                        'viewParameters'  => ['page' => $lastPage],
                        'contentTemplate' => $this->getControllerBase().'::'.$this->getPostActionControllerAction('index').'Action',
                        'passthroughVars' => [
                            'mauticContent' => $this->getJsLoadMethodPrefix(),
                        ],
                    ],
                    'index'
                )
            );
        }

        // set what page currently on so that we can return here after form submission/cancellation
        $session->set('mautic.'.$this->getSessionBase().'.page', $page);

        $viewParameters = [
            'permissionBase'  => $this->getPermissionBase(),
            'mauticContent'   => $this->getJsLoadMethodPrefix(),
            'sessionVar'      => $this->getSessionBase(),
            'actionRoute'     => $this->getActionRoute(),
            'indexRoute'      => $this->getIndexRoute(),
            'tablePrefix'     => $model->getRepository()->getTableAlias(),
            'modelName'       => $this->getModelName(),
            'translationBase' => $this->getTranslationBase(),
            'searchValue'     => $search,
            'items'           => $items,
            'totalItems'      => $count,
            'page'            => $page,
            'limit'           => $limit,
            'permissions'     => $permissions,
            'tmpl'            => $request->get('tmpl', 'index'),
        ];

        return $this->delegateView(
            $this->getViewArguments(
                [
                    'viewParameters'  => $viewParameters,
                    'contentTemplate' => $this->getTemplateName('list.html.twig'),
                    'passthroughVars' => [
                        'mauticContent' => $this->getJsLoadMethodPrefix(),
                        'route'         => $this->generateUrl($this->getIndexRoute(), ['page' => $page]),
                    ],
                ],
                'index'
            )
        );
    }

    /**
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     *
     * @throws \Exception
     */
    protected function newStandard(Request $request)
    {
        $entity = $this->getFormEntity('new');

        if (!$this->checkActionPermission('new')) {
            return $this->accessDenied();
        }

        $model = $this->getModel($this->getModelName());
        if (!$model instanceof FormModel) {
            throw new \Exception($model::class.' must extend '.FormModel::class);
        }

        // set the page we came from
        $page = $request->getSession()->get('mautic.'.$this->getSessionBase().'.page', 1);

        $options = $this->getEntityFormOptions();
        $action  = $this->generateUrl($this->getActionRoute(), ['objectAction' => 'new']);
        $form    = $model->createForm($entity, $this->formFactory, $action, $options);

        // /Check for a submitted form and process it
        $isPost = 'POST' === $request->getMethod();
        $this->beforeFormProcessed($entity, $form, 'new', $isPost);

        if ($isPost) {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    if ($valid = $this->beforeEntitySave($entity, $form, 'new')) {
                        $model->saveEntity($entity);
                        $this->afterEntitySave($entity, $form, 'new', $valid);

                        if (method_exists($this, 'viewAction')) {
                            $viewParameters = ['objectId' => $entity->getId(), 'objectAction' => 'view'];
                            $returnUrl      = $this->generateUrl($this->getActionRoute(), $viewParameters);
                            $template       = $this->getControllerBase().'::viewAction';
                        } else {
                            $viewParameters = ['page' => $page];
                            $returnUrl      = $this->generateUrl($this->getIndexRoute(), $viewParameters);
                            $template       = $this->getControllerBase().'::'.$this->getPostActionControllerAction('new').'Action';
                        }
                    }
                }

                $this->afterFormProcessed($valid, $entity, $form, 'new');
            } else {
                $viewParameters = ['page' => $page];
                $returnUrl      = $this->generateUrl($this->getIndexRoute(), $viewParameters);
                $template       = $this->getControllerBase().'::'.$this->getPostActionControllerAction('new').'Action';
            }

            $passthrough = [
                'mauticContent' => $this->getJsLoadMethodPrefix(),
            ];

            if ($isInPopup = isset($form['updateSelect'])) {
                $template    = false;
                $passthrough = array_merge(
                    $passthrough,
                    $this->getUpdateSelectParams($form['updateSelect']->getData(), $entity)
                );
            }

            if ($cancelled || ($valid && !$this->isFormApplied($form))) {
                if ($isInPopup) {
                    $passthrough['closeModal'] = true;
                }

                return $this->postActionRedirect(
                    $this->getPostActionRedirectArguments(
                        [
                            'returnUrl'       => $returnUrl,
                            'viewParameters'  => $viewParameters,
                            'contentTemplate' => $template,
                            'passthroughVars' => $passthrough,
                            'entity'          => $entity,
                        ],
                        'new'
                    )
                );
            } elseif ($valid && $this->isFormApplied($form)) {
                return $this->editAction($request, $entity->getId(), true);
            }
        }

        $delegateArgs = [
            'viewParameters' => [
                'permissionBase'  => $this->getPermissionBase(),
                'mauticContent'   => $this->getJsLoadMethodPrefix(),
                'actionRoute'     => $this->getActionRoute(),
                'indexRoute'      => $this->getIndexRoute(),
                'tablePrefix'     => $model->getRepository()->getTableAlias(),
                'modelName'       => $this->getModelName(),
                'translationBase' => $this->getTranslationBase(),
                'tmpl'            => $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index',
                'entity'          => $entity,
                'form'            => $this->getFormView($form, 'new'),
            ],
            'contentTemplate' => $this->getTemplateName('form.html.twig'),
            'passthroughVars' => [
                'mauticContent' => $this->getJsLoadMethodPrefix(),
                'route'         => $this->generateUrl(
                    $this->getActionRoute(),
                    [
                        'objectAction' => (!empty($valid) ? 'edit' : 'new'), // valid means a new form was applied
                        'objectId'     => ($entity) ? $entity->getId() : 0,
                    ]
                ),
                'validationError' => $this->getFormErrorForBuilder($form),
            ],
            'entity' => $entity,
            'form'   => $form,
        ];

        return $this->delegateView(
            $this->getViewArguments($delegateArgs, 'new')
        );
    }

    /**
     * @param string|null $name
     */
    protected function setListFilters($name = null)
    {
        return parent::setListFilters($name ?: $this->getSessionBase());
    }

    /**
     * @param string|null $logObject
     * @param string|null $logBundle
     * @param string|null $listPage
     * @param string      $itemName
     *
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    protected function viewStandard(Request $request, $objectId, $logObject = null, $logBundle = null, $listPage = null, $itemName = 'item')
    {
        $model    = $this->getModel($this->getModelName());
        $entity   = $model->getEntity($objectId);

        if (null === $entity) {
            $page = $request->getSession()->get('mautic.'.$this->getSessionBase().'.page', 1);

            return $this->postActionRedirect(
                $this->getPostActionRedirectArguments(
                    [
                        'returnUrl'       => $this->generateUrl($this->getIndexRoute(), ['page' => $page]),
                        'viewParameters'  => ['page' => $page],
                        'contentTemplate' => $this->getControllerBase().'::'.$this->getPostActionControllerAction('view').'Action',
                        'passthroughVars' => [
                            'mauticContent' => $this->getJsLoadMethodPrefix(),
                        ],
                        'flashes' => [
                            [
                                'type'    => 'error',
                                'msg'     => $this->getTranslatedString('error.notfound'),
                                'msgVars' => ['%id%' => $objectId],
                            ],
                        ],
                    ],
                    'view'
                )
            );
        } elseif (!$this->checkActionPermission('view', $entity)) {
            return $this->accessDenied();
        }

        $this->setListFilters();

        // Audit log entries
        $logs = ($logObject) ? $this->getModel('core.auditlog')->getLogForObject($logObject, $objectId, $entity->getDateAdded(), 10, $logBundle) : [];

        // Generate route
        $routeVars = [
            'objectAction' => 'view',
            'objectId'     => $entity->getId(),
        ];
        if (null !== $listPage) {
            $routeVars['listPage'] = $listPage;
        }
        $route = $this->generateUrl($this->getActionRoute(), $routeVars);

        $delegateArgs = [
            'viewParameters' => [
                $itemName     => $entity,
                'logs'        => $logs,
                'tmpl'        => $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index',
                'permissions' => $this->security->isGranted(
                    [
                        $this->getPermissionBase().':view',
                        $this->getPermissionBase().':viewown',
                        $this->getPermissionBase().':viewother',
                        $this->getPermissionBase().':create',
                        $this->getPermissionBase().':edit',
                        $this->getPermissionBase().':editown',
                        $this->getPermissionBase().':editother',
                        $this->getPermissionBase().':delete',
                        $this->getPermissionBase().':deleteown',
                        $this->getPermissionBase().':deleteother',
                        $this->getPermissionBase().':publish',
                        $this->getPermissionBase().':publishown',
                        $this->getPermissionBase().':publishother',
                    ],
                    'RETURN_ARRAY',
                    null,
                    true
                ),
            ],
            'contentTemplate' => $this->getTemplateName('details.html.twig'),
            'passthroughVars' => [
                'mauticContent' => $this->getJsLoadMethodPrefix(),
                'route'         => $route,
            ],
            'objectId' => $objectId,
            'entity'   => $entity,
        ];

        return $this->delegateView(
            $this->getViewArguments($delegateArgs, 'view')
        );
    }

    protected function getDataForExport(AbstractCommonModel $model, array $args, callable $resultsCallback = null, ?int $start = 0)
    {
        return parent::getDataForExport($model, $args, $resultsCallback, $start);
    }
}
