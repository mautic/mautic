<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Controller;

use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Model\FormModel;
use Symfony\Component\Form\Form;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * abstract StandardFormControllerInterface.
 */
abstract class AbstractStandardFormController extends AbstractFormController
{
    /**
     * Get this controller's model name.
     */
    abstract protected function getModelName();

    /**
     * Support non-index pages such as modal forms.
     *
     * @param string $route
     * @param array  $parameters
     * @param int    $referenceType
     *
     * @return bool|string
     */
    public function generateUrl($route, $parameters = [], $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        if (false === $route) {
            return false;
        }

        return parent::generateUrl($route, $parameters, $referenceType);
    }

    /**
     * Modify the cloned entity prior to sending through editAction.
     *
     * @param $newEntity
     * @param $entity
     *
     * @return array of arguments for editAction
     */
    protected function afterEntityClone($newEntity, $entity)
    {
        return [$newEntity, true];
    }

    /**
     * Called after the entity has been persisted allowing for custom preperation of $entity prior to viewAction.
     *
     * @param      $entity
     * @param Form $form
     * @param      $action
     * @param null $pass
     */
    protected function afterEntitySave($entity, Form $form, $action, $pass = null)
    {
    }

    /**
     * Called after the form is validated on POST.
     *
     * @param      $isValid
     * @param      $entity
     * @param Form $form
     * @param      $action
     * @param      $isClone
     */
    protected function afterFormProcessed($isValid, $entity, Form $form, $action, $isClone = false)
    {
    }

    /**
     * Deletes a group of entities.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function batchDeleteStandard()
    {
        $page      = $this->get('session')->get('mautic.'.$this->getSessionBase().'.page', 1);
        $returnUrl = $this->generateUrl($this->getIndexRoute(), ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => $this->getControllerBase().':'.$this->getPostActionControllerAction('batchDelete'),
            'passthroughVars' => [
                'mauticContent' => $this->getJsLoadMethodPrefix(),
            ],
        ];

        if ($this->request->getMethod() == 'POST') {
            $model     = $this->getModel($this->getModelName());
            $ids       = json_decode($this->request->query->get('ids', ''));
            $deleteIds = [];

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $model->getEntity($objectId);

                if ($entity === null) {
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
        } //else don't do anything

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
     * @param $entity
     * @param $form
     * @param $action
     * @param $objectId
     * @param $isClone
     *
     * @return mixed Whatever is returned will be passed into afterEntitySave; pass false to fail validation
     */
    protected function beforeEntitySave($entity, Form $form, $action, $objectId = null, $isClone = false)
    {
        return true;
    }

    /**
     * Do anything necessary before the form is checked for POST and processed.
     *
     * @param      $entity
     * @param Form $form
     * @param      $action
     * @param      $isPost
     * @param $objectId
     * @param   $isClone
     */
    protected function beforeFormProcessed($entity, Form $form, $action, $isPost, $objectId = null, $isClone = false)
    {
    }

    /**
     * @param      $action
     * @param null $entity
     * @param null $objectId
     *
     * @return bool|mixed
     */
    protected function checkActionPermission($action, $entity = null, $objectId = null)
    {
        $security = $this->get('mautic.security');
        if ($entity) {
            $permissionUser = method_exists($entity, 'getPermissionUser') ? $entity->getPermissionUser() : $entity->getCreatedBy();
        }

        if ($entity) {
            switch ($action) {
                case 'new':
                    return $security->isGranted($this->getPermissionBase().':create');
                case 'view':
                case 'index':
                    return ($entity) ? $security->hasEntityAccess(
                        $this->getPermissionBase().':viewown',
                        $this->getPermissionBase().':viewother',
                        $permissionUser
                    ) : $security->isGranted($this->getPermissionBase().':view');
                case 'clone':
                    return
                        $security->isGranted($this->getPermissionBase().':create')
                        && $this->get('mautic.security')->hasEntityAccess(
                            $this->getPermissionBase().':viewown',
                            $this->getPermissionBase().':viewother',
                            $permissionUser
                        );
                case 'delete':
                case 'batchDelete':
                    return $this->get('mautic.security')->hasEntityAccess(
                        $this->getPermissionBase().':deleteown',
                        $this->getPermissionBase().':deleteother',
                        $permissionUser
                    );
                default:
                    return $this->get('mautic.security')->hasEntityAccess(
                        $this->getPermissionBase().':'.$action.'own',
                        $this->getPermissionBase().':'.$action.'other',
                        $permissionUser
                    );
            }
        } else {
            switch ($action) {
                case 'new':
                    return $security->isGranted($this->getPermissionBase().':create');
                case 'view':
                case 'index':
                    return $security->isGranted($this->getPermissionBase().':view');
                case 'clone':
                    return
                        $security->isGranted($this->getPermissionBase().':create')
                        && $security->isGranted($this->getPermissionBase().':view');
                case 'delete':
                case 'batchDelete':
                    return $security->isGranted($this->getPermissionBase().':delete');
                default:
                    return $security->isGranted($this->getPermissionBase().':'.$action);
            }
        }

        return false;
    }

