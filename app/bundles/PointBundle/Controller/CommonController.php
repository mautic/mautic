<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
//@todo - add support to editing more than one form at a time (i.e. opened in different tabs)

namespace Mautic\PointBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CommonController extends FormController
{

    protected $permissionName;
    protected $modelName;
    protected $sessionVar;
    protected $translationVar;
    protected $routerVar;
    protected $templateVar;
    protected $actionVar;
    protected $mauticContent;
    protected $tableAlias;

    /**
     * @param int    $page
     * @param string $view
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($page = 1)
    {

        //set some permissions
        $permissions = $this->factory->getSecurity()->isGranted(array(
            'point:'.$this->permissionName.':viewown',
            'point:'.$this->permissionName.':viewother',
            'point:'.$this->permissionName.':create',
            'point:'.$this->permissionName.':editown',
            'point:'.$this->permissionName.':editother',
            'point:'.$this->permissionName.':deleteown',
            'point:'.$this->permissionName.':deleteother',
            'point:'.$this->permissionName.':publishown',
            'point:'.$this->permissionName.':publishother'

        ), "RETURN_ARRAY");

        if (!$permissions['point:'.$this->permissionName.':viewown'] && !$permissions['point:'.$this->permissionName.':viewother']) {
            return $this->accessDenied();
        }

        //set limits
        $limit = $this->factory->getSession()->get('mautic.'.$this->sessionVar.'.limit', $this->factory->getParameter('default_pagelimit'));
        $start = ($page === 1) ? 0 : (($page-1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search = $this->request->get('search', $this->factory->getSession()->get('mautic.'.$this->sessionVar.'.filter', ''));
        $this->factory->getSession()->set('mautic.'.$this->sessionVar.'.filter', $search);

        $filter      = array('string' => $search, 'force' => array());

        if (!$permissions['point:'.$this->permissionName.':viewother']) {
            $filter['force'] =
                array('column' => $this->tableAlias.'.createdBy', 'expr' => 'eq', 'value' => $this->factory->getUser());
        }

        $orderBy     = $this->factory->getSession()->get('mautic.'.$this->sessionVar.'.orderby', $this->tableAlias.'.name');
        $orderByDir  = $this->factory->getSession()->get('mautic.'.$this->sessionVar.'.orderbydir', 'ASC');

        $forms = $this->factory->getModel($this->modelName)->getEntities(
            array(
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir
            ));

        $count = count($forms);
        if ($count && $count < ($start + 1)) {
            //the number of entities are now less then the current page so redirect to the last page
            if ($count === 1) {
                $lastPage = 1;
            } else {
                $lastPage = (floor($limit / $count)) ? : 1;
            }
            $this->factory->getSession()->set('mautic.'.$this->sessionVar.'.page', $lastPage);
            $returnUrl   = $this->generateUrl('mautic_'.$this->routerVar.'_index', array('page' => $lastPage));

            return $this->postActionRedirect(array(
                'returnUrl'       => $returnUrl,
                'viewParameters'  => array('page' => $lastPage),
                'contentTemplate' => 'MauticPointBundle:'.$this->templateVar.':index',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_'.$this->routerVar.'_index',
                    'mauticContent' => $this->mauticContent
                )
            ));
        }

        //set what page currently on so that we can return here after form submission/cancellation
        $this->factory->getSession()->set('mautic.'.$this->sessionVar.'.page', $page);

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        return $this->delegateView(array(
            'viewParameters'  => array(
                'searchValue' => $search,
                'items'       => $forms,
                'page'        => $page,
                'limit'       => $limit,
                'permissions' => $permissions,
                'tmpl'        => $tmpl,
                'security'    => $this->factory->getSecurity()
            ),
            'contentTemplate' => 'MauticPointBundle:'.$this->templateVar.':list.html.php',
            'passthroughVars' => array(
                'activeLink'     => '#mautic_'.$this->routerVar.'_index',
                'mauticContent'  => $this->mauticContent,
                'route'          => $this->generateUrl('mautic_'.$this->routerVar.'_index', array('page' => $page)),
                'replaceContent' => ($tmpl == 'list') ? 'true' : 'false'
            )
        ));
    }

    /**
     * View a specific point
     *
     * @param $objectId
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function viewAction($objectId)
    {
        $entity  = $this->factory->getModel($this->modelName)->getEntity($objectId);
        //set the page we came from
        $page        = $this->factory->getSession()->get('mautic.'.$this->sessionVar.'.page', 1);

        if ($entity === null) {
            //set the return URL
            $returnUrl  = $this->generateUrl('mautic_'.$this->routerVar.'_index', array('page' => $page));

            return $this->postActionRedirect(array(
                'returnUrl'       => $returnUrl,
                'viewParameters'  => array('page' => $page),
                'contentTemplate' => 'MauticPointBundle:'.$this->templateVar.':index',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_'.$this->routerVar.'_index',
                    'mauticContent' => $this->mauticContent
                ),
                'flashes'         =>array(
                    array(
                        'type' => 'error',
                        'msg'  => 'mautic.'.$this->sessionVar.'.error.notfound',
                        'msgVars' => array('%id%' => $objectId)
                    )
                )
            ));
        } elseif (!$this->factory->getSecurity()->hasEntityAccess(
            'point:'.$this->permissionName.':viewown', 'point:'.$this->permissionName.':viewother', $entity->getCreatedBy()
        )) {
            return $this->accessDenied();
        }

        $permissions = $this->factory->getSecurity()->isGranted(array(
            'point:'.$this->permissionName.':viewown',
            'point:'.$this->permissionName.':viewother',
            'point:'.$this->permissionName.':create',
            'point:'.$this->permissionName.':editown',
            'point:'.$this->permissionName.':editother',
            'point:'.$this->permissionName.':deleteown',
            'point:'.$this->permissionName.':deleteother',
            'point:'.$this->permissionName.':publishown',
            'point:'.$this->permissionName.':publishother'

        ), "RETURN_ARRAY");

        return $this->delegateView(array(
            'viewParameters'  => array(
                'entity'      => $entity,
                'page'        => $page,
                'permissions' => $permissions,
                'security'    => $this->factory->getSecurity()
            ),
            'contentTemplate' => 'MauticPointBundle:'.$this->templateVar.':details.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_'.$this->routerVar.'_index',
                'mauticContent' => $this->mauticContent,
                'route'         => $this->generateUrl('mautic_'.$this->routerVar.'_action', array(
                        'objectAction' => 'view',
                        'objectId'     => $entity->getId())
                )
            )
        ));
        return $this->indexAction($page, 'view', $entity);
    }

    /**
     * Generates new form and processes post data
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction ()
    {
        $model   = $this->factory->getModel($this->modelName);
        $entity  = $model->getEntity();
        $session = $this->factory->getSession();

        if (!$this->factory->getSecurity()->isGranted('point:'.$this->permissionName.':create')) {
            return $this->accessDenied();
        }

        //set the page we came from
        $page   = $this->factory->getSession()->get('mautic.'.$this->sessionVar.'.page', 1);

        //set added/updated actions
        $addActions     = $session->get('mautic.'.$this->actionVar.'.add', array());
        $deletedActions = $session->get('mautic.'.$this->actionVar.'.remove', array());

        $action = $this->generateUrl('mautic_'.$this->routerVar.'_action', array('objectAction' => 'new'));
        $form   = $model->createForm($entity, $this->get('form.factory'), $action);

        ///Check for a submitted form and process it
        if ($this->request->getMethod() == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    //only save actions that are not to be deleted
                    $actions  = array_diff_key($addActions, array_flip($deletedActions));

                    //make sure that at least one action is selected
                    if ($this->modelName == 'point' && empty($actions)) {
                        //set the error
                        $form->addError(new FormError(
                            $this->get('translator')->trans('mautic.point.form.actions.notempty', array(), 'validators')
                        ));
                        $valid = false;
                    } else {
                        $model->setActions($entity, $actions);

                        //form is valid so process the data
                        $model->saveEntity($entity);

                        $this->request->getSession()->getFlashBag()->add(
                            'notice',
                            $this->get('translator')->trans('mautic.'.$this->translationVar.'.notice.created', array(
                                '%name%' => $entity->getName(),
                                '%url%'          => $this->generateUrl('mautic_'.$this->routerVar.'_action', array(
                                    'objectAction' => 'edit',
                                    'objectId'     => $entity->getId()
                                ))
                            ), 'flashes')
                        );

                        if ($form->get('buttons')->get('save')->isClicked()) {
                            $viewParameters = array(
                                'objectAction' => 'view',
                                'objectId'     => $entity->getId()
                            );
                            $returnUrl      = $this->generateUrl('mautic_'.$this->routerVar.'_action', $viewParameters);
                            $template       = 'MauticPointBundle:'.$this->templateVar.':view';
                        } else {
                            //return edit view so that all the session stuff is loaded
                            return $this->editAction($entity->getId(), true);
                        }
                    }
                }
            } else {
                $viewParameters  = array('page' => $page);
                $returnUrl = $this->generateUrl('mautic_'.$this->routerVar.'_index', $viewParameters);
                $template  = 'MauticPointBundle:'.$this->templateVar.':index';
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                //clear temporary fields
                $this->clearSessionComponents();

                return $this->postActionRedirect(array(
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => $viewParameters,
                    'contentTemplate' => $template,
                    'passthroughVars' => array(
                        'activeLink'    => '#mautic_'.$this->routerVar.'_index',
                        'mauticContent' => $this->mauticContent
                    )
                ));
            }
        } else {
            //clear out existing fields in case the form was refreshed, browser closed, etc
            $this->clearSessionComponents();
        }

        //fire the form builder event
        $customComponents = $model->getCustomComponents();

        return $this->delegateView(array(
            'viewParameters'  => array(
                'actions'        => $customComponents['actions'],
                'pointActions'   => $addActions,
                'deletedActions' => $deletedActions,
                'tmpl'           => $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index',
                'entity'         => $entity,
                'form'           => $form->createView()
            ),
            'contentTemplate' => 'MauticPointBundle:'.$this->templateVar.'Builder:components.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_'.$this->routerVar.'_index',
                'mauticContent' => $this->mauticContent,
                'route'         => $this->generateUrl('mautic_'.$this->routerVar.'_action', array(
                    'objectAction' => (!empty($valid) ? 'edit' : 'new'), //valid means a new form was applied
                    'objectId'     => $entity->getId())
                )
            )
        ));
    }

    /**
     * Generates edit form and processes post data
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction ($objectId, $ignorePost = false)
    {
        $model      = $this->factory->getModel($this->modelName);
        $entity     = $model->getEntity($objectId);
        $session    = $this->factory->getSession();
        $cleanSlate = true;
        //set the page we came from
        $page    = $this->factory->getSession()->get('mautic.'.$this->sessionVar.'.page', 1);

        //set the return URL
        $returnUrl  = $this->generateUrl('mautic_'.$this->routerVar.'_index', array('page' => $page));

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => array('page' => $page),
            'contentTemplate' => 'MauticPointBundle:'.$this->templateVar.':index',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_'.$this->routerVar.'_index',
                'mauticContent' => $this->mauticContent
            )
        );
        //form not found
        if ($entity === null) {
            return $this->postActionRedirect(
                array_merge($postActionVars, array(
                    'flashes' => array(
                        array(
                            'type' => 'error',
                            'msg'  => 'mautic.'.$this->sessionVar.'.error.notfound',
                            'msgVars' => array('%id%' => $objectId)
                        )
                    )
                ))
            );
        } elseif (!$this->factory->getSecurity()->hasEntityAccess(
            'point:'.$this->permissionName.':editown', 'point:'.$this->permissionName.':editother', $entity->getCreatedBy()
        )) {
            return $this->accessDenied();
        } elseif ($model->isLocked($entity)) {
            //deny access if the entity is locked
            return $this->isLocked($postActionVars, $entity, $this->modelName);
        }

        $action = $this->generateUrl('mautic_'.$this->routerVar.'_action', array('objectAction' => 'edit', 'objectId' => $objectId));
        $form   = $model->createForm($entity, $this->get('form.factory'), $action);

        ///Check for a submitted form and process it
        if (!$ignorePost && $this->request->getMethod() == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                //set added/updated actions
                $addActions     = $session->get('mautic.'.$this->actionVar.'.add', array());
                $deletedActions = $session->get('mautic.'.$this->actionVar.'.remove', array());
                $actions        = array_diff_key($addActions, array_flip($deletedActions));

                if ($valid = $this->isFormValid($form)) {
                    //make sure that at least one field is selected
                    if ($this->modelName == 'point' && empty($addActions)) {
                        //set the error
                        $form->addError(new FormError(
                            $this->get('translator')->trans('mautic.point.form.actions.notempty', array(), 'validators')
                        ));
                        $valid = false;
                    } else {
                        $model->setActions($entity, $actions);

                        //form is valid so process the data
                        $model->saveEntity($entity, $form->get('buttons')->get('save')->isClicked());

                        //delete entities
                        $this->factory->getModel('form.action')->deleteEntities($deletedActions);

                        $this->request->getSession()->getFlashBag()->add(
                            'notice',
                            $this->get('translator')->trans('mautic.'.$this->translationVar.'.notice.updated', array(
                                '%name%' => $entity->getName(),
                                '%url%'  => $this->generateUrl('mautic_'.$this->routerVar.'_action', array(
                                    'objectAction' => 'edit',
                                    'objectId'     => $entity->getId()
                                ))
                            ), 'flashes')
                        );

                        if ($form->get('buttons')->get('save')->isClicked()) {
                            $viewParameters = array(
                                'objectAction' => 'view',
                                'objectId'     => $entity->getId()
                            );
                            $returnUrl      = $this->generateUrl('mautic_'.$this->routerVar.'_action', $viewParameters);
                            $template       = 'MauticPointBundle:'.$this->templateVar.':view';
                        }
                    }
                }
            } else {
                //unlock the entity
                $model->unlockEntity($entity);

                $viewParameters  = array('page' => $page);
                $returnUrl = $this->generateUrl('mautic_'.$this->routerVar.'_index', $viewParameters);
                $template  = 'MauticPointBundle:'.$this->templateVar.':index';
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                //remove fields from session
                $this->clearSessionComponents();

                return $this->postActionRedirect(
                    array_merge($postActionVars, array(
                        'returnUrl'       => $returnUrl,
                        'viewParameters'  => $viewParameters,
                        'contentTemplate' => $template
                    ))
                );
            } elseif ($form->get('buttons')->get('apply')->isClicked()) {
                //rebuild everything to include new ids
                $cleanSlate = true;
            }
        } else {
            $cleanSlate = true;

            //lock the entity
            $model->lockEntity($entity);
        }

        if ($cleanSlate) {
            //clean slate
            $this->clearSessionComponents();

            //load existing actions into session
            $pointActions     = array();
            $existingActions = $entity->getActions()->toArray();
            foreach ($existingActions as $a) {
                $id     = $a->getId();
                $action = $a->convertToArray();
                unset($action['form']);
                $pointActions[$id] = $action;
            }
            $session->set('mautic.'.$this->actionVar.'.add', $pointActions);
            $deletedActions = array();
        }

        $customComponents = $model->getCustomComponents();

        return $this->delegateView(array(
            'viewParameters'  => array(
                'actions'        => $customComponents['actions'],
                'pointActions'   => $pointActions,
                'deletedActions' => $deletedActions,
                'tmpl'           => $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index',
                'entity'         => $entity,
                'form'           => $form->createView()
            ),
            'contentTemplate' => 'MauticPointBundle:'.$this->templateVar.'Builder:components.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_'.$this->routerVar.'_index',
                'mauticContent' => $this->mauticContent,
                'route'         => $this->generateUrl('mautic_'.$this->routerVar.'_action', array(
                    'objectAction' => 'edit',
                    'objectId'     => $entity->getId())
                )
            )
        ));
    }

    /**
     * Clone an entity
     *
     * @param $objectId
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function cloneAction ($objectId)
    {
        $model   = $this->factory->getModel($this->modelName);
        $entity  = $model->getEntity($objectId);

        if ($entity != null) {
            if (!$this->factory->getSecurity()->isGranted('point:'.$this->permissionName.':create') ||
                !$this->factory->getSecurity()->hasEntityAccess(
                    'point:'.$this->permissionName.':viewown', 'point:'.$this->permissionName.':viewother', $entity->getCreatedBy()
                )
            ) {
                return $this->accessDenied();
            }

            $clone = clone $entity;
            $clone->setIsPublished(false);
            $model->saveEntity($clone);
            $objectId = $clone->getId();
        }

        return $this->editAction($objectId);
    }

    /**
     * Deletes the entity
     *
     * @param         $objectId
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction($objectId) {
        $page        = $this->factory->getSession()->get('mautic.'.$this->sessionVar.'.page', 1);
        $returnUrl   = $this->generateUrl('mautic_'.$this->routerVar.'_index', array('page' => $page));
        $flashes     = array();

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => array('page' => $page),
            'contentTemplate' => 'MauticPointBundle:'.$this->templateVar.':index',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_'.$this->routerVar.'_index',
                'mauticContent' => $this->mauticContent
            )
        );

        if ($this->request->getMethod() == 'POST') {
            $model  = $this->factory->getModel($this->modelName);
            $entity = $model->getEntity($objectId);

            if ($entity === null) {
                $flashes[] = array(
                    'type'    => 'error',
                    'msg'     => 'mautic.'.$this->sessionVar.'.error.notfound',
                    'msgVars' => array('%id%' => $objectId)
                );
            } elseif (!$this->factory->getSecurity()->hasEntityAccess(
                'point:'.$this->permissionName.':deleteown', 'point:'.$this->permissionName.':deleteother', $entity->getCreatedBy()
            )) {
                return $this->accessDenied();
            } elseif ($model->isLocked($entity)) {
                return $this->isLocked($postActionVars, $entity, $this->modelName);
            }

            $model->deleteEntity($entity);

            $identifier = $this->get('translator')->trans($entity->getName());
            $flashes[] = array(
                'type' => 'notice',
                'msg'  => 'mautic.'.$this->sessionVar.'.notice.deleted',
                'msgVars' => array(
                    '%name%' => $identifier,
                    '%id%'   => $objectId
                )
            );
        } //else don't do anything

        return $this->postActionRedirect(
            array_merge($postActionVars, array(
                'flashes' => $flashes
            ))
        );
    }

    /**
     * Clear field and actions from the session
     */
    public function clearSessionComponents()
    {
        $session = $this->factory->getSession();
        $session->remove('mautic.'.$this->actionVar.'.add');
        $session->remove('mautic.'.$this->actionVar.'.remove');
    }
}