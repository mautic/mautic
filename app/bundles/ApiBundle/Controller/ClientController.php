<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class ClientController extends FormController
{
    /**
     * Generate's default client list.
     *
     * @param int $page
     *
     * @return JsonResponse|Response
     */
    public function indexAction($page = 1)
    {
        if (!$this->get('mautic.security')->isGranted('api:clients:view')) {
            return $this->accessDenied();
        }

        //set limits
        $limit = $this->get('session')->get('mautic.client.limit', $this->get('mautic.helper.core_parameters')->get('default_pagelimit'));
        $start = (1 === $page) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $orderBy    = $this->get('session')->get('mautic.client.orderby', 'c.name');
        $orderByDir = $this->get('session')->get('mautic.client.orderbydir', 'ASC');
        $filter     = $this->request->get('search', $this->get('session')->get('mautic.client.filter', ''));
        $apiMode    = $this->factory->getRequest()->get('api_mode', $this->get('session')->get('mautic.client.filter.api_mode', 'oauth1a'));
        $this->get('session')->set('mautic.client.filter.api_mode', $apiMode);
        $this->get('session')->set('mautic.client.filter', $filter);
        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        $clients = $this->getModel('api.client')->getEntities(
            [
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir,
            ]
        );

        $count = count($clients);
        if ($count && $count < ($start + 1)) {
            //the number of entities are now less then the current page so redirect to the last page
            $lastPage = (1 === $count) ? 1 : (ceil($count / $limit)) ?: 1;
            $this->get('session')->set('mautic.client.page', $lastPage);
            $returnUrl = $this->generateUrl('mautic_client_index', ['page' => $lastPage]);

            return $this->postActionRedirect(
                [
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => ['page' => $lastPage],
                    'contentTemplate' => 'MauticApiBundle:Client:index',
                    'passthroughVars' => [
                        'activeLink'    => 'mautic_client_index',
                        'mauticContent' => 'client',
                    ],
                ]
            );
        }

        //set what page currently on so that we can return here after form submission/cancellation
        $this->get('session')->set('mautic.client.page', $page);

        //set some permissions
        $permissions = [
            'create' => $this->get('mautic.security')->isGranted('api:clients:create'),
            'edit'   => $this->get('mautic.security')->isGranted('api:clients:editother'),
            'delete' => $this->get('mautic.security')->isGranted('api:clients:deleteother'),
        ];

        // filters
        $filters = [];

        // api options
        $apiOptions           = [];
        $apiOptions['oauth1'] = 'OAuth 1';
        $apiOptions['oauth2'] = 'OAuth 2';
        $filters['api_mode']  = [
            'values'  => [$apiMode],
            'options' => $apiOptions,
        ];

        $parameters = [
            'items'       => $clients,
            'page'        => $page,
            'limit'       => $limit,
            'permissions' => $permissions,
            'tmpl'        => $tmpl,
            'searchValue' => $filter,
            'filters'     => $filters,
        ];

        return $this->delegateView(
            [
                'viewParameters'  => $parameters,
                'contentTemplate' => 'MauticApiBundle:Client:list.html.php',
                'passthroughVars' => [
                    'route'         => $this->generateUrl('mautic_client_index', ['page' => $page]),
                    'mauticContent' => 'client',
                ],
            ]
        );
    }

    /**
     * @return Response
     */
    public function authorizedClientsAction()
    {
        $me      = $this->get('security.token_storage')->getToken()->getUser();
        $clients = $this->getModel('api.client')->getUserClients($me);

        return $this->render('MauticApiBundle:Client:authorized.html.php', ['clients' => $clients]);
    }

    /**
     * @param int $clientId
     *
     * @return JsonResponse|RedirectResponse
     */
    public function revokeAction($clientId)
    {
        $success = 0;
        $flashes = [];

        if ('POST' == $this->request->getMethod()) {
            /** @var \Mautic\ApiBundle\Model\ClientModel $model */
            $model = $this->getModel('api.client');

            $client = $model->getEntity($clientId);

            if (null === $client) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.api.client.error.notfound',
                    'msgVars' => ['%id%' => $clientId],
                ];
            } else {
                $name = $client->getName();

                $model->revokeAccess($client);

                $flashes[] = [
                    'type'    => 'notice',
                    'msg'     => 'mautic.api.client.notice.revoked',
                    'msgVars' => [
                        '%name%' => $name,
                    ],
                ];
            }
        }

        return $this->postActionRedirect(
            [
                'returnUrl'       => $this->generateUrl('mautic_user_account'),
                'contentTemplate' => 'MauticUserBundle:Profile:index',
                'passthroughVars' => [
                    'success' => $success,
                ],
                'flashes' => $flashes,
            ]
        );
    }

    /**
     * @param mixed $objectId
     *
     * @return array|JsonResponse|RedirectResponse|Response
     */
    public function newAction($objectId = 0)
    {
        if (!$this->get('mautic.security')->isGranted('api:clients:create')) {
            return $this->accessDenied();
        }

        $apiMode = (0 === $objectId) ? $this->get('session')->get('mautic.client.filter.api_mode', 'oauth1a') : $objectId;
        $this->get('session')->set('mautic.client.filter.api_mode', $apiMode);

        /** @var \Mautic\ApiBundle\Model\ClientModel $model */
        $model = $this->getModel('api.client');
        $model->setApiMode($apiMode);

        //retrieve the entity
        $client = $model->getEntity();

        //set the return URL for post actions
        $returnUrl = $this->generateUrl('mautic_client_index');

        //get the user form factory
        $action = $this->generateUrl('mautic_client_action', ['objectAction' => 'new']);
        $form   = $model->createForm($client, $this->get('form.factory'), $action);

        //remove the client id and secret fields as they'll be auto generated
        $form->remove('randomId');
        $form->remove('secret');
        $form->remove('publicId');
        $form->remove('consumerKey');
        $form->remove('consumerSecret');

        ///Check for a submitted form and process it
        if ('POST' == $this->request->getMethod()) {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    //form is valid so process the data
                    $model->saveEntity($client);
                    $this->addFlash(
                        'mautic.api.client.notice.created',
                        [
                            '%name%'         => $client->getName(),
                            '%clientId%'     => $client->getPublicId(),
                            '%clientSecret%' => $client->getSecret(),
                            '%url%'          => $this->generateUrl(
                                'mautic_client_action',
                                [
                                    'objectAction' => 'edit',
                                    'objectId'     => $client->getId(),
                                ]
                            ),
                        ]
                    );
                }
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                return $this->postActionRedirect(
                    [
                        'returnUrl'       => $returnUrl,
                        'contentTemplate' => 'MauticApiBundle:Client:index',
                        'passthroughVars' => [
                            'activeLink'    => '#mautic_client_index',
                            'mauticContent' => 'client',
                        ],
                    ]
                );
            } elseif ($valid && !$cancelled) {
                return $this->editAction($client->getId(), true);
            }
        }

        return $this->delegateView(
            [
                'viewParameters' => [
                    'form' => $form->createView(),
                    'tmpl' => $this->request->get('tmpl', 'form'),
                ],
                'contentTemplate' => 'MauticApiBundle:Client:form.html.php',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_client_new',
                    'route'         => $action,
                    'mauticContent' => 'client',
                ],
            ]
        );
    }

    /**
     * Generates edit form and processes post data.
     *
     * @param int  $objectId
     * @param bool $ignorePost
     *
     * @return JsonResponse|RedirectResponse|Response
     */
    public function editAction($objectId, $ignorePost = false)
    {
        if (!$this->get('mautic.security')->isGranted('api:clients:editother')) {
            return $this->accessDenied();
        }

        /** @var \Mautic\ApiBundle\Model\ClientModel $model */
        $model     = $this->getModel('api.client');
        $client    = $model->getEntity($objectId);
        $returnUrl = $this->generateUrl('mautic_client_index');

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'contentTemplate' => 'MauticApiBundle:Client:index',
            'passthroughVars' => [
                'activeLink'    => '#mautic_client_index',
                'mauticContent' => 'client',
            ],
        ];

        //client not found
        if (null === $client) {
            return $this->postActionRedirect(
                array_merge(
                    $postActionVars,
                    [
                        'flashes' => [
                            [
                                'type'    => 'error',
                                'msg'     => 'mautic.api.client.error.notfound',
                                'msgVars' => ['%id%' => $objectId],
                            ],
                        ],
                    ]
                )
            );
        } elseif ($model->isLocked($client)) {
            //deny access if the entity is locked
            return $this->isLocked($postActionVars, $client, 'api.client');
        }

        $action = $this->generateUrl('mautic_client_action', ['objectAction' => 'edit', 'objectId' => $objectId]);
        $form   = $model->createForm($client, $this->get('form.factory'), $action);

        // remove api_mode field
        $form->remove('api_mode');

        ///Check for a submitted form and process it
        if (!$ignorePost && 'POST' == $this->request->getMethod()) {
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    //form is valid so process the data
                    $model->saveEntity($client, $form->get('buttons')->get('save')->isClicked());
                    $this->addFlash(
                        'mautic.core.notice.updated',
                        [
                            '%name%'      => $client->getName(),
                            '%menu_link%' => 'mautic_client_index',
                            '%url%'       => $this->generateUrl(
                                'mautic_client_action',
                                [
                                    'objectAction' => 'edit',
                                    'objectId'     => $client->getId(),
                                ]
                            ),
                        ]
                    );

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

        return $this->delegateView(
            [
                'viewParameters' => [
                    'form' => $form->createView(),
                    'tmpl' => $this->request->get('tmpl', 'form'),
                ],
                'contentTemplate' => 'MauticApiBundle:Client:form.html.php',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_client_index',
                    'route'         => $action,
                    'mauticContent' => 'client',
                ],
            ]
        );
    }

    /**
     * Deletes the entity.
     *
     * @param int $objectId
     *
     * @return JsonResponse|RedirectResponse
     */
    public function deleteAction($objectId)
    {
        if (!$this->get('mautic.security')->isGranted('api:clients:delete')) {
            return $this->accessDenied();
        }

        $returnUrl = $this->generateUrl('mautic_client_index');
        $success   = 0;
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'contentTemplate' => 'MauticApiBundle:Client:index',
            'passthroughVars' => [
                'activeLink'    => '#mautic_client_index',
                'success'       => $success,
                'mauticContent' => 'client',
            ],
        ];

        if ('POST' == $this->request->getMethod()) {
            /** @var \Mautic\ApiBundle\Model\ClientModel $model */
            $model  = $this->getModel('api.client');
            $entity = $model->getEntity($objectId);
            if (null === $entity) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.api.client.error.notfound',
                    'msgVars' => ['%id%' => $objectId],
                ];
            } elseif ($model->isLocked($entity)) {
                //deny access if the entity is locked
                return $this->isLocked($postActionVars, $entity, 'api.client');
            } else {
                $model->deleteEntity($entity);
                $name      = $entity->getName();
                $flashes[] = [
                    'type'    => 'notice',
                    'msg'     => 'mautic.core.notice.deleted',
                    'msgVars' => [
                        '%name%' => $name,
                        '%id%'   => $objectId,
                    ],
                ];
            }
        }

        return $this->postActionRedirect(
            array_merge(
                $postActionVars,
                [
                    'flashes' => $flashes,
                ]
            )
        );
    }
}
