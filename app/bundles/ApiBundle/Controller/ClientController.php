<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;

/**
 * Class ClientController
 */
class ClientController extends FormController
{

    /**
     * Generate's default client list
     *
     * @param int $page
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($page = 1)
    {
        if (!$this->factory->getSecurity()->isGranted('api:clients:view')) {
            return $this->accessDenied();
        }

        //set limits
        $limit = $this->factory->getSession()->get('mautic.client.limit', $this->factory->getParameter('default_pagelimit'));
        $start = ($page === 1) ? 0 : (($page-1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $orderBy    = $this->factory->getSession()->get('mautic.client.orderby', 'c.name');
        $orderByDir = $this->factory->getSession()->get('mautic.client.orderbydir', 'ASC');
        $filter     = $this->request->get('search', $this->factory->getSession()->get('mautic.client.filter', ''));
        $this->factory->getSession()->set('mautic.client.filter', $filter);
        $tmpl       = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        $clients = $this->factory->getModel('api.client')->getEntities(
            array(
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir
            ));

        $count = count($clients);
        if ($count && $count < ($start + 1)) {
            //the number of entities are now less then the current page so redirect to the last page
            $lastPage = ($count === 1) ? 1 : (ceil($count / $limit)) ?: 1;
            $this->factory->getSession()->set('mautic.client.page', $lastPage);
            $returnUrl   = $this->generateUrl('mautic_client_index', array('page' => $lastPage));

            return $this->postActionRedirect(array(
                'returnUrl'       => $returnUrl,
                'viewParameters'  => array('page' => $lastPage),
                'contentTemplate' => 'MauticApiBundle:Client:index',
                'passthroughVars' => array(
                    'activeLink'    => 'mautic_client_index',
                    'mauticContent' => 'client'
                )
            ));
        }

        //set what page currently on so that we can return here after form submission/cancellation
        $this->factory->getSession()->set('mautic.client.page', $page);

        //set some permissions
        $permissions = array(
            'create' => $this->factory->getSecurity()->isGranted('api:clients:create'),
            'edit'   => $this->factory->getSecurity()->isGranted('api:clients:editother'),
            'delete' => $this->factory->getSecurity()->isGranted('api:clients:deleteother'),
        );

        $parameters = array(
            'items'       => $clients,
            'page'        => $page,
            'limit'       => $limit,
            'permissions' => $permissions,
            'tmpl'        => $tmpl,
            'searchValue' => $filter
        );

        return $this->delegateView(array(
            'viewParameters'  => $parameters,
            'contentTemplate' => 'MauticApiBundle:Client:list.html.php',
            'passthroughVars' => array(
                'route'          => $this->generateUrl('mautic_client_index', array('page' => $page)),
                'mauticContent'  => 'client'
            )
        ));
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function authorizedClientsAction()
    {
        $me      = $this->get('security.context')->getToken()->getUser();
        $clients = $this->factory->getModel('api.client')->getUserClients($me);

        return $this->render('MauticApiBundle:Client:authorized.html.php', array('clients' => $clients));
    }

    /**
     * @param int $clientId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function revokeAction($clientId)
    {
        $success = 0;
        $flashes = array();

        if ($this->request->getMethod() == 'POST') {
            /** @var \Mautic\ApiBundle\Model\ClientModel $model */
            $model   = $this->factory->getModel('api.client');

            $client = $model->getEntity($clientId);

