<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */



namespace Mautic\UserBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\UserBundle\Entity as Entity;
use Mautic\UserBundle\Form\Type as FormType;
use Symfony\Component\HttpKernel\Exception\PreconditionRequiredHttpException;

/**
 * Class RoleController
 *
 * @package Mautic\UserBundle\Controller
 */
class RoleController extends FormController
{
    /**
     * Generate's default role list view
     *
     * @param $page
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($page = 1)
    {
        if (!$this->get('mautic.security')->isGranted('user:roles:view')) {
            return $this->accessDenied();
        }

        //set limits
        $limit = $this->get('session')->get('mautic.role.limit', $this->get('mautic.factory')->getParameter('default_pagelimit'));
        $start = ($page === 1) ? 0 : (($page-1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $orderBy    = $this->get('session')->get('mautic.role.orderby', 'r.name');
        $orderByDir = $this->get('session')->get('mautic.role.orderbydir', 'ASC');
        $filter     = $this->request->get('search', $this->get('session')->get('mautic.role.filter', ''));
        $this->get('session')->set('mautic.role.filter', $filter);
        $tmpl       = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        $items = $this->get('mautic.factory')->getModel('user.role')->getEntities(
            array(
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir
            ));

        $count = count($items);
        if ($count && $count < ($start + 1)) {
            //the number of entities are now less then the current page so redirect to the last page
            if ($count === 1) {
                $lastPage = 1;
            } else {
                $lastPage = (floor($limit / $count)) ? : 1;
            }
            $this->get('session')->set('mautic.role.page', $lastPage);
            $returnUrl   = $this->generateUrl('mautic_role_index', array('page' => $lastPage));

            return $this->postActionRedirect(array(
                'returnUrl'       => $returnUrl,
                'viewParameters'  => array(
                    'page' => $lastPage,
                    'tmpl' => $tmpl
                ),
                'contentTemplate' => 'MauticUserBundle:Role:index',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_role_index',
                    'mauticContent' => 'role'
                )
            ));
        }

        //set what page currently on so that we can return here after form submission/cancellation
        $this->get('session')->set('mautic.role.page', $page);

        //set some permissions
        $permissions = array(
            'create' => $this->get('mautic.security')->isGranted('user:roles:create'),
            'edit'   => $this->get('mautic.security')->isGranted('user:roles:edit'),
            'delete' => $this->get('mautic.security')->isGranted('user:roles:delete'),
        );

        $parameters = array(
            'items'       => $items,
            'page'        => $page,
            'limit'       => $limit,
            'permissions' => $permissions,
            'tmpl'        => $tmpl
        );

        return $this->delegateView(array(
            'viewParameters'  => $parameters,
            'contentTemplate' => 'MauticUserBundle:Role:list.html.php',
            'passthroughVars' => array(
                'route'         => $this->generateUrl('mautic_role_index', array('page' => $page)),
                'mauticContent' => 'role'
            )
        ));
    }

    /**
     * Generate's new role form and processes post data
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction ()
    {
        if (!$this->get('mautic.security')->isGranted('user:roles:create')) {
            return $this->accessDenied();
        }

        //retrieve the entity
        $entity     = new Entity\Role();
        $model      = $this->get('mautic.factory')->getModel('user.role');
        //set the return URL for post actions
        $returnUrl  = $this->generateUrl('mautic_role_index');
        //set the page we came from
        $page       = $this->get('session')->get('mautic.role.page', 1);
        $action     = $this->generateUrl('mautic_role_action', array('objectAction' => 'new'));
        //get the user form factory
        $form       = $model->createForm($entity, $this->get('form.factory'), $action);

        ///Check for a submitted form and process it
        if ($this->request->getMethod() == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    //set the permissions
                    $permissions = $this->request->request->get('role[permissions]', null, true);
                    $model->setRolePermissions($entity, $permissions);

                    //form is valid so process the data
                    $model->saveEntity($entity);

                    $this->request->getSession()->getFlashBag()->add(
                        'notice',
                        $this->get('translator')->trans('mautic.user.role.notice.created',  array(
                            '%name%' => $entity->getName(),
                            '%url%'  => $this->generateUrl('mautic_role_action', array(
                                'objectAction' => 'edit',
                                'objectId'     => $entity->getId()
                            ))
                        ), 'flashes')
                    );
                }
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                return $this->postActionRedirect(array(
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => array('page' => $page),
                    'contentTemplate' => 'MauticUserBundle:Role:index',
                    'passthroughVars' => array(
                        'activeLink'    => '#mautic_role_index',
                        'mauticContent' => 'role'
                    )
                ));
            } else {
                return $this->editAction($entity->getId(), true);
            }
        }

        $formView = $form->createView();
        $this->container->get('templating')->getEngine('MauticUserBundle:Role:form.html.php')->get('form')
            ->setTheme($formView, 'MauticUserBundle:FormUser');

        $permissionList = $this->get('mautic.security')->getAllPermissions(true);

        return $this->delegateView(array(
            'viewParameters'  => array(
                'form'           => $formView,
                'permissionList' => $permissionList
            ),
            'contentTemplate' => 'MauticUserBundle:Role:form.html.php',
            'passthroughVars' => array(
                'activeLink'      => '#mautic_role_new',
                'route'           => $this->generateUrl('mautic_role_action', array('objectAction' => 'new')),
                'mauticContent'   => 'role',
                'permissionList'  => $permissionList
            )
        ));
    }

    /**
     * Generate's role edit form and processes post data
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction ($objectId, $ignorePost = true)
    {
        if (!$this->get('mautic.security')->isGranted('user:roles:edit')) {
            return $this->accessDenied();
        }

        $model   = $this->get('mautic.factory')->getModel('user.role');
        $entity  = $model->getEntity($objectId);

        //set the page we came from
        $page    = $this->get('session')->get('mautic.role.page', 1);
        //set the return URL
        $returnUrl  = $this->generateUrl('mautic_role_index', array('page' => $page));

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => array('page' => $page),
            'contentTemplate' => 'MauticUserBundle:Role:index',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_role_index',
                'mauticContent' => 'role'
            )
        );
        //user not found
        if ($entity === null) {
            return $this->postActionRedirect(
                array_merge($postActionVars, array(
                    'flashes' => array(
                        array(
                            'type' => 'error',
                            'msg'  => 'mautic.user.role.error.notfound',
                            'msgVars' => array('%id' => $objectId)
                        )
                    )
                ))
            );
        } elseif ($model->isLocked($entity)) {
            //deny access if the entity is locked
            return $this->isLocked($postActionVars, $entity, 'user.role');
        }

        $action = $this->generateUrl('mautic_role_action', array('objectAction' => 'edit', 'objectId' => $objectId));
        $form   = $model->createForm($entity, $this->get('form.factory'), $action);

        ///Check for a submitted form and process it
        if (!$ignorePost && $this->request->getMethod() == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    //set the permissions
                    $permissions = $this->request->request->get('role[permissions]', null, true);
                    $model->setRolePermissions($entity, $permissions);

                    //form is valid so process the data
                    $model->saveEntity($entity, $form->get('buttons')->get('save')->isClicked());

                    $this->request->getSession()->getFlashBag()->add(
                        'notice',
                        $this->get('translator')->trans('mautic.user.role.notice.updated',  array(
                            '%name%' => $entity->getName(),
                            '%url%'  => $this->generateUrl('mautic_role_action', array(
                                'objectAction' => 'edit',
                                'objectId'     => $entity->getId()
                            ))
                        ), 'flashes')
                    );
                }
            } else {
                //unlock the entity
                $model->unlockEntity($entity);
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                return $this->postActionRedirect($postActionVars);
            } else {
                //the form has to be rebuilt because the permissions were updated
                $form = $model->createForm($entity, $this->get('form.factory'), $action);
            }
        } else {
            //lock the entity
            $model->lockEntity($entity);
        }

        $formView = $form->createView();
        $this->container->get('templating')->getEngine('MauticUserBundle:Role:form.html.php')->get('form')
            ->setTheme($formView, 'MauticUserBundle:FormUser');

        $permissionList = $this->get('mautic.security')->getAllPermissions(true);

        return $this->delegateView(array(
            'viewParameters'  => array(
                'form'            => $formView,
                'permissionList'  => $permissionList
            ),
            'contentTemplate' => 'MauticUserBundle:Role:form.html.php',
            'passthroughVars' => array(
                'activeLink'      => '#mautic_role_index',
                'route'           => $action,
                'mauticContent'   => 'role',
                'permissionList'  => $permissionList
            )
        ));
    }

    /**
     * Delete's a role
     *
     * @param         $objectId
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction($objectId) {
        if (!$this->get('mautic.security')->isGranted('user:roles:delete')) {
            return $this->accessDenied();
        }

        $page           = $this->get('session')->get('mautic.role.page', 1);
        $returnUrl      = $this->generateUrl('mautic_role_index', array('page' => $page));
        $success        = 0;
        $flashes        = array();
        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => array('page' => $page),
            'contentTemplate' => 'MauticUserBundle:Role:index',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_role_index',
                'success'       => $success,
                'mauticContent' => 'role'
            )
        );

        if ($this->request->getMethod() == 'POST') {
            try {
                $model = $this->get('mautic.factory')->getModel('user.role');
                $entity = $model->getEntity($objectId);

                if ($entity === null) {
                    $flashes[] = array(
                        'type' => 'error',
                        'msg'  => 'mautic.user.role.error.notfound',
                        'msgVars' => array('%id%' => $objectId)
                    );
                } elseif ($model->isLocked($entity)) {
                    return $this->isLocked($postActionVars, $entity, 'user.role');
                } else {
                    $model->deleteEntity($objectId);
                    $name = $entity->getName();
                    $flashes[] = array(
                        'type' => 'notice',
                        'msg'  => 'mautic.user.role.notice.deleted',
                        'msgVars' => array(
                            '%name%' => $name,
                            '%id%'   => $objectId
                        )
                    );
                }
            } catch (PreconditionRequiredHttpException $e) {
                $flashes[] = array(
                    'type' => 'error',
                    'msg'  => $e->getMessage()
                );
            }

        } //else don't do anything

        return $this->postActionRedirect(
            array_merge($postActionVars, array(
                'flashes'=> $flashes
            ))
        );
    }
}