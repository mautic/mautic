<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class PointController extends FormController
{
    /**
     * @param int    $page
     * @param string $view
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($page = 1)
    {

        //set some permissions
        $permissions = $this->factory->getSecurity()->isGranted(array(
            'point:points:view',
            'point:points:create',
            'point:points:edit',
            'point:points:delete',
            'point:points:publish'

        ), "RETURN_ARRAY");

        if (!$permissions['point:points:view']) {
            return $this->accessDenied();
        }

        if ($this->request->getMethod() == 'POST') {
            $this->setTableOrder();
        }

        //set limits
        $limit = $this->factory->getSession()->get('mautic.point.limit', $this->factory->getParameter('default_pagelimit'));
        $start = ($page === 1) ? 0 : (($page-1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search = $this->request->get('search', $this->factory->getSession()->get('mautic.point.filter', ''));
        $this->factory->getSession()->set('mautic.point.filter', $search);

        $filter      = array('string' => $search, 'force' => array());
        $orderBy     = $this->factory->getSession()->get('mautic.point.orderby', 'p.name');
        $orderByDir  = $this->factory->getSession()->get('mautic.point.orderbydir', 'ASC');

        $points = $this->factory->getModel('point')->getEntities(
            array(
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir
            ));

        $count = count($points);
        if ($count && $count < ($start + 1)) {
            //the number of entities are now less then the current page so redirect to the last page
            if ($count === 1) {
                $lastPage = 1;
            } else {
                $lastPage = (floor($limit / $count)) ? : 1;
            }
            $this->factory->getSession()->set('mautic.point.page', $lastPage);
            $returnUrl   = $this->generateUrl('mautic_point_index', array('page' => $lastPage));

            return $this->postActionRedirect(array(
                'returnUrl'       => $returnUrl,
                'viewParameters'  => array('page' => $lastPage),
                'contentTemplate' => 'MauticPointBundle:Point:index',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_point_index',
                    'mauticContent' => 'point'
                )
            ));
        }

        //set what page currently on so that we can return here after form submission/cancellation
        $this->factory->getSession()->set('mautic.point.page', $page);

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        //get the list of actions
        $actions = $this->factory->getModel('point')->getPointActions();

        return $this->delegateView(array(
            'viewParameters'  => array(
                'searchValue' => $search,
                'items'       => $points,
                'actions'     => $actions['actions'],
                'page'        => $page,
                'limit'       => $limit,
                'permissions' => $permissions,
                'tmpl'        => $tmpl
            ),
            'contentTemplate' => 'MauticPointBundle:Point:list.html.php',
            'passthroughVars' => array(
                'activeLink'     => '#mautic_point_index',
                'mauticContent'  => 'point',
                'route'          => $this->generateUrl('mautic_point_index', array('page' => $page)),
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
        $entity  = $this->factory->getModel('point')->getEntity($objectId);
        //set the page we came from
        $page        = $this->factory->getSession()->get('mautic.point.page', 1);

        $permissions = $this->factory->getSecurity()->isGranted(array(
            'point:points:view',
            'point:points:create',
            'point:points:edit',
            'point:points:delete',
            'point:points:publish'

        ), "RETURN_ARRAY");

        if ($entity === null) {
            //set the return URL
            $returnUrl  = $this->generateUrl('mautic_point_index', array('page' => $page));

            return $this->postActionRedirect(array(
                'returnUrl'       => $returnUrl,
                'viewParameters'  => array('page' => $page),
                'contentTemplate' => 'MauticPointBundle:Point:index',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_point_index',
                    'mauticContent' => 'point'
                ),
                'flashes'         =>array(
                    array(
                        'type' => 'error',
                        'msg'  => 'mautic.point.error.notfound',
                        'msgVars' => array('%id%' => $objectId)
                    )
                )
            ));
        } elseif (!$permissions['point:points:view']) {
            return $this->accessDenied();
        }

        return $this->delegateView(array(
            'viewParameters'  => array(
                'entity'      => $entity,
                'page'        => $page,
                'permissions' => $permissions
            ),
            'contentTemplate' => 'MauticPointBundle:Point:details.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_point_index',
                'mauticContent' => 'point',
                'route'         => $this->generateUrl('mautic_point_action', array(
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
        $model   = $this->factory->getModel('point');
        $entity  = $model->getEntity();

        if (!$this->factory->getSecurity()->isGranted('point:points:create')) {
            return $this->accessDenied();
        }

        //set the page we came from
        $page   = $this->factory->getSession()->get('mautic.point.page', 1);

        $actionType = ($this->request->getMethod() == 'POST') ? $this->request->request->get('point[type]', '', true) : '';

        $action  = $this->generateUrl('mautic_point_action', array('objectAction' => 'new'));
        $actions = $model->getPointActions();
        $form    = $model->createForm($entity, $this->get('form.factory'), $action, array(
            'pointActions' => $actions,
            'actionType'   => $actionType
        ));

        ///Check for a submitted form and process it
        if ($this->request->getMethod() == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    //form is valid so process the data
                    $model->saveEntity($entity);

                    $this->request->getSession()->getFlashBag()->add(
                        'notice',
                        $this->get('translator')->trans('mautic.point.notice.created', array(
                            '%name%' => $entity->getName(),
                            '%url%'          => $this->generateUrl('mautic_point_action', array(
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
                        $returnUrl      = $this->generateUrl('mautic_point_action', $viewParameters);
                        $template       = 'MauticPointBundle:Point:view';
                    } else {
                        //return edit view so that all the session stuff is loaded
                        return $this->editAction($entity->getId(), true);
                    }
                }
            } else {
                $viewParameters  = array('page' => $page);
                $returnUrl = $this->generateUrl('mautic_point_index', $viewParameters);
                $template  = 'MauticPointBundle:Point:index';
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                return $this->postActionRedirect(array(
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => $viewParameters,
                    'contentTemplate' => $template,
                    'passthroughVars' => array(
                        'activeLink'    => '#mautic_point_index',
                        'mauticContent' => 'point'
                    )
                ));
            }
        }

        $formView = $this->setFormTheme($form, 'MauticPointBundle:Point:form.html.php', 'MauticPointBundle:PointForm');
        return $this->delegateView(array(
            'viewParameters'  => array(
                'tmpl'           => $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index',
                'entity'         => $entity,
                'form'           => $formView,
                'actions'        => $actions['actions']
            ),
            'contentTemplate' => 'MauticPointBundle:Point:form.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_point_index',
                'mauticContent' => 'point',
                'route'         => $this->generateUrl('mautic_point_action', array(
                        'objectAction' => (!empty($valid) ? 'edit' : 'new'), //valid means a new form was applied
                        'objectId'     => $entity->getId()
                    )
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
        $model      = $this->factory->getModel('point');
        $entity     = $model->getEntity($objectId);

        //set the page we came from
        $page    = $this->factory->getSession()->get('mautic.point.page', 1);

        //set the return URL
        $returnUrl  = $this->generateUrl('mautic_point_index', array('page' => $page));

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => array('page' => $page),
            'contentTemplate' => 'MauticPointBundle:Point:index',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_point_index',
                'mauticContent' => 'point'
            )
        );
        //form not found
        if ($entity === null) {
            return $this->postActionRedirect(
                array_merge($postActionVars, array(
                    'flashes' => array(
                        array(
                            'type' => 'error',
                            'msg'  => 'mautic.point.error.notfound',
                            'msgVars' => array('%id%' => $objectId)
                        )
                    )
                ))
            );
        } elseif (!$this->factory->getSecurity()->isGranted('point:points:edit')) {
            return $this->accessDenied();
        } elseif ($model->isLocked($entity)) {
            //deny access if the entity is locked
            return $this->isLocked($postActionVars, $entity, 'point');
        }


        $actionType = ($this->request->getMethod() == 'POST') ? $this->request->request->get('point[type]', '', true) : '';

        $action  = $this->generateUrl('mautic_point_action', array('objectAction' => 'edit', 'objectId' => $objectId));
        $actions = $model->getPointActions();
        $form    = $model->createForm($entity, $this->get('form.factory'), $action, array(
            'pointActions' => $actions,
            'actionType'   => $actionType
        ));


        ///Check for a submitted form and process it
        if (!$ignorePost && $this->request->getMethod() == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    //form is valid so process the data
                    $model->saveEntity($entity, $form->get('buttons')->get('save')->isClicked());

                    $this->request->getSession()->getFlashBag()->add(
                        'notice',
                        $this->get('translator')->trans('mautic.point.notice.updated', array(
                            '%name%' => $entity->getName(),
                            '%url%'  => $this->generateUrl('mautic_point_action', array(
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
                        $returnUrl      = $this->generateUrl('mautic_point_action', $viewParameters);
                        $template       = 'MauticPointBundle:Point:view';
                    }
                }
            } else {
                //unlock the entity
                $model->unlockEntity($entity);

                $viewParameters  = array('page' => $page);
                $returnUrl = $this->generateUrl('mautic_point_index', $viewParameters);
                $template  = 'MauticPointBundle:Point:index';
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                return $this->postActionRedirect(
                    array_merge($postActionVars, array(
                        'returnUrl'       => $returnUrl,
                        'viewParameters'  => $viewParameters,
                        'contentTemplate' => $template
                    ))
                );
            }
        } else {
            //lock the entity
            $model->lockEntity($entity);
        }

        $formView = $this->setFormTheme($form, 'MauticPointBundle:Point:form.html.php', 'MauticPointBundle:PointForm');
        return $this->delegateView(array(
            'viewParameters'  => array(
                'tmpl'           => $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index',
                'entity'         => $entity,
                'form'           => $formView,
                'actions'        => $actions['actions']
            ),
            'contentTemplate' => 'MauticPointBundle:Point:form.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_point_index',
                'mauticContent' => 'point',
                'route'         => $this->generateUrl('mautic_point_action', array(
                        'objectAction' => 'edit',
                        'objectId'     => $entity->getId()
                    )
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
        $model   = $this->factory->getModel('point');
        $entity  = $model->getEntity($objectId);

        if ($entity != null) {
            if (!$this->factory->getSecurity()->isGranted('point:points:create')) {
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
        $page        = $this->factory->getSession()->get('mautic.point.page', 1);
        $returnUrl   = $this->generateUrl('mautic_point_index', array('page' => $page));
        $flashes     = array();

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => array('page' => $page),
            'contentTemplate' => 'MauticPointBundle:Point:index',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_point_index',
                'mauticContent' => 'point'
            )
        );

        if ($this->request->getMethod() == 'POST') {
            $model  = $this->factory->getModel('point');
            $entity = $model->getEntity($objectId);

            if ($entity === null) {
                $flashes[] = array(
                    'type'    => 'error',
                    'msg'     => 'mautic.point.error.notfound',
                    'msgVars' => array('%id%' => $objectId)
                );
            } elseif (!$this->factory->getSecurity()->isGranted('point:points:delete')) {
                return $this->accessDenied();
            } elseif ($model->isLocked($entity)) {
                return $this->isLocked($postActionVars, $entity, 'point');
            }

            $model->deleteEntity($entity);

            $identifier = $this->get('translator')->trans($entity->getName());
            $flashes[] = array(
                'type' => 'notice',
                'msg'  => 'mautic.point.notice.deleted',
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
     * Deletes a group of entities
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchDeleteAction() {
        $page        = $this->factory->getSession()->get('mautic.point.page', 1);
        $returnUrl   = $this->generateUrl('mautic_point_index', array('page' => $page));
        $flashes     = array();

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => array('page' => $page),
            'contentTemplate' => 'MauticPointBundle:Point:index',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_point_index',
                'mauticContent' => 'point'
            )
        );

        if ($this->request->getMethod() == 'POST') {
            $model     = $this->factory->getModel('point');
            $ids       = json_decode($this->request->query->get('ids', array()));
            $deleteIds = array();

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $model->getEntity($objectId);

                if ($entity === null) {
                    $flashes[] = array(
                        'type'    => 'error',
                        'msg'     => 'mautic.point.error.notfound',
                        'msgVars' => array('%id%' => $objectId)
                    );
                } elseif (!$this->factory->getSecurity()->isGranted('point:points:delete')) {
                    $flashes[] = $this->accessDenied(true);
                } elseif ($model->isLocked($entity)) {
                    $flashes[] = $this->isLocked($postActionVars, $entity, 'point', true);
                } else {
                    $deleteIds[] = $objectId;
                }
            }

            // Delete everything we are able to
            if (!empty($deleteIds)) {
                $entities = $model->deleteEntities($deleteIds);

                $flashes[] = array(
                    'type' => 'notice',
                    'msg'  => 'mautic.point.notice.batch_deleted',
                    'msgVars' => array(
                        '%count%' => count($entities)
                    )
                );
            }
        } //else don't do anything

        return $this->postActionRedirect(
            array_merge($postActionVars, array(
                'flashes' => $flashes
            ))
        );
    }
}
