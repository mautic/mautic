<?php

namespace Mautic\ApiBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\ApiBundle\Model\ClientModel;
use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Factory\PageHelperFactoryInterface;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\FormBundle\Helper\FormFieldHelper;
use Mautic\UserBundle\Entity\User;
use OAuth2\OAuth2;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ClientController extends FormController
{
    public function __construct(
        private ClientModel $clientModel,
        FormFactoryInterface $formFactory,
        FormFieldHelper $fieldHelper,
        ManagerRegistry $doctrine,
        MauticFactory $factory,
        ModelFactory $modelFactory,
        UserHelper $userHelper,
        CoreParametersHelper $coreParametersHelper,
        EventDispatcherInterface $dispatcher,
        Translator $translator,
        FlashBag $flashBag,
        RequestStack $requestStack,
        CorePermissions $security
    ) {
        parent::__construct($formFactory, $fieldHelper, $doctrine, $factory, $modelFactory, $userHelper, $coreParametersHelper, $dispatcher, $translator, $flashBag, $requestStack, $security);
    }

    /**
     * Generate's default client list.
     *
     * @param int $page
     *
     * @return Response
     */
    public function indexAction(Request $request, PageHelperFactoryInterface $pageHelperFactory, $page = 1)
    {
        if (!$this->security->isGranted('api:clients:view')) {
            return $this->accessDenied();
        }

        $pageHelper= $pageHelperFactory->make('mautic.client', $page);
        $limit     = $pageHelper->getLimit();
        $start     = $pageHelper->getStart();
        $orderBy   = $request->getSession()->get('mautic.client.orderby', 'c.name');
        $orderByDir= $request->getSession()->get('mautic.client.orderbydir', 'ASC');
        $filter    = $request->get('search', $request->getSession()->get('mautic.client.filter', ''));
        $apiMode   = $this->factory->getRequest()->get('api_mode', $request->getSession()->get('mautic.client.filter.api_mode', 'oauth2'));
        $request->getSession()->set('mautic.client.filter.api_mode', $apiMode);
        $request->getSession()->set('mautic.client.filter', $filter);

        $clients = $this->clientModel->getEntities(
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
            $lastPage  = $pageHelper->countPage($count);
            $returnUrl = $this->generateUrl('mautic_client_index', ['page' => $lastPage]);
            $pageHelper->rememberPage($lastPage);

            return $this->postActionRedirect(
                [
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => ['page' => $lastPage],
                    'contentTemplate' => 'Mautic\ApiBundle\Controller\ClientController::indexAction',
                    'passthroughVars' => [
                        'activeLink'    => 'mautic_client_index',
                        'mauticContent' => 'client',
                    ],
                ]
            );
        }

        $pageHelper->rememberPage($page);

        // filters
        $filters = [];

        // api options
        $apiOptions           = [];
        $apiOptions['oauth2'] = 'OAuth 2';
        $filters['api_mode']  = [
            'values'  => [$apiMode],
            'options' => $apiOptions,
        ];

        return $this->delegateView(
            [
                'viewParameters'  => [
                    'items'       => $clients,
                    'page'        => $page,
                    'limit'       => $limit,
                    'permissions' => [
                        'create' => $this->security->isGranted('api:clients:create'),
                        'edit'   => $this->security->isGranted('api:clients:editother'),
                        'delete' => $this->security->isGranted('api:clients:deleteother'),
                    ],
                    'tmpl'        => $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index',
                    'searchValue' => $filter,
                    'filters'     => $filters,
                ],
                'contentTemplate' => '@MauticApi/Client/list.html.twig',
                'passthroughVars' => [
                    'route'         => $this->generateUrl('mautic_client_index', ['page' => $page]),
                    'mauticContent' => 'client',
                ],
            ]
        );
    }

    public function authorizedClientsAction(TokenStorageInterface $tokenStorage): Response
    {
        $apiClientModel = $this->clientModel;
        \assert($apiClientModel instanceof ClientModel);
        $me = $tokenStorage->getToken()->getUser();
        \assert($me instanceof User);
        $clients = $apiClientModel->getUserClients($me);

        return $this->render('@MauticApi/Client/authorized.html.twig', ['clients' => $clients]);
    }

    /**
     * @param int $clientId
     *
     * @return Response
     */
    public function revokeAction(Request $request, $clientId)
    {
        $success = 0;
        $flashes = [];

        if ('POST' == $request->getMethod()) {
            /** @var \Mautic\ApiBundle\Model\ClientModel $model */
            $model = $this->clientModel;

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
                'contentTemplate' => 'Mautic\UserBundle\Controller\ProfileController::indexAction',
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
    public function newAction(Request $request, $objectId = 0)
    {
        if (!$this->security->isGranted('api:clients:create')) {
            return $this->accessDenied();
        }

        $apiMode = (0 === $objectId) ? $request->getSession()->get('mautic.client.filter.api_mode', 'oauth2') : $objectId;
        $request->getSession()->set('mautic.client.filter.api_mode', $apiMode);

        /** @var \Mautic\ApiBundle\Model\ClientModel $model */
        $model = $this->clientModel;
        $model->setApiMode($apiMode);

        // retrieve the entity
        $client = $model->getEntity();

        // set the return URL for post actions
        $returnUrl = $this->generateUrl('mautic_client_index');

        // get the user form factory
        $action = $this->generateUrl('mautic_client_action', ['objectAction' => 'new']);
        $form   = $model->createForm($client, $this->formFactory, $action);

        // remove the client id and secret fields as they'll be auto generated
        $form->remove('randomId');
        $form->remove('secret');
        $form->remove('publicId');
        $form->remove('consumerKey');
        $form->remove('consumerSecret');

        // /Check for a submitted form and process it
        if ('POST' == $request->getMethod()) {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    // form is valid so process the data
                    // If the admin is creating API credentials, enable 'Client Credential' grant type
                    /** @var User $user */
                    $user = $this->getUser();
                    if (ClientModel::API_MODE_OAUTH2 == $apiMode && $user->getRole()->isAdmin()) {
                        $client->addGrantType(OAuth2::GRANT_TYPE_CLIENT_CREDENTIALS);
                    }
                    $client->setRole($user->getRole());
                    $model->saveEntity($client);
                    $this->addFlashMessage(
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

            if ($cancelled || ($valid && $this->getFormButton($form, ['buttons', 'save'])->isClicked())) {
                return $this->postActionRedirect(
                    [
                        'returnUrl'       => $returnUrl,
                        'contentTemplate' => 'Mautic\ApiBundle\Controller\ClientController::indexAction',
                        'passthroughVars' => [
                            'activeLink'    => '#mautic_client_index',
                            'mauticContent' => 'client',
                        ],
                    ]
                );
            } elseif ($valid && !$cancelled) {
                return $this->editAction($request, $client->getId(), true);
            }
        }

        return $this->delegateView(
            [
                'viewParameters' => [
                    'form' => $form->createView(),
                    'tmpl' => $request->get('tmpl', 'form'),
                ],
                'contentTemplate' => '@MauticApi/Client/form.html.twig',
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
    public function editAction(Request $request, $objectId, $ignorePost = false)
    {
        if (!$this->security->isGranted('api:clients:editother')) {
            return $this->accessDenied();
        }

        /** @var \Mautic\ApiBundle\Model\ClientModel $model */
        $model     = $this->clientModel;
        $client    = $model->getEntity($objectId);
        $returnUrl = $this->generateUrl('mautic_client_index');

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'contentTemplate' => 'Mautic\ApiBundle\Controller\ClientController::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_client_index',
                'mauticContent' => 'client',
            ],
        ];

        // client not found
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
            // deny access if the entity is locked
            return $this->isLocked($postActionVars, $client, 'api.client');
        }

        $action = $this->generateUrl('mautic_client_action', ['objectAction' => 'edit', 'objectId' => $objectId]);
        $form   = $model->createForm($client, $this->formFactory, $action);

        // remove api_mode field
        $form->remove('api_mode');

        // /Check for a submitted form and process it
        if (!$ignorePost && 'POST' == $request->getMethod()) {
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    // form is valid so process the data
                    $model->saveEntity($client, $this->getFormButton($form, ['buttons', 'save'])->isClicked());
                    $this->addFlashMessage(
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

                    if ($this->getFormButton($form, ['buttons', 'save'])->isClicked()) {
                        return $this->postActionRedirect($postActionVars);
                    }
                }
            } else {
                // unlock the entity
                $model->unlockEntity($client);

                return $this->postActionRedirect($postActionVars);
            }
        } else {
            // lock the entity
            $model->lockEntity($client);
        }

        return $this->delegateView(
            [
                'viewParameters' => [
                    'form' => $form->createView(),
                    'tmpl' => $request->get('tmpl', 'form'),
                ],
                'contentTemplate' => '@MauticApi/Client/form.html.twig',
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
     * @return Response
     */
    public function deleteAction(Request $request, $objectId)
    {
        if (!$this->security->isGranted('api:clients:delete')) {
            return $this->accessDenied();
        }

        $returnUrl = $this->generateUrl('mautic_client_index');
        $success   = 0;
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'contentTemplate' => 'Mautic\ApiBundle\Controller\ClientController::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_client_index',
                'success'       => $success,
                'mauticContent' => 'client',
            ],
        ];

        if ('POST' === $request->getMethod()) {
            /** @var \Mautic\ApiBundle\Model\ClientModel $model */
            $model  = $this->clientModel;
            $entity = $model->getEntity($objectId);
            if (null === $entity) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.api.client.error.notfound',
                    'msgVars' => ['%id%' => $objectId],
                ];
            } elseif ($model->isLocked($entity)) {
                // deny access if the entity is locked
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
