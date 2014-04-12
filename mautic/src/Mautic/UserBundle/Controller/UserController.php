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
 * Class UserController
 *
 * @package Mautic\UserBundle\Controller
 */
class UserController extends FormController
{
    /**
     * Generate's default user list
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($page = 1)
    {
        if (!$this->get('mautic.security')->isGranted('user:users:view')) {
            return $this->accessDenied();
        }

        //set limits
        $limit = $this->container->getParameter('mautic.default_pagelimit');
        $start = ($page === 1) ? 0 : (($page-1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $orderBy    = $this->get('session')->get('mautic.user.orderby', 'u.lastName, u.firstName, u.username');
        $orderByDir = $this->get('session')->get('mautic.user.orderbydir', 'ASC');
        $filter     = $this->request->request->get('filter-user', $this->get('session')->get('mautic.user.filter', ''));
        $this->get('session')->set('mautic.user.filter', $filter);

        $users = $this->getDoctrine()
            ->getRepository('MauticUserBundle:User')
            ->getUsers(array(
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir
            ));

        $count = count($users);
        if ($count && $count < ($start + 1)) {
            //the number of entities are now less then the current page so redirect to the last page
            if ($count === 1) {
                $lastPage = 1;
            } else {
                $lastPage = (floor($limit / $count)) ? : 1;
            }
            $this->get('session')->set('mautic.user.page', $lastPage);
            $returnUrl   = $this->generateUrl('mautic_user_index', array('page' => $lastPage));

            return $this->postActionRedirect(array(
                'returnUrl'       => $returnUrl,
                'viewParameters'  => array('page' => $lastPage),
                'contentTemplate' => 'MauticUserBundle:User:index',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_user_index',
                    'route'         => $returnUrl,
                )
            ));
        }

        //set what page currently on so that we can return here after form submission/cancellation
        $this->get('session')->set('mautic.user.page', $page);

        //set some permissions
        $permissions = array(
            'create' => $this->get('mautic.security')->isGranted('user:users:create'),
            'edit'   => $this->get('mautic.security')->isGranted('user:users:editother'),
            'delete' => $this->get('mautic.security')->isGranted('user:users:deleteother'),
        );

        $parameters = array(
            'filterValue' => $filter,
            'items'       => $users,
            'page'        => $page,
            'limit'       => $limit,
            'permissions' => $permissions
        );

        if ($this->request->isXmlHttpRequest() && !$this->request->get('ignoreAjax', false)) {
            return $this->ajaxAction(array(
                'viewParameters'  => $parameters,
                'contentTemplate' => 'MauticUserBundle:User:index.html.php',
                'passthroughVars' => array('route' => $this->generateUrl('mautic_user_index', array('page' => $page)))
            ));
        } else {
            return $this->render('MauticUserBundle:User:index.html.php', $parameters);
        }
    }

    /**
     * Generate's form and processes new post data
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction ()
    {
        if (!$this->get('mautic.security')->isGranted('user:users:create')) {
            return $this->accessDenied();
        }

        //retrieve the user entity
        $user       = new Entity\User();
        //set action URL
        $action     = $this->generateUrl('mautic_user_action', array('objectAction' => 'new'));
        //set the return URL for post actions
        $returnUrl  = $this->generateUrl('mautic_user_index');
        //set the page we came from
        $page       = $this->get('session')->get('mautic.user.page', 1);
        //get the user form factory
        $form       = $this->get('form.factory')->create('user', $user, array('action' => $action));

        ///Check for a submitted form and process it
        if ($this->request->getMethod() == 'POST') {
            $model = $this->container->get('mautic.model.user');
            //check to see if the password needs to be rehashed
            $overrides             = array();
            $overrides['password'] = $model->checkNewPassword($user);
            $valid = $this->checkFormValidity($form);

            if ($valid === 1) {
                //form is valid so process the data
                $result = $this->container->get('mautic.model.user')->saveEntity($user, true, $overrides);
            }

            if (!empty($valid)) { //cancelled or success

                return $this->postActionRedirect(array(
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => array('page' => $page),
                    'contentTemplate' => 'MauticUserBundle:User:index',
                    'passthroughVars' => array(
                        'activeLink'    => '#mautic_user_index',
                        'route'         => $returnUrl,
                    ),
                    'flashes'         =>
                        (!empty($result) && $result === 1) ? array( //success
                            array(
                                'type' => 'notice',
                                'msg'  => 'mautic.user.user.notice.created',
                                'msgVars' => array('%name%' => $user->getName())
                            )
                        ) : array()
                ));
            }
        }

        if ($this->request->isXmlHttpRequest() && !$this->request->get('ignoreAjax', false)) {
            return $this->ajaxAction(array(
                'viewParameters'  => array('form' => $form->createView()),
                'contentTemplate' => 'MauticUserBundle:User:form.html.php',
                'passthroughVars' => array(
                    'ajaxForms'  => array('user'),
                    'activeLink' => '#mautic_user_new',
                    'route'      => $action
                )
            ));
        } else {
            return $this->render('MauticUserBundle:User:form.html.php',
                array(
                    'form' => $form->createView()
                )
            );
        }
    }

    /**
     * Generates edit form and processes post data
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction ($objectId)
    {
        if (!$this->get('mautic.security')->isGranted('user:users:editother')) {
            return $this->accessDenied();
        }

        $em      = $this->getDoctrine()->getManager();
        $user    = $em->getRepository('MauticUserBundle:User')->find($objectId);
        //set the page we came from
        $page    = $this->get('session')->get('mautic.user.page', 1);
        //set the return URL
        $returnUrl  = $this->generateUrl('mautic_user_index', array('page' => $page));

        //user not found
        if (empty($user)) {
            return $this->postActionRedirect(array(
                'returnUrl'       => $returnUrl,
                'viewParameters'  => array('page' => $page),
                'contentTemplate' => 'MauticUserBundle:User:index',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_user_index',
                    'route'         => $returnUrl,
                ),
                'flashes'         =>array(
                    array(
                        'type' => 'error',
                        'msg'  => 'mautic.user.user.error.notfound',
                        'msgVars' => array('%id%' => $objectId)
                    )
                )
            ));
        }

        //set action URL
        $action     = $this->generateUrl('mautic_user_action',
            array('objectAction' => 'edit', 'objectId' => $objectId)
        );

        $form       = $this->get('form.factory')->create('user', $user, array('action' => $action));

        ///Check for a submitted form and process it
        if ($this->request->getMethod() == 'POST') {
            $model = $this->container->get('mautic.model.user');
            //check to see if the password needs to be rehashed
            $overrides             = array();
            $overrides['password'] = $model->checkNewPassword($user);
            $valid = $this->checkFormValidity($form);

            if ($valid === 1) {
                //form is valid so process the data
                $result = $model->saveEntity($user, false, $overrides);
            }

            if (!empty($valid)) { //cancelled or success

                return $this->postActionRedirect(array(
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => array('page' => $page),
                    'contentTemplate' => 'MauticUserBundle:User:index',
                    'passthroughVars' => array(
                        'activeLink'    => '#mautic_user_index',
                        'route'         => $returnUrl,
                    ),
                    'flashes'         =>
                        (!empty($result) && $result === 1) ? array( //success
                            array(
                                'type' => 'notice',
                                'msg'  => 'mautic.user.user.notice.updated',
                                'msgVars' => array('%name%' => $user->getName())
                            )
                        ) : array()
                ));
            }
        }

        if ($this->request->isXmlHttpRequest() && !$this->request->get('ignoreAjax', false)) {
            return $this->ajaxAction(array(
                'viewParameters'  => array('form' => $form->createView()),
                'contentTemplate' => 'MauticUserBundle:User:form.html.php',
                'passthroughVars' => array(
                    'ajaxForms'   => array('user'),
                    'activeLink'  => '#mautic_user_index',
                    'route'       => $action
                )
            ));
        } else {
            return $this->render('MauticUserBundle:User:form.html.php',
                array(
                    'form' => $form->createView()
                )
            );
        }
    }

    /**
     * Deletes a user object
     *
     * @param         $objectId
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction($objectId) {
        if (!$this->get('mautic.security')->isGranted('user:users:deleteother')) {
            return $this->accessDenied();
        }

        $currentUser = $this->get('security.context')->getToken()->getUser();
        $page        = $this->get('session')->get('mautic.user.page', 1);
        $returnUrl   = $this->generateUrl('mautic_user_index', array('page' => $page));
        $success     = 0;
        $flashes     = array();
        if ($this->request->getMethod() == 'POST') {
            //ensure the user logged in is not getting deleted
            if ((int) $currentUser->getId() !== (int) $objectId) {
                $result = $this->container->get('mautic.model.user')->deleteEntity($objectId);
                $name   = $result->getName();

                if (!$result) {
                    $flashes[] = array(
                        'type' => 'error',
                        'msg'  => 'mautic.user.user.error.notfound',
                        'msgVars' => array('%id%' => $objectId)
                    );
                } else {
                    $flashes[] = array(
                        'type' => 'notice',
                        'msg'  => 'mautic.user.user.notice.deleted',
                        'msgVars' => array(
                            '%name%' => $name,
                            '%id%'   => $objectId
                        )
                    );
                }
            } else {
                $flashes[] = array(
                    'type' => 'error',
                    'msg'  => 'mautic.user.user.error.cannotdeleteself'
                );
            }
        } //else don't do anything

        return $this->postActionRedirect(array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => array('page' => $page),
            'contentTemplate' => 'MauticUserBundle:User:index',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_user_index',
                'route'         => $returnUrl,
                'success'       => $success
            ),
            'flashes'         => $flashes
        ));
    }
}