            if ($client === null) {
                $flashes[] = array(
                    'type'    => 'error',
                    'msg'     => 'mautic.api.client.error.notfound',
                    'msgVars' => array('%id%' => $clientId)
                );
            } else {
                $name = $client->getName();

                $model->revokeAccess($client);

                $flashes[] = array(
                    'type'    => 'notice',
                    'msg'     => 'mautic.api.client.notice.revoked',
                    'msgVars' => array(
                        '%name%' => $name
                    )
                );
            }
        }

        return $this->postActionRedirect(array(
            'returnUrl'       => $this->generateUrl('mautic_user_account'),
            'contentTemplate' => 'MauticUserBundle:Profile:index',
            'passthroughVars' => array(
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
    public function newAction()
    {
        if (!$this->factory->getSecurity()->isGranted('api:clients:create')) {
            return $this->accessDenied();
        }

        /** @var \Mautic\ApiBundle\Model\ClientModel $model */
        $model = $this->factory->getModel('api.client');

        //retrieve the entity
        $client = $model->getEntity();

        //set the return URL for post actions
        $returnUrl = $this->generateUrl('mautic_client_index');

        //get the user form factory
        $action = $this->generateUrl('mautic_client_action', array('objectAction' => 'new'));
        $form   = $model->createForm($client, $this->get('form.factory'), $action);

        //remove the client id and secret fields as they'll be auto generated
        $form->remove('randomId');
        $form->remove('secret');
        $form->remove('publicId');
        $form->remove('consumerKey');
        $form->remove('consumerSecret');

        ///Check for a submitted form and process it
        if ($this->request->getMethod() == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    //form is valid so process the data
                    $model->saveEntity($client);
                    $this->addFlash('mautic.api.client.notice.created', array(
                        '%name%'         => $client->getName(),
                        '%clientId%'     => $client->getPublicId(),
                        '%clientSecret%' => $client->getSecret(),
                        '%url%'          => $this->generateUrl('mautic_client_action', array(
                            'objectAction' => 'edit',
                            'objectId'     => $client->getId()
                        ))
                    ));
                }
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                return $this->postActionRedirect(array(
                    'returnUrl'       => $returnUrl,
                    'contentTemplate' => 'MauticApiBundle:Client:index',
                    'passthroughVars' => array(
                        'activeLink'    => '#mautic_client_index',
                        'mauticContent' => 'client'
                    )
                ));
            } elseif ($valid && !$cancelled) {
                return $this->editAction($client->getId(), false);
            }
        }

        return $this->delegateView(array(
            'viewParameters'  => array('form' => $form->createView()),
            'contentTemplate' => 'MauticApiBundle:Client:form.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_client_new',
                'route'         => $action,
                'mauticContent' => 'client'
            )
        ));
    }

    /**
     * Generates edit form and processes post data
     *
     * @param int  $objectId
     * @param bool $ignorePost
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction($objectId, $ignorePost = false)
    {
        if (!$this->factory->getSecurity()->isGranted('api:clients:editother')) {
            return $this->accessDenied();
        }
        /** @var \Mautic\ApiBundle\Model\ClientModel $model */
        $model     = $this->factory->getModel('api.client');
        $client    = $model->getEntity($objectId);
        $returnUrl = $this->generateUrl('mautic_client_index');

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'contentTemplate' => 'MauticApiBundle:Client:index',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_client_index',
                'mauticContent' => 'client'
            )
        );

        //client not found
        if ($client === null) {
            return $this->postActionRedirect(
                array_merge($postActionVars, array(
                    'flashes' => array(
                        array(
                            'type' => 'error',
                            'msg'  => 'mautic.api.client.error.notfound',
                            'msgVars' => array('%id%' => $objectId)
                        )
                    ))
                )
            );
        } elseif ($model->isLocked($client)) {
            //deny access if the entity is locked
            return $this->isLocked($postActionVars, $client, 'api.client');
        }

        $action = $this->generateUrl('mautic_client_action', array('objectAction' => 'edit', 'objectId' => $objectId));
        $form   = $model->createForm($client, $this->get('form.factory'), $action);

        ///Check for a submitted form and process it
        if (!$ignorePost && $this->request->getMethod() == 'POST') {
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    //form is valid so process the data
                    $model->saveEntity($client, $form->get('buttons')->get('save')->isClicked());
                    $this->addFlash('mautic.core.notice.updated', array(
                        '%name%'      => $client->getName(),
                        '%menu_link%' => 'mautic_client_index',
                        '%url%'       => $this->generateUrl('mautic_client_action', array(
                            'objectAction' => 'edit',
                            'objectId'     => $client->getId()
                        ))
                    ));

                    if ($form->get('buttons')->get('save')->isClicked()) {
                        return $this->postActionRedirect($postActionVars);
                    }
                }
            } else {
                //unlock the entity
                $model->unlockEntity($client);
                return $this->postActionRedirect($postActionVars);
            }
        } else {
            //lock the entity
            $model->lockEntity($client);
        }

        return $this->delegateView(array(
            'viewParameters'  => array('form' => $form->createView()),
            'contentTemplate' => 'MauticApiBundle:Client:form.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_client_index',
                'route'         => $action,
                'mauticContent' => 'client'
            )
        ));
    }

    /**
     * Deletes the entity
     *
     * @param int $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction($objectId)
    {
        if (!$this->factory->getSecurity()->isGranted('api:clients:delete')) {
            return $this->accessDenied();
        }

        $returnUrl = $this->generateUrl('mautic_client_index');
        $success   = 0;
        $flashes   = array();

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'contentTemplate' => 'MauticApiBundle:Client:index',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_client_index',
                'success'       => $success,
                'mauticContent' => 'client'
            )
        );

        if ($this->request->getMethod() == 'POST') {
            /** @var \Mautic\ApiBundle\Model\ClientModel $model */
            $model  = $this->factory->getModel('api.client');
            $entity = $model->getEntity($objectId);
            if ($entity === null) {
                $flashes[] = array(
                    'type' => 'error',
                    'msg'  => 'mautic.api.client.error.notfound',
                    'msgVars' => array('%id%' => $objectId)
                );
            } elseif ($model->isLocked($entity)) {
                //deny access if the entity is locked
                return $this->isLocked($postActionVars, $entity, 'api.client');
            } else {
                $model->deleteEntity($entity);
                $name      = $entity->getName();
                $flashes[] = array(
                    'type'    => 'notice',
                    'msg'     => 'mautic.core.notice.deleted',
                    'msgVars' => array(
                        '%name%' => $name,
                        '%id%'   => $objectId
                    )
                );
            }
        }

        return $this->postActionRedirect(
            array_merge($postActionVars, array(
                'flashes' => $flashes
            ))
        );
    }
}
