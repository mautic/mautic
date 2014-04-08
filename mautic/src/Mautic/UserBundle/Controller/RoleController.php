<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */



namespace Mautic\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Mautic\CoreBundle\Controller\FormController;
use Mautic\UserBundle\Entity as Entity;
use Mautic\UserBundle\Form\Type as FormType;

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
     * @param Request $this->request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($page = 1)
    {
        if (!$this->get('mautic_core.permissions')->isGranted('user:roles:view')) {
            return $this->accessDenied();
        }

        //set limits
        $limit = $this->container->getParameter('default_pagelimit');
        $start = ($page === 1) ? 0 : (($page-1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $orderBy    = $this->get('session')->get('mautic.role.orderby', 'r.name');
        $orderByDir = $this->get('session')->get('mautic.role.orderbydir', 'ASC');
        $filter     = $this->request->request->get('filter-role', $this->get('session')->get('mautic.role.filter', ''));
        $this->get('session')->set('mautic.role.filter', $filter);

        $items = $this->getDoctrine()
            ->getRepository('MauticUserBundle:Role')
            ->getRoles($start, $limit, $filter, $orderBy, $orderByDir);

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

            return $this->postActionRedirect(
                $returnUrl,
                array('page' => $lastPage),
                'MauticUserBundle:Role:index',
                array(
                    'activeLink'    => '#mautic_role_index',
                    'route'         => $returnUrl
                ),
                array()
            );
        }

        //set what page currently on so that we can return here after form submission/cancellation
        $this->get('session')->set('mautic.role.page', $page);

        //set some permissions
        $permissions = array(
            'create' => $this->get('mautic_core.permissions')->isGranted('user:roles:create'),
            'edit'   => $this->get('mautic_core.permissions')->isGranted('user:roles:editother'),
            'delete' => $this->get('mautic_core.permissions')->isGranted('user:roles:deleteother'),
        );

        $parameters = array(
            'filterValue' => $filter,
            'items'       => $items,
            'page'        => $page,
            'limit'       => $limit,
            'permissions' => $permissions
        );

        if ($this->request->isXmlHttpRequest() && !$this->request->get('ignoreAjax', false)) {
            return $this->ajaxAction(
                $parameters,
                'MauticUserBundle:Role:index.html.php',
                array('route' => $this->generateUrl('mautic_role_index', array('page' => $page)))
            );
        } else {
            return $this->render('MauticUserBundle:Role:index.html.php', $parameters);
        }
    }

    /**
     * Generate's new role form and processes post data
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction ()
    {
        if (!$this->get('mautic_core.permissions')->isGranted('user:roles:create')) {
            return $this->accessDenied();
        }

        //retrieve the entity
        $entity      = new Entity\Role();

        //set action URL
        $action     = $this->generateUrl('mautic_role_action', array('objectAction' => 'new'));
        //set the return URL for post actions
        $returnUrl  = $this->generateUrl('mautic_role_index');
        //set the page we came from
        $page       = $this->get('session')->get('mautic.role.page', 1);
        //get the user form factory
        $form       = $this->get('form.factory')->create('role', $entity, array('action' => $action));

        ///Check for a submitted form and process it
        if ($this->request->getMethod() == 'POST') {
            $valid = $this->checkFormValidity($form);

            if ($valid === 1) {
                //form is valid so process the data
                $result = $this->container->get('mautic_user.model.role')->saveEntity($entity, true);
            }

            if (!empty($valid)) { //cancelled or success
                return $this->postActionRedirect(
                    $returnUrl,
                    array('page' => $page),
                    'MauticUserBundle:Role:index',
                    array(
                        'activeLink' => '#mautic_role_index',
                        'route'      => $returnUrl
                    ),
                    (!empty($result) && $result === 1) ? array( //success
                        array(
                            'type'    => 'notice',
                            'msg'     => 'mautic.user.role.notice.created',
                            'msgVars' => array('%name%' => $entity->getName())
                        )
                    ) : array()
                );
            }
        }

        if ($this->request->isXmlHttpRequest() && !$this->request->get('ignoreAjax', false)) {
            return $this->ajaxAction(
                array('form' => $form->createView()),
                'MauticUserBundle:Role:form.html.php',
                array(
                    'ajaxForms'  => array('role'),
                    'activeLink' => '#mautic_role_new',
                    'route'      => $action
                )
            );
        } else {
            return $this->render('MauticUserBundle:Role:form.html.php',
                array(
                    'form' => $form->createView()
                )
            );
        }
    }

    /**
     * Generate's role edit form and processes post data
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction ($objectId)
    {
        if (!$this->get('mautic_core.permissions')->isGranted('user:roles:editother')) {
            return $this->accessDenied();
        }

        $em      = $this->getDoctrine()->getManager();
        $entity  = $em->getRepository('MauticUserBundle:Role')->find($objectId);

        //set the page we came from
        $page    = $this->get('session')->get('mautic.role.page', 1);
        //set the return URL
        $returnUrl  = $this->generateUrl('mautic_role_index', array('page' => $page));

        //user not found
        if (empty($entity)) {
            return $this->postActionRedirect(
                $returnUrl,
                array('page' => $page),
                'MauticUserBundle:Role:index',
                array(
                    'activeLink'    => '#mautic_role_index',
                    'route'         => $returnUrl
                ),
                array(
                    array(
                        'type' => 'error',
                        'msg'  => 'mautic.user.role.error.notfound',
                        'msgVars' => array('%id' => $objectId)
                    )
                )
            );
        }

        //set action URL
        $action     = $this->generateUrl('mautic_role_action',
            array('objectAction' => 'edit', 'objectId' => $objectId)
        );

        $form       = $this->get('form.factory')->create('role', $entity, array('action' => $action));

        ///Check for a submitted form and process it
        if ($this->request->getMethod() == 'POST') {
            $valid = $this->checkFormValidity($form);

            if ($valid === 1) {
                //form is valid so process the data
                $result = $this->container->get('mautic_user.model.role')->saveEntity($entity);
            }

            if (!empty($valid)) { //cancelled or success
                return $this->postActionRedirect(
                    $returnUrl,
                    array('page' => $page),
                    'MauticUserBundle:Role:index',
                    array(
                        'activeLink' => '#mautic_role_index',
                        'route'      => $returnUrl
                    ),
                    (!empty($result) && $result === 1) ? array( //success
                        array(
                            'type'    => 'notice',
                            'msg'     => 'mautic.user.role.notice.updated',
                            'msgVars' => array('%name%' => $entity->getName())
                        )
                    ) : array()
                );
            }
        }

        if ($this->request->isXmlHttpRequest() && !$this->request->get('ignoreAjax', false)) {
            return $this->ajaxAction(
                array('form' => $form->createView()),
                'MauticUserBundle:Role:form.html.php',
                array(
                    'ajaxForms'   => array('role'),
                    'activeLink'  => '#mautic_role_index',
                    'route'       => $action
                )
            );
        } else {
            return $this->render('MauticUserBundle:Role:form.html.php',
                array(
                    'form' => $form->createView()
                )
            );
        }
    }

    /**
     * Delete's a role
     *
     * @param         $objectId
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction($objectId) {
        if (!$this->get('mautic_core.permissions')->isGranted('user:roles:deleteother')) {
            return $this->accessDenied();
        }

        $page        = $this->get('session')->get('mautic.role.page', 1);
        $returnUrl   = $this->generateUrl('mautic_role_index', array('page' => $page));
        $success     = 0;
        $flashes     = array();
        if ($this->request->getMethod() == 'POST') {
            $result = $this->container->get('mautic_user.model.role')->deleteEntity($objectId);
            $name   = $result->getName();

            if (!$result) {
                $flashes[] = array(
                    'type' => 'error',
                    'msg'  => 'mautic.user.role.error.notfound',
                    'msgVars' => array('%id%' => $objectId)
                );
            } else {
                $flashes[] = array(
                    'type' => 'notice',
                    'msg'  => 'mautic.user.role.notice.deleted',
                    'msgVars' => array(
                        '%name%' => $name,
                        '%id%'   => $objectId
                    )
                );
            }
        } //else don't do anything

        return $this->postActionRedirect(
            $returnUrl,
            array('page' => $page),
            'MauticUserBundle:Role:index',
            array(
                'activeLink'    => '#mautic_role_index',
                'route'         => $returnUrl,
                'success'       => $success
            ),
            $flashes
        );
    }
}