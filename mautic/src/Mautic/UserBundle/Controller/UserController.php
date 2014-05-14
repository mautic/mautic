<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */



namespace Mautic\UserBundle\Controller;

use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Controller\FormController;
use Mautic\UserBundle\Form\Type as FormType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

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
        $filter     = $this->request->get('search', $this->get('session')->get('mautic.user.filter', ''));
        $this->get('session')->set('mautic.user.filter', $filter);
        $tmpl       = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';
        $users      = $this->container->get('mautic.model.user')->getEntities(
            array(
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir
            ));

        //Check to see if the number of pages match the number of users
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
                'viewParameters'  => array(
                    'page' => $lastPage,
                    'tmpl' => $tmpl
                ),
                'contentTemplate' => 'MauticUserBundle:User:index',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_user_index',
                    'route'         => $returnUrl,
                    'mauticContent' => 'user'
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
            'items'       => $users,
            'page'        => $page,
            'limit'       => $limit,
            'permissions' => $permissions,
            'tmpl'        => $tmpl
        );

        return $this->delegateView(array(
            'viewParameters'  => $parameters,
            'contentTemplate' => 'MauticUserBundle:User:list.html.php',
            'passthroughVars' => array(
                'route'         => $this->generateUrl('mautic_user_index', array('page' => $page)),
                'mauticContent' => 'user'
            )
        ));
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
        $model      = $this->container->get('mautic.model.user');

        //retrieve the user entity
        $user       = $model->getEntity();
        //set the return URL for post actions
        $returnUrl  = $this->generateUrl('mautic_user_index');
        //set the page we came from
        $page       = $this->get('session')->get('mautic.user.page', 1);

        //get the user form factory
        $action     = $this->generateUrl('mautic_user_action', array('objectAction' => 'new'));
        $form       = $model->createForm($user, $action);

        ///Check for a submitted form and process it
        if ($this->request->getMethod() == 'POST') {
            //check to see if the password needs to be rehashed
            $submittedPassword  = $this->request->request->get('user[plainPassword][password]', null, true);
            $password           = $model->checkNewPassword($user, $submittedPassword);
            $valid = $this->checkFormValidity($form);

            if ($valid === 1) {
                //form is valid so process the data
                $user->setPassword($password);
                $this->container->get('mautic.model.user')->saveEntity($user);
            }

            if (!empty($valid)) { //cancelled or success
                return $this->postActionRedirect(array(
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => array('page' => $page),
                    'contentTemplate' => 'MauticUserBundle:User:index',
                    'passthroughVars' => array(
                        'activeLink'    => '#mautic_user_index',
                        'route'         => $returnUrl,
                        'mauticContent' => 'user'
                    ),
                    'flashes'         =>
                        ($valid === 1) ? array(
                            array(
                                'type' => 'notice',
                                'msg'  => 'mautic.user.user.notice.created',
                                'msgVars' => array('%name%' => $user->getName())
                            )
                        ) : array()
                ));
            } else {
                //check for role error and assign it to role_lookup
                $errors = $form->getErrors();
                if (!empty($errors)) {
                    foreach ($errors as $error) {
                        if ($error->getMessageTemplate() == 'mautic.user.user.role.notblank') {
                            $form->get('role_lookup')->addError(new FormError($error->getMessage()));
                            break;
                        }
                    }
                }
            }
        }

        $formView = $form->createView();
        $this->container->get('templating')->getEngine('MauticUserBundle:User:form.html.php')->get('form')
            ->setTheme($formView, 'MauticUserBundle:FormUser');

        return $this->delegateView(array(
            'viewParameters'  => array('form' => $formView),
            'contentTemplate' => 'MauticUserBundle:User:form.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_user_new',
                'route'         => $action,
                'mauticContent' => 'user'
            )
        ));
    }

    /**
     * Generates edit form and processes post data
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction ($objectId)
    {
        if (!$this->get('mautic.security')->isGranted('user:users:edit')) {
            return $this->accessDenied();
        }
        $model   = $this->container->get('mautic.model.user');
        $user    = $model->getEntity($objectId);
        //set the page we came from
        $page    = $this->get('session')->get('mautic.user.page', 1);
        //set the return URL
        $returnUrl  = $this->generateUrl('mautic_user_index', array('page' => $page));

        //user not found
        if ($user === null) {
            return $this->postActionRedirect(array(
                'returnUrl'       => $returnUrl,
                'viewParameters'  => array('page' => $page),
                'contentTemplate' => 'MauticUserBundle:User:index',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_user_index',
                    'route'         => $returnUrl,
                    'mauticContent' => 'user'
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
        $action = $this->generateUrl('mautic_user_action', array('objectAction' => 'edit', 'objectId' => $objectId));
        $form   = $model->createForm($user, $action);

        ///Check for a submitted form and process it
        if ($this->request->getMethod() == 'POST') {
            //check to see if the password needs to be rehashed
            $submittedPassword  = $this->request->request->get('user[plainPassword][password]', null, true);
            $password           = $model->checkNewPassword($user, $submittedPassword);
            $valid              = $this->checkFormValidity($form);

            if ($valid === 1) {
                //form is valid so process the data
                $user->setPassword($password);
                $model->saveEntity($user);
            }

            if (!empty($valid)) { //cancelled or success

                return $this->postActionRedirect(array(
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => array('page' => $page),
                    'contentTemplate' => 'MauticUserBundle:User:index',
                    'passthroughVars' => array(
                        'activeLink'    => '#mautic_user_index',
                        'route'         => $returnUrl,
                        'mauticContent' => 'user'
                    ),
                    'flashes'         =>
                        ($valid === 1) ? array( //success
                            array(
                                'type' => 'notice',
                                'msg'  => 'mautic.user.user.notice.updated',
                                'msgVars' => array('%name%' => $user->getName())
                            )
                        ) : array()
                ));
            } else {
                //check for role error and assign it to role_lookup
                $errors = $form->getErrors();
                if (!empty($errors)) {
                    foreach ($errors as $error) {
                        if ($error->getMessageTemplate() == 'mautic.user.user.role.notblank') {
                            $form->get('role_lookup')->addError(new FormError($error->getMessage()));
                            break;
                        }
                    }
                }
            }
        } else {
            $form->get('role_lookup')->setData($user->getRole()->getName());
        }

        $formView = $form->createView();
        $this->container->get('templating')->getEngine('MauticUserBundle:User:form.html.php')->get('form')
            ->setTheme($formView, 'MauticUserBundle:FormUser');

        return $this->delegateView(array(
            'viewParameters'  => array('form' => $formView),
            'contentTemplate' => 'MauticUserBundle:User:form.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_user_index',
                'route'         => $action,
                'mauticContent' => 'user'
            )
        ));
    }

    /**
     * Deletes a user object
     *
     * @param         $objectId
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction($objectId) {
        if (!$this->get('mautic.security')->isGranted('user:users:delete')) {
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
                $model = $this->container->get('mautic.model.user');
                $entity = $model->getEntity($objectId);

                if ($entity === null) {
                    $flashes[] = array(
                        'type' => 'error',
                        'msg'  => 'mautic.user.user.error.notfound',
                        'msgVars' => array('%id%' => $objectId)
                    );
                } else {
                    $model->deleteEntity($entity);
                    $name = $entity->getName();
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
                'success'       => $success,
                'mauticContent' => 'user'
            ),
            'flashes'         => $flashes
        ));
    }

    /**
     * {@inheritdoc)
     *
     * @param $action
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function executeAjaxAction( Request $request, $ajaxAction = "" )
    {
        $dataArray = array("success" => 0);
        switch ($ajaxAction) {
            case "rolelist":
                $filter  = InputHelper::clean($request->query->get('filter'));
                $results = $this->get('mautic.model.user')->getLookupResults('role', $filter);
                $dataArray = array();
                foreach ($results as $r) {
                    $dataArray[] = array(
                        'label' => $r['name'],
                        'value' => $r['id']
                    );
                }
                break;
            case "positionlist":
                $filter  = InputHelper::clean($request->query->get('filter'));
                $results = $this->get('mautic.model.user')->getLookupResults('position', $filter);
                $dataArray = array();
                foreach ($results as $r) {
                    $dataArray[] = array('value' => $r['position']);
                }
                break;
        }
        $response  = new JsonResponse();
        $response->setData($dataArray);

        return $response;
    }
}