    /**
     * Clone an entity.
     *
     * @param   $objectId
     *
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function cloneStandard($objectId)
    {
        $model  = $this->getModel($this->getModelName());
        $entity = $model->getEntity($objectId);

        if ($entity != null) {
            if (!$this->checkActionPermission('clone', $entity)) {
                return $this->accessDenied();
            }

            $newEntity = clone $entity;

            if ($arguments = $this->afterEntityClone($newEntity, $entity)) {
                return call_user_func_array([$this, 'editAction'], $arguments);
            } else {
                return $this->editAction($newEntity, true);
            }
        }

        return $this->newAction();
    }

    /**
     * Deletes the entity.
     *
     * @param int $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function deleteStandard($objectId)
    {
        $page      = $this->get('session')->get('mautic.'.$this->getSessionBase().'.page', 1);
        $returnUrl = $this->generateUrl($this->getIndexRoute(), ['page' => $page]);
        $flashes   = [];
        $model     = $this->getModel($this->getModelName());
        $entity    = $model->getEntity($objectId);

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => $this->getControllerBase().':'.$this->getPostActionControllerAction('delete'),
            'passthroughVars' => [
                'mauticContent' => $this->getJsLoadMethodPrefix(),
            ],
            'entity' => $entity,
        ];

        if ($this->request->getMethod() == 'POST') {
            if ($entity === null) {
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

            $identifier = $this->get('translator')->trans($entity->getName());
            $flashes[]  = [
                'type'    => 'notice',
                'msg'     => 'mautic.core.notice.deleted',
                'msgVars' => [
                    '%name%' => $identifier,
                    '%id%'   => $objectId,
                ],
            ];
        } //else don't do anything

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
     * @param      $objectId
     * @param bool $ignorePost
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     *
     * @throws \Exception
     */
    protected function editStandard($objectId, $ignorePost = false)
    {
        $isClone = false;
        $model   = $this->getModel($this->getModelName());
        if (!$model instanceof FormModel) {
            throw new \Exception(get_class($model).' must extend '.FormModel::class);
        }

        $entity = $this->getFormEntity('edit', $objectId, $isClone);

        //set the return URL
        $returnUrl      = $this->generateUrl($this->getIndexRoute());
        $page           = $this->get('session')->get('mautic.'.$this->getSessionBase().'.page', 1);
        $viewParameters = ['page' => $page];

        $template = $this->getControllerBase().':'.$this->getPostActionControllerAction('edit');

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => $viewParameters,
            'contentTemplate' => $template,
            'passthroughVars' => [
                'mauticContent' => $this->getJsLoadMethodPrefix(),
            ],
            'entity' => $entity,
        ];

        //form not found
        if ($entity === null) {
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
        } elseif (!$this->checkActionPermission('edit', $entity)) {
            return $this->accessDenied();
        } elseif (!$isClone && $model->isLocked($entity)) {
            //deny access if the entity is locked
            return $this->isLocked($postActionVars, $entity, $this->getModelName());
        }

        $options = $this->getEntityFormOptions();
        $action  = $this->generateUrl($this->getActionRoute(), ['objectAction' => 'edit', 'objectId' => $objectId]);
        $form    = $model->createForm($entity, $this->get('form.factory'), $action, $options);

        $isPost = !$ignorePost && $this->request->getMethod() == 'POST';
        $this->beforeFormProcessed($entity, $form, 'edit', $isPost, $objectId, $isClone);

