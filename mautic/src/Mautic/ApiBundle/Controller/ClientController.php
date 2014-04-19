<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */



namespace Mautic\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Mautic\CoreBundle\Controller\FormController;
use Mautic\ApiBundle\Form\Type as FormType;

/**
 * Class ClientController
 *
 * @package Mautic\ApiBundle\Controller
 */
class ClientController extends FormController
{
    /**
     * Generate's default client list
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        if (!$this->get('mautic.security')->isGranted('api:clients:view')) {
            return $this->accessDenied();
        }

        $filter     = $this->request->request->get('filter-client', $this->get('session')->get('mautic.client.filter', ''));
        $this->get('session')->set('mautic.client.filter', $filter);

        $clients = $this->container->get('mautic.model.client')->getEntities(array('filter' => $filter));

        //set some permissions
        $permissions = array(
            'create' => $this->get('mautic.security')->isGranted('api:clients:create'),
            'edit'   => $this->get('mautic.security')->isGranted('api:clients:editother'),
            'delete' => $this->get('mautic.security')->isGranted('api:clients:deleteother'),
        );

        $parameters = array(
            'filterValue' => $filter,
            'items'       => $clients,
            'permissions' => $permissions
        );

        if ($this->request->isXmlHttpRequest() && !$this->request->get('ignoreAjax', false)) {
            return $this->ajaxAction(array(
                'viewParameters'  => $parameters,
                'contentTemplate' => 'MauticApiBundle:Client:index.html.php',
                'passthroughVars' => array('route' => $this->generateUrl('mautic_client_index'))
            ));
        } else {
            return $this->render('MauticApiBundle:Client:index.html.php', $parameters);
        }
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function authorizedClientsAction()
    {
        $me      = $this->get('security.context')->getToken()->getUser();
        $clients = $me->getClients()->toArray();

        return $this->render('MauticApiBundle:Client:authorized.html.php', array('clients' => $clients));
    }

    public function revokeAction($clientId)
    {
        $success = 0;
        $flashes = array();
        if ($this->request->getMethod() == 'POST') {
            $me      = $this->get('security.context')->getToken()->getUser();
            $client  = $this->container->get('mautic.model.client')->getEntity($clientId);

            if ($client === null) {
                $flashes[] = array(
                    'type'    => 'error',
                    'msg'     => 'mautic.api.client.error.notfound',
                    'msgVars' => array('%id%' => $clientId)
                );
            } else {
                $name = $client->getName();

                //remove this client from user
                $me->removeClient($client);
                $this->container->get('mautic.model.user')->saveEntity($me);

                //remove the user from the client
                $client->removeUser($me);
                $this->container->get('mautic.model.client')->saveEntity($client);

                $flashes[] = array(
                    'type'    => 'notice',
                    'msg'     => 'mautic.api.client.notice.revoked',
                    'msgVars' => array(
                        '%name%' => $name
                    )
                );
            }
        }
        $returnUrl = $this->generateUrl('mautic_user_account');
        return $this->postActionRedirect(array(
            'returnUrl'       => $returnUrl,
            'contentTemplate' => 'MauticUserBundle:Profile:index',
            'passthroughVars' => array(
                'route'         => $returnUrl,
                'success'       => $success
            ),
            'flashes'         => $flashes
        ));
    }

    /**
     * Generate's form and processes new post data
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction ()
    {
        if (!$this->get('mautic.security')->isGranted('api:clients:create')) {
            return $this->accessDenied();
        }

        $model      = $this->container->get('mautic.model.client');
        //retrieve the entity
        $client     = $model->getEntity();
        //set the return URL for post actions
        $returnUrl  = $this->generateUrl('mautic_client_index');

        //get the user form factory
        $action     = $this->generateUrl('mautic_client_action', array('objectAction' => 'new'));
        $form       = $model->createForm($client, $action);

        //remove the client id and secret fields as they'll be auto generated
        $form->remove("randomId");
        $form->remove("secret");
        $form->remove("publicId");

        ///Check for a submitted form and process it
        if ($this->request->getMethod() == 'POST') {
            $valid = $this->checkFormValidity($form);

            if ($valid === 1) {
                //form is valid so process the data
                $model->saveEntity($client);
            }

            if (!empty($valid)) { //cancelled or success

                return $this->postActionRedirect(array(
                    'returnUrl'       => $returnUrl,
                    'contentTemplate' => 'MauticApiBundle:Client:index',
                    'passthroughVars' => array(
                        'activeLink'    => '#mautic_client_index',
                        'route'         => $returnUrl,
                    ),
                    'flashes'         =>
                        ($valid === 1) ? array( //success
                            array(
                                'type' => 'notice',
                                'msg'  => 'mautic.api.client.notice.created',
                                'msgVars' => array(
                                    '%name%'         => $client->getName(),
                                    '%clientId%'     => $client->getPublicId(),
                                    '%clientSecret%' => $client->getSecret()
                                )
                            )
                        ) : array()
                ));
            }
        }

        if ($this->request->isXmlHttpRequest() && !$this->request->get('ignoreAjax', false)) {
            return $this->ajaxAction(array(
                'viewParameters'  => array('form' => $form->createView()),
                'contentTemplate' => 'MauticApiBundle:Client:form.html.php',
                'passthroughVars' => array(
                    'ajaxForms'  => array('client'),
                    'activeLink' => '#mautic_client_new',
                    'route'      => $action
                )
            ));
        } else {
            return $this->render('MauticApiBundle:Client:form.html.php',
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
        if (!$this->get('mautic.security')->isGranted('api:clients:editother')) {
            return $this->accessDenied();
        }
        $model     = $this->container->get('mautic.model.client');
        $client    = $model->getEntity($objectId);
        $returnUrl = $this->generateUrl('mautic_client_index');

        //client not found
        if ($client === null) {
            return $this->postActionRedirect(array(
                'returnUrl'       => $returnUrl,
                'contentTemplate' => 'MauticApiBundle:Client:index',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_client_index',
                    'route'         => $returnUrl
                ),
                'flashes'         =>array(
                    array(
                        'type' => 'error',
                        'msg'  => 'mautic.api.client.error.notfound',
                        'msgVars' => array('%id%' => $objectId)
                    )
                )
            ));
        }
        $action = $this->generateUrl('mautic_client_action', array('objectAction' => 'edit', 'objectId' => $objectId));
        $form   = $model->createForm($client, $action);

        ///Check for a submitted form and process it
        if ($this->request->getMethod() == 'POST') {
            $valid = $this->checkFormValidity($form);

            if ($valid === 1) {
                //form is valid so process the data
                $model->saveEntity($client);
            }

            if (!empty($valid)) { //cancelled or success

                return $this->postActionRedirect(array(
                    'returnUrl'       => $returnUrl,
                    'contentTemplate' => 'MauticApiBundle:Client:index',
                    'passthroughVars' => array(
                        'activeLink'    => '#mautic_client_index',
                        'route'         => $returnUrl,
                    ),
                    'flashes'         =>
                        ($valid === 1) ? array( //success
                            array(
                                'type' => 'notice',
                                'msg'  => 'mautic.api.client.notice.updated',
                                'msgVars' => array('%name%' => $client->getName())
                            )
                        ) : array()
                ));
            }
        }

        if ($this->request->isXmlHttpRequest() && !$this->request->get('ignoreAjax', false)) {
            return $this->ajaxAction(array(
                'viewParameters'  => array('form' => $form->createView()),
                'contentTemplate' => 'MauticApiBundle:Client:form.html.php',
                'passthroughVars' => array(
                    'ajaxForms'   => array('client'),
                    'activeLink'  => '#mautic_client_index',
                    'route'       => $action
                )
            ));
        } else {
            return $this->render('MauticApiBundle:Client:form.html.php',
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
        if (!$this->get('mautic.security')->isGranted('api:clients:deleteother')) {
            return $this->accessDenied();
        }

        $returnUrl   = $this->generateUrl('mautic_client_index');
        $success     = 0;
        $flashes     = array();
        if ($this->request->getMethod() == 'POST') {
            $result = $this->container->get('mautic.model.client')->deleteEntity($objectId);

            if (!$result) {
                $flashes[] = array(
                    'type' => 'error',
                    'msg'  => 'mautic.api.client.error.notfound',
                    'msgVars' => array('%id%' => $objectId)
                );
            } else {
                $name = $result->getName();
                $flashes[] = array(
                    'type' => 'notice',
                    'msg'  => 'mautic.api.client.notice.deleted',
                    'msgVars' => array(
                        '%name%' => $name,
                        '%id%'   => $objectId
                    )
                );
            }
        }

        return $this->postActionRedirect(array(
            'returnUrl'       => $returnUrl,
            'contentTemplate' => 'MauticApiBundle:Client:index',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_client_index',
                'route'         => $returnUrl,
                'success'       => $success
            ),
            'flashes'         => $flashes
        ));
    }
}