<?php

namespace Mautic\NotificationBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Factory\PageHelperFactoryInterface;
use Mautic\CoreBundle\Form\Type\DateRangeType;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\LeadBundle\Controller\EntityContactsTrait;
use Mautic\NotificationBundle\Entity\Notification;
use Mautic\NotificationBundle\Model\NotificationModel;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MobileNotificationController extends FormController
{
    use EntityContactsTrait;

    /**
     * @param int $page
     *
     * @return JsonResponse|Response
     */
    public function indexAction(Request $request, $page = 1)
    {
        /** @var NotificationModel $model */
        $model = $this->getModel('notification');

        // set some permissions
        $permissions = $this->security->isGranted(
            [
                'notification:mobile_notifications:viewown',
                'notification:mobile_notifications:viewother',
                'notification:mobile_notifications:create',
                'notification:mobile_notifications:editown',
                'notification:mobile_notifications:editother',
                'notification:mobile_notifications:deleteown',
                'notification:mobile_notifications:deleteother',
                'notification:mobile_notifications:publishown',
                'notification:mobile_notifications:publishother',
            ],
            'RETURN_ARRAY'
        );

        if (!$permissions['notification:mobile_notifications:viewown'] && !$permissions['notification:mobile_notifications:viewother']) {
            return $this->accessDenied();
        }

        $session = $request->getSession();

        // set limits
        $limit = $session->get('mautic.mobile_notification.limit', $this->coreParametersHelper->get('default_pagelimit'));
        $start = (1 === $page) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search = $request->get('search', $session->get('mautic.mobile_notification.filter', ''));
        $session->set('mautic.mobile_notification.filter', $search);

        $filter = [
            'string' => $search,
            'where'  => [
                [
                    'expr' => 'eq',
                    'col'  => 'mobile',
                    'val'  => 1,
                ],
            ],
        ];

        if (!$permissions['notification:mobile_notifications:viewother']) {
            $filter['force'][] =
                ['column' => 'e.createdBy', 'expr' => 'eq', 'value' => $this->user->getId()];
        }

        $orderBy    = $session->get('mautic.mobile_notification.orderby', 'e.name');
        $orderByDir = $session->get('mautic.mobile_notification.orderbydir', 'DESC');

        $notifications = $model->getEntities(
            [
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir,
            ]
        );

        $count = count($notifications);
        if ($count && $count < ($start + 1)) {
            // the number of entities are now less then the current page so redirect to the last page
            if (1 === $count) {
                $lastPage = 1;
            } else {
                $lastPage = (floor($count / $limit)) ?: 1;
            }

            $session->set('mautic.mobile_notification.page', $lastPage);
            $returnUrl = $this->generateUrl('mautic_mobile_notification_index', ['page' => $lastPage]);

            return $this->postActionRedirect(
                [
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => ['page' => $lastPage],
                    'contentTemplate' => 'Mautic\NotificationBundle\Controller\MobileNotificationController::indexAction',
                    'passthroughVars' => [
                        'activeLink'    => '#mautic_mobile_notification_index',
                        'mauticContent' => 'mobile_notification',
                    ],
                ]
            );
        }
        $session->set('mautic.mobile_notification.page', $page);

        return $this->delegateView(
            [
                'viewParameters' => [
                    'searchValue' => $search,
                    'items'       => $notifications,
                    'totalItems'  => $count,
                    'page'        => $page,
                    'limit'       => $limit,
                    'tmpl'        => $request->get('tmpl', 'index'),
                    'permissions' => $permissions,
                    'model'       => $model,
                    'security'    => $this->security,
                ],
                'contentTemplate' => '@MauticNotification/MobileNotification/list.html.twig',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_mobile_notification_index',
                    'mauticContent' => 'mobile_notification',
                    'route'         => $this->generateUrl('mautic_mobile_notification_index', ['page' => $page]),
                ],
            ]
        );
    }

    /**
     * Loads a specific form into the detailed panel.
     *
     * @return JsonResponse|Response
     */
    public function viewAction(Request $request, $objectId)
    {
        /** @var NotificationModel $model */
        $model    = $this->getModel('notification');
        $security = $this->security;

        /** @var Notification $notification */
        $notification = $model->getEntity($objectId);
        // set the page we came from
        $page = $request->getSession()->get('mautic.mobile_notification.page', 1);

        if (null === $notification) {
            // set the return URL
            $returnUrl = $this->generateUrl('mautic_mobile_notification_index', ['page' => $page]);

            return $this->postActionRedirect(
                [
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => ['page' => $page],
                    'contentTemplate' => 'Mautic\NotificationBundle\Controller\MobileNotificationController::indexAction',
                    'passthroughVars' => [
                        'activeLink'    => '#mautic_mobile_notification_index',
                        'mauticContent' => 'mobile_notification',
                    ],
                    'flashes' => [
                        [
                            'type'    => 'error',
                            'msg'     => 'mautic.notification.error.notfound',
                            'msgVars' => ['%id%' => $objectId],
                        ],
                    ],
                ]
            );
        } elseif (!$this->security->hasEntityAccess(
            'notification:mobile_notifications:viewown',
            'notification:mobile_notifications:viewother',
            $notification->getCreatedBy()
        )
        ) {
            return $this->accessDenied();
        }

        // Audit Log
        $auditLogModel = $this->getModel('core.auditlog');
        \assert($auditLogModel instanceof AuditLogModel);
        $logs = $auditLogModel->getLogForObject('notification', $notification->getId(), $notification->getDateAdded());

        // Init the date range filter form
        $dateRangeValues = $request->query->all()['daterange'] ?? $request->request->all()['daterange'] ?? [];
        $action          = $this->generateUrl('mautic_mobile_notification_action', ['objectAction' => 'view', 'objectId' => $objectId]);
        $dateRangeForm   = $this->formFactory->create(DateRangeType::class, $dateRangeValues, ['action' => $action]);
        $entityViews     = $model->getHitsLineChartData(
            null,
            new \DateTime($dateRangeForm->get('date_from')->getData()),
            new \DateTime($dateRangeForm->get('date_to')->getData()),
            null,
            ['notification_id' => $notification->getId()]
        );

        // Get click through stats
        $trackableLinks = $model->getNotificationClickStats($notification->getId());

        return $this->delegateView([
            'returnUrl'      => $this->generateUrl('mautic_mobile_notification_action', ['objectAction' => 'view', 'objectId' => $notification->getId()]),
            'viewParameters' => [
                'notification' => $notification,
                'trackables'   => $trackableLinks,
                'logs'         => $logs,
                'permissions'  => $security->isGranted([
                    'notification:mobile_notifications:viewown',
                    'notification:mobile_notifications:viewother',
                    'notification:mobile_notifications:create',
                    'notification:mobile_notifications:editown',
                    'notification:mobile_notifications:editother',
                    'notification:mobile_notifications:deleteown',
                    'notification:mobile_notifications:deleteother',
                    'notification:mobile_notifications:publishown',
                    'notification:mobile_notifications:publishother',
                ], 'RETURN_ARRAY'),
                'security'    => $security,
                'entityViews' => $entityViews,
                'contacts'    => $this->forward(
                    'Mautic\NotificationBundle\Controller\MobileNotificationController::contactsAction',
                    [
                        'objectId'   => $notification->getId(),
                        'page'       => $request->getSession()->get('mautic.mobile_notification.contact.page', 1),
                        'ignoreAjax' => true,
                    ]
                )->getContent(),
                'dateRangeForm' => $dateRangeForm->createView(),
            ],
            'contentTemplate' => '@MauticNotification/MobileNotification/details.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_mobile_notification_index',
                'mauticContent' => 'mobile_notification',
            ],
        ]);
    }

    /**
     * Generates new form and processes post data.
     *
     * @param Notification $entity
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function newAction(Request $request, IntegrationHelper $integrationHelper, $entity = null)
    {
        /** @var NotificationModel $model */
        $model = $this->getModel('notification');

        if (!$entity instanceof Notification) {
            /** @var Notification $entity */
            $entity = $model->getEntity();
        }

        $method  = $request->getMethod();
        $session = $request->getSession();

        if (!$this->security->isGranted('notification:mobile_notifications:create')) {
            return $this->accessDenied();
        }

        // set the page we came from
        $page         = $session->get('mautic.mobile_notification.page', 1);
        $action       = $this->generateUrl('mautic_mobile_notification_action', ['objectAction' => 'new']);
        $notification = $request->request->all()['notification'] ?? [];
        $updateSelect = 'POST' === $method
            ? ($notification['updateSelect'] ?? false)
            : $request->get('updateSelect', false);

        if ($updateSelect) {
            $entity->setNotificationType('template');
        }

        // create the form
        $form = $model->createForm($entity, $this->formFactory, $action, ['update_select' => $updateSelect]);

        // /Check for a submitted form and process it
        if ('POST' === $method) {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    // form is valid so process the data
                    $model->saveEntity($entity);

                    $this->addFlashMessage(
                        'mautic.core.notice.created',
                        [
                            '%name%'      => $entity->getName(),
                            '%menu_link%' => 'mautic_mobile_notification_index',
                            '%url%'       => $this->generateUrl(
                                'mautic_mobile_notification_action',
                                [
                                    'objectAction' => 'edit',
                                    'objectId'     => $entity->getId(),
                                ]
                            ),
                        ]
                    );

                    if ($this->getFormButton($form, ['buttons', 'save'])->isClicked()) {
                        $viewParameters = [
                            'objectAction' => 'view',
                            'objectId'     => $entity->getId(),
                        ];
                        $returnUrl = $this->generateUrl('mautic_mobile_notification_action', $viewParameters);
                        $template  = 'Mautic\NotificationBundle\Controller\MobileNotificationController::viewAction';
                    } else {
                        // return edit view so that all the session stuff is loaded
                        return $this->editAction($request, $integrationHelper, $entity->getId(), true);
                    }
                }
            } else {
                $viewParameters = ['page' => $page];
                $returnUrl      = $this->generateUrl('mautic_mobile_notification_index', $viewParameters);
                $template       = 'Mautic\NotificationBundle\Controller\MobileNotificationController::indexAction';
                // clear any modified content
                $session->remove('mautic.mobile_notification.'.$entity->getId().'.content');
            }

            $passthrough = [
                'activeLink'    => 'mautic_mobile_notification_index',
                'mauticContent' => 'mobile_notification',
            ];

            // Check to see if this is a popup
            if (isset($form['updateSelect'])) {
                $template    = false;
                $passthrough = array_merge(
                    $passthrough,
                    [
                        'updateSelect' => $form['updateSelect']->getData(),
                        'id'           => $entity->getId(),
                        'name'         => $entity->getName(),
                        'group'        => $entity->getLanguage(),
                    ]
                );
            }

            if ($cancelled || ($valid && $this->getFormButton($form, ['buttons', 'save'])->isClicked())) {
                return $this->postActionRedirect(
                    [
                        'returnUrl'       => $returnUrl,
                        'viewParameters'  => $viewParameters,
                        'contentTemplate' => $template,
                        'passthroughVars' => $passthrough,
                    ]
                );
            }
        }

        $integration = $integrationHelper->getIntegrationObject('OneSignal');

        return $this->delegateView(
            [
                'viewParameters' => [
                    'form'         => $form->createView(),
                    'notification' => $entity,
                    'integration'  => $integration,
                ],
                'contentTemplate' => '@MauticNotification/MobileNotification/form.html.twig',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_mobile_notification_index',
                    'mauticContent' => 'mobile_notification',
                    'updateSelect'  => InputHelper::clean($request->query->get('updateSelect')),
                    'route'         => $this->generateUrl(
                        'mautic_mobile_notification_action',
                        [
                            'objectAction' => 'new',
                        ]
                    ),
                ],
            ]
        );
    }

    /**
     * @param bool $ignorePost
     * @param bool $forceTypeSelection
     *
     * @return array|JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function editAction(Request $request, IntegrationHelper $integrationHelper, $objectId, $ignorePost = false, $forceTypeSelection = false)
    {
        /** @var NotificationModel $model */
        $model   = $this->getModel('notification');
        $method  = $request->getMethod();
        $entity  = $model->getEntity($objectId);
        $session = $request->getSession();
        $page    = $session->get('mautic.mobile_notification.page', 1);

        // set the return URL
        $returnUrl = $this->generateUrl('mautic_mobile_notification_index', ['page' => $page]);

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\NotificationBundle\Controller\MobileNotificationController::indexAction',
            'passthroughVars' => [
                'activeLink'    => 'mautic_mobile_notification_index',
                'mauticContent' => 'mobile_notification',
            ],
        ];

        // not found
        if (null === $entity) {
            return $this->postActionRedirect(
                array_merge(
                    $postActionVars,
                    [
                        'flashes' => [
                            [
                                'type'    => 'error',
                                'msg'     => 'mautic.notification.error.notfound',
                                'msgVars' => ['%id%' => $objectId],
                            ],
                        ],
                    ]
                )
            );
        } elseif (!$this->security->hasEntityAccess(
            'notification:mobile_notifications:viewown',
            'notification:mobile_notifications:viewother',
            $entity->getCreatedBy()
        )
        ) {
            return $this->accessDenied();
        } elseif ($model->isLocked($entity)) {
            // deny access if the entity is locked
            return $this->isLocked($postActionVars, $entity, 'notification');
        }

        // Create the form
        $action       = $this->generateUrl('mautic_mobile_notification_action', ['objectAction' => 'edit', 'objectId' => $objectId]);
        $notification = $request->request->all()['notification'] ?? [];
        $updateSelect = 'POST' === $method
            ? ($notification['updateSelect'] ?? false)
            : $request->get('updateSelect', false);

        $form = $model->createForm($entity, $this->formFactory, $action, ['update_select' => $updateSelect]);

        // /Check for a submitted form and process it
        if (!$ignorePost && 'POST' == $method) {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    // form is valid so process the data
                    $model->saveEntity($entity, $this->getFormButton($form, ['buttons', 'save'])->isClicked());

                    $this->addFlashMessage(
                        'mautic.core.notice.updated',
                        [
                            '%name%'      => $entity->getName(),
                            '%menu_link%' => 'mautic_mobile_notification_index',
                            '%url%'       => $this->generateUrl(
                                'mautic_mobile_notification_action',
                                [
                                    'objectAction' => 'edit',
                                    'objectId'     => $entity->getId(),
                                ]
                            ),
                        ],
                        'warning'
                    );
                }
            } else {
                // clear any modified content
                $session->remove('mautic.mobile_notification.'.$objectId.'.content');
                // unlock the entity
                $model->unlockEntity($entity);
            }

            $template    = 'Mautic\NotificationBundle\Controller\MobileNotificationController::viewAction';
            $passthrough = [
                'activeLink'    => 'mautic_mobile_notification_index',
                'mauticContent' => 'mobile_notification',
            ];

            // Check to see if this is a popup
            if (isset($form['updateSelect'])) {
                $template    = false;
                $passthrough = array_merge(
                    $passthrough,
                    [
                        'updateSelect' => $form['updateSelect']->getData(),
                        'id'           => $entity->getId(),
                        'name'         => $entity->getName(),
                        'group'        => $entity->getLanguage(),
                    ]
                );
            }

            if ($cancelled || ($valid && $this->getFormButton($form, ['buttons', 'save'])->isClicked())) {
                $viewParameters = [
                    'objectAction' => 'view',
                    'objectId'     => $entity->getId(),
                ];

                return $this->postActionRedirect(
                    array_merge(
                        $postActionVars,
                        [
                            'returnUrl'       => $this->generateUrl('mautic_mobile_notification_action', $viewParameters),
                            'viewParameters'  => $viewParameters,
                            'contentTemplate' => $template,
                            'passthroughVars' => $passthrough,
                        ]
                    )
                );
            }
        } else {
            // lock the entity
            $model->lockEntity($entity);
        }

        $integration = $integrationHelper->getIntegrationObject('OneSignal');

        return $this->delegateView(
            [
                'viewParameters' => [
                    'form'               => $form->createView(),
                    'notification'       => $entity,
                    'forceTypeSelection' => $forceTypeSelection,
                    'integration'        => $integration,
                ],
                'contentTemplate' => '@MauticNotification/MobileNotification/form.html.twig',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_mobile_notification_index',
                    'mauticContent' => 'mobile_notification',
                    'updateSelect'  => InputHelper::clean($request->query->get('updateSelect')),
                    'route'         => $this->generateUrl(
                        'mautic_mobile_notification_action',
                        [
                            'objectAction' => 'edit',
                            'objectId'     => $entity->getId(),
                        ]
                    ),
                ],
            ]
        );
    }

    /**
     * Clone an entity.
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function cloneAction(Request $request, IntegrationHelper $integrationHelper, $objectId)
    {
        $model  = $this->getModel('notification');
        $entity = $model->getEntity($objectId);

        if (null != $entity) {
            if (!$this->security->isGranted('notification:mobile_notifications:create')
                || !$this->security->hasEntityAccess(
                    'notification:mobile_notifications:viewown',
                    'notification:mobile_notifications:viewother',
                    $entity->getCreatedBy()
                )
            ) {
                return $this->accessDenied();
            }

            $entity      = clone $entity;
            $session     = $request->getSession();
            $contentName = 'mautic.mobile_notification.'.$entity->getId().'.content';

            $session->set($contentName, $entity->getContent());
        }

        return $this->newAction($request, $integrationHelper, $entity);
    }

    /**
     * Deletes the entity.
     *
     * @return Response
     */
    public function deleteAction(Request $request, $objectId)
    {
        $page      = $request->getSession()->get('mautic.mobile_notification.page', 1);
        $returnUrl = $this->generateUrl('mautic_mobile_notification_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\NotificationBundle\Controller\MobileNotificationController::indexAction',
            'passthroughVars' => [
                'activeLink'    => 'mautic_mobile_notification_index',
                'mauticContent' => 'mobile_notification',
            ],
        ];

        if (Request::METHOD_POST === $request->getMethod()) {
            $model = $this->getModel('notification');
            \assert($model instanceof NotificationModel);
            $entity = $model->getEntity($objectId);

            if (null === $entity) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.notification.error.notfound',
                    'msgVars' => ['%id%' => $objectId],
                ];
            } elseif (!$this->security->hasEntityAccess(
                'notification:mobile_notifications:deleteown',
                'notification:mobile_notifications:deleteother',
                $entity->getCreatedBy()
            )
            ) {
                return $this->accessDenied();
            } elseif ($model->isLocked($entity)) {
                return $this->isLocked($postActionVars, $entity, 'notification');
            }

            $model->deleteEntity($entity);

            $flashes[] = [
                'type'    => 'notice',
                'msg'     => 'mautic.core.notice.deleted',
                'msgVars' => [
                    '%name%' => $entity->getName(),
                    '%id%'   => $objectId,
                ],
            ];
        } // else don't do anything

        return $this->postActionRedirect(
            array_merge(
                $postActionVars,
                [
                    'flashes' => $flashes,
                ]
            )
        );
    }

    /**
     * Deletes a group of entities.
     */
    public function batchDeleteAction(Request $request): Response
    {
        $page      = $request->getSession()->get('mautic.mobile_notification.page', 1);
        $returnUrl = $this->generateUrl('mautic_mobile_notification_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\NotificationBundle\Controller\MobileNotificationController::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_mobile_notification_index',
                'mauticContent' => 'mobile_notification',
            ],
        ];

        if (Request::METHOD_POST === $request->getMethod()) {
            $model = $this->getModel('notification');
            \assert($model instanceof NotificationModel);
            $ids = json_decode($request->query->get('ids', '{}'));

            $deleteIds = [];

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $model->getEntity($objectId);

                if (null === $entity) {
                    $flashes[] = [
                        'type'    => 'error',
                        'msg'     => 'mautic.notification.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ];
                } elseif (!$this->security->hasEntityAccess(
                    'notification:mobile_notifications:viewown',
                    'notification:mobile_notifications:viewother',
                    $entity->getCreatedBy()
                )
                ) {
                    $flashes[] = $this->accessDenied(true);
                } elseif ($model->isLocked($entity)) {
                    $flashes[] = $this->isLocked($postActionVars, $entity, 'notification', true);
                } else {
                    $deleteIds[] = $objectId;
                }
            }

            // Delete everything we are able to
            if (!empty($deleteIds)) {
                $entities = $model->deleteEntities($deleteIds);

                $flashes[] = [
                    'type'    => 'notice',
                    'msg'     => 'mautic.notification.notice.batch_deleted',
                    'msgVars' => [
                        '%count%' => count($entities),
                    ],
                ];
            }
        } // else don't do anything

        return $this->postActionRedirect(
            array_merge(
                $postActionVars,
                [
                    'flashes' => $flashes,
                ]
            )
        );
    }

    public function previewAction($objectId): Response
    {
        /** @var NotificationModel $model */
        $model        = $this->getModel('notification');
        $notification = $model->getEntity($objectId);

        return $this->delegateView(
            [
                'viewParameters' => [
                    'notification' => $notification,
                ],
                'contentTemplate' => '@MauticNotification/MobileNotification/preview.html.twig',
            ]
        );
    }

    /**
     * @param int $page
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function contactsAction(
        Request $request,
        PageHelperFactoryInterface $pageHelperFactory,
        $objectId,
        $page = 1,
    ) {
        return $this->generateContactsGrid(
            $request,
            $pageHelperFactory,
            $objectId,
            $page,
            'notification:mobile_notifications:view',
            'mobile_notification',
            'push_notification_stats',
            'mobile_notification',
            'notification_id'
        );
    }
}