        ///Check for a submitted form and process it
        if ($isPost) {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    if ($valid = $this->beforeEntitySave($entity, $form, 'edit', $objectId, $isClone)) {
                        $model->saveEntity($entity, $form->get('buttons')->get('save')->isClicked());

                        $this->afterEntitySave($entity, $form, 'edit', $valid);

                        $this->addFlash(
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
                            $postActionVars['contentTemplate'] = $this->getControllerBase().':view';
                        }
                    }

                    $this->afterFormProcessed($valid, $entity, $form, 'edit', $isClone);
                }
            } else {
                if (!$isClone) {
                    //unlock the entity
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
                $form   = $model->createForm($entity, $this->get('form.factory'), $action);
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
                'tmpl'            => $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index',
                'entity'          => $entity,
                'form'            => $this->getFormView($form, 'edit'),
            ],
            'contentTemplate' => $this->getTemplateName('form.html.php'),
            'passthroughVars' => [
                'mauticContent' => $this->getJsLoadMethodPrefix(),
                'route'         => $this->generateUrl(
                    $this->getActionRoute(),
                    [
                        'objectAction' => 'edit',
                        'objectId'     => $entity->getId(),
                    ]
                ),
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
        return 'MauticCoreBundle:Standard';
    }

    /**
     * @param      $objectId
     * @param      $action
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
                } elseif (strpos($objectId, 'mautic_') !== false) {
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
     *
     * @param Form $form
     * @param      $action
     *
     * @return \Symfony\Component\Form\FormView
     */
    protected function getFormView(Form $form, $action)
    {
        return $form->createView();
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
     *
     * @param array $args
     * @param       $action
     *
     * @return array
     */
    protected function getPostActionRedirectArguments(array $args, $action)
    {
        return $args;
    }

    /**
     * @param $action
     *
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
     * @param null $objectId
     *
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
     * Get template base different than MauticCoreBundle:Standard.
     *
     * @return string
     */
    protected function getTemplateBase()
    {
        return 'MauticCoreBundle:Standard';
    }

    /**
     * Get the template file.
     *
     * @param $file
     *
     * @return string
     */
    protected function getTemplateName($file)
    {
        if ($this->get('templating')->exists($this->getControllerBase().':'.$file)) {
            return $this->getControllerBase().':'.$file;
        } elseif ($this->get('templating')->exists($this->getTemplateBase().':'.$file)) {
            return $this->getTemplateBase().':'.$file;
        } else {
            return 'MauticCoreBundle:Standard:'.$file;
        }
    }

    /**
     * Get custom or core translation.
     *
     * @param $string
     *
     * @return string
     */
    protected function getTranslatedString($string)
    {
        return $this->get('translator')->hasId($this->getTranslationBase().'.'.$string) ? $this->getTranslationBase()
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
     *
     * @param array $args
     * @param       $action
     *
     * @return array
     */
    protected function getViewArguments(array $args, $action)
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
    protected function getUpdateSelectParams($updateSelect, $entity, $nameMethod = 'getName', $groupMethod = null)
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
     * @param        $objectId
     * @param        $returnUrl
     * @param string $timezone
     * @param null   $dateRangeForm
     *
     * @return array
     */
    protected function getViewDateRange($objectId, $returnUrl, $timezone = 'local', &$dateRangeForm = null)
    {
        $name            = $this->getSessionBase($objectId).'.view.daterange';
        $method          = ('POST' === $this->request->getMethod()) ? 'request' : 'query';
        $dateRangeValues = $this->request->$method->get('daterange', $this->get('session')->get($name, []));
        $this->get('session')->set($name, $dateRangeValues);

        $dateRangeForm = $this->get('form.factory')->create('daterange', $dateRangeValues, ['action' => $returnUrl]);
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
    protected function indexStandard($page = null)
    {
        //set some permissions
        $permissions = $this->get('mautic.security')->isGranted(
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

        if ($this->request->getMethod() == 'POST') {
            $this->setListFilters();
        }

        $session = $this->get('session');
        if (empty($page)) {
            $page = $session->get('mautic.'.$this->getSessionBase().'.page', 1);
        }

        //set limits
        $limit = $session->get('mautic.'.$this->getSessionBase().'.limit', $this->coreParametersHelper->getParameter('default_pagelimit'));
        $start = ($page === 1) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search = $this->request->get('search', $session->get('mautic.'.$this->getSessionBase().'.filter', ''));
        $session->set('mautic.'.$this->getSessionBase().'.filter', $search);

        $filter = ['string' => $search, 'force' => []];

        $model = $this->getModel($this->getModelName());
        $repo  = $model->getRepository();

        if (!$permissions[$this->getPermissionBase().':viewother']) {
            $filter['force'] = ['column' => $repo->getTableAlias().'.createdBy', 'expr' => 'eq', 'value' => $this->user->getId()];
        }

        $orderBy    = $session->get('mautic.'.$this->getSessionBase().'.orderby', $repo->getTableAlias().'.name');
        $orderByDir = $session->get('mautic.'.$this->getSessionBase().'.orderbydir', 'ASC');

        list($count, $items) = $this->getIndexItems($start, $limit, $filter, $orderBy, $orderByDir);

        if ($count && $count < ($start + 1)) {
            //the number of entities are now less then the current page so redirect to the last page
            $lastPage = ($count === 1) ? 1 : ((ceil($count / $limit)) ?: 1) ?: 1;

            $session->set('mautic.'.$this->getSessionBase().'.page', $lastPage);
            $returnUrl = $this->generateUrl($this->getIndexRoute(), ['page' => $lastPage]);

            return $this->postActionRedirect(
                $this->getPostActionRedirectArguments(
                    [
                        'returnUrl'       => $returnUrl,
                        'viewParameters'  => ['page' => $lastPage],
                        'contentTemplate' => $this->getControllerBase().':'.$this->getPostActionControllerAction('index'),
                        'passthroughVars' => [
                            'mauticContent' => $this->getJsLoadMethodPrefix(),
                        ],
                    ],
                    'index'
                )
            );
        }

        //set what page currently on so that we can return here after form submission/cancellation
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
            'tmpl'            => $this->request->get('tmpl', 'index'),
        ];

        return $this->delegateView(
            $this->getViewArguments(
                [
                    'viewParameters'  => $viewParameters,
                    'contentTemplate' => $this->getTemplateName('list.html.php'),
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
    protected function newStandard()
    {
        $entity = $this->getFormEntity('new');

        if (!$this->checkActionPermission('new')) {
            return $this->accessDenied();
        }

        $model = $this->getModel($this->getModelName());
        if (!$model instanceof FormModel) {
            throw new \Exception(get_class($model).' must extend '.FormModel::class);
        }

        //set the page we came from
        $page = $this->get('session')->get('mautic.'.$this->getSessionBase().'.page', 1);

        $options = $this->getEntityFormOptions();
        $action  = $this->generateUrl($this->getActionRoute(), ['objectAction' => 'new']);
        $form    = $model->createForm($entity, $this->get('form.factory'), $action, $options);

        ///Check for a submitted form and process it
        $isPost = $this->request->getMethod() === 'POST';
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
                            $template       = $this->getControllerBase().':view';
                        } else {
                            $viewParameters = ['page' => $page];
                            $returnUrl      = $this->generateUrl($this->getIndexRoute(), $viewParameters);
                            $template       = $this->getControllerBase().':'.$this->getPostActionControllerAction('new');
                        }
                    }
                }

                $this->afterFormProcessed($valid, $entity, $form, 'new');
            } else {
                $viewParameters = ['page' => $page];
                $returnUrl      = $this->generateUrl($this->getIndexRoute(), $viewParameters);
                $template       = $this->getControllerBase().':'.$this->getPostActionControllerAction('new');
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
                return $this->editAction($entity->getId(), true);
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
                'tmpl'            => $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index',
                'entity'          => $entity,
                'form'            => $this->getFormView($form, 'new'),
            ],
            'contentTemplate' => $this->getTemplateName('form.html.php'),
            'passthroughVars' => [
                'mauticContent' => $this->getJsLoadMethodPrefix(),
                'route'         => $this->generateUrl(
                    $this->getActionRoute(),
                    [
                        'objectAction' => (!empty($valid) ? 'edit' : 'new'), //valid means a new form was applied
                        'objectId'     => ($entity) ? $entity->getId() : 0,
                    ]
                ),
            ],
            'entity' => $entity,
            'form'   => $form,
        ];

        return $this->delegateView(
            $this->getViewArguments($delegateArgs, 'new')
        );
    }

    /**
     * @param null $name
     */
    protected function setListFilters($name = null)
    {
        return parent::setListFilters(($name) ? $name : $this->getSessionBase());
    }

    /**
     * @param        $objectId
     * @param null   $logObject
     * @param null   $logBundle
     * @param null   $listPage
     * @param string $itemName
     *
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    protected function viewStandard($objectId, $logObject = null, $logBundle = null, $listPage = null, $itemName = 'item')
    {
        $model    = $this->getModel($this->getModelName());
        $entity   = $model->getEntity($objectId);
        $security = $this->get('mautic.security');

        if ($entity === null) {
            $page = $this->get('session')->get('mautic.'.$this->getSessionBase().'.page', 1);

            return $this->postActionRedirect(
                $this->getPostActionRedirectArguments(
                    [
                        'returnUrl'       => $this->generateUrl($this->getIndexRoute(), ['page' => $page]),
                        'viewParameters'  => ['page' => $page],
                        'contentTemplate' => $this->getControllerBase().':'.$this->getPostActionControllerAction('view'),
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

        // Set filters
        if ($this->request->getMethod() == 'POST') {
            $this->setListFilters();
        }

        // Audit log entries
        $logs = ($logObject) ? $this->getModel('core.auditLog')->getLogForObject($logObject, $objectId, $entity->getDateAdded(), 10, $logBundle) : [];

        // Generate route
        $routeVars = [
            'objectAction' => 'view',
            'objectId'     => $entity->getId(),
        ];
        if ($listPage !== null) {
            $routeVars['listPage'] = $listPage;
        }
        $route = $this->generateUrl($this->getActionRoute(), $routeVars);

        $delegateArgs = [
            'viewParameters' => [
                $itemName     => $entity,
                'logs'        => $logs,
                'tmpl'        => $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index',
                'permissions' => $security->isGranted(
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
            'contentTemplate' => $this->getTemplateName('details.html.php'),
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
}
