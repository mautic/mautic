<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\NotificationBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\LeadBundle\Controller\EntityContactsTrait;
use Mautic\NotificationBundle\Entity\Notification;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class MobileNotificationController extends FormController
{
    use EntityContactsTrait;

    /**
     * @param int $page
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($page = 1)
    {
        /** @var \Mautic\NotificationBundle\Model\NotificationModel $model */
        $model = $this->getModel('notification');

        //set some permissions
        $permissions = $this->get('mautic.security')->isGranted(
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

        $session = $this->get('session');

        //set limits
        $limit = $session->get('mautic.mobile_notification.limit', $this->coreParametersHelper->getParameter('default_pagelimit'));
        $start = ($page === 1) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search = $this->request->get('search', $session->get('mautic.mobile_notification.filter', ''));
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
            //the number of entities are now less then the current page so redirect to the last page
            if ($count === 1) {
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
                    'contentTemplate' => 'MauticNotificationBundle:MobileNotification:index',
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
                    'tmpl'        => $this->request->get('tmpl', 'index'),
                    'permissions' => $permissions,
                    'model'       => $model,
                    'security'    => $this->get('mautic.security'),
                ],
                'contentTemplate' => 'MauticNotificationBundle:MobileNotification:list.html.php',
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
     * @param $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function viewAction($objectId)
    {
        /** @var \Mautic\NotificationBundle\Model\NotificationModel $model */
        $model    = $this->getModel('notification');
        $security = $this->get('mautic.security');

        /** @var \Mautic\NotificationBundle\Entity\Notification $notification */
        $notification = $model->getEntity($objectId);
        //set the page we came from
        $page = $this->get('session')->get('mautic.mobile_notification.page', 1);

        if ($notification === null) {
            //set the return URL
            $returnUrl = $this->generateUrl('mautic_mobile_notification_index', ['page' => $page]);

            return $this->postActionRedirect(
                [
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => ['page' => $page],
                    'contentTemplate' => 'MauticNotificationBundle:MobileNotification:index',
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
        } elseif (!$this->get('mautic.security')->hasEntityAccess(
            'notification:mobile_notifications:viewown',
            'notification:mobile_notifications:viewother',
            $notification->getCreatedBy()
        )
        ) {
            return $this->accessDenied();
        }

        // Audit Log
        $logs = $this->getModel('core.auditLog')->getLogForObject('notification', $notification->getId(), $notification->getDateAdded());

        // Init the date range filter form
        $dateRangeValues = $this->request->get('daterange', []);
        $action          = $this->generateUrl('mautic_mobile_notification_action', ['objectAction' => 'view', 'objectId' => $objectId]);
        $dateRangeForm   = $this->get('form.factory')->create('daterange', $dateRangeValues, ['action' => $action]);
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
                    'MauticNotificationBundle:MobileNotification:contacts',
                    [
                        'objectId'   => $notification->getId(),
                        'page'       => $this->get('session')->get('mautic.mobile_notification.contact.page', 1),
                        'ignoreAjax' => true,
                    ]
                )->getContent(),
                'dateRangeForm' => $dateRangeForm->createView(),
            ],
            'contentTemplate' => 'MauticNotificationBundle:MobileNotification:details.html.php',
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
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction($entity = null)
    {
        /** @var \Mautic\NotificationBundle\Model\NotificationModel $model */
        $model = $this->getModel('notification');

        if (!$entity instanceof Notification) {
            /** @var \Mautic\NotificationBundle\Entity\Notification $entity */
            $entity = $model->getEntity();
        }

        $method  = $this->request->getMethod();
        $session = $this->get('session');

        if (!$this->get('mautic.security')->isGranted('notification:mobile_notifications:create')) {
            return $this->accessDenied();
        }

        //set the page we came from
        $page   = $session->get('mautic.mobile_notification.page', 1);
        $action = $this->generateUrl('mautic_mobile_notification_action', ['objectAction' => 'new']);

        $updateSelect = ($method == 'POST')
            ? $this->request->request->get('notification[updateSelect]', false, true)
            : $this->request->get('updateSelect', false);

        if ($updateSelect) {
            $entity->setNotificationType('template');
        }

        // create the form
        $form = $model->createForm($entity, $this->get('form.factory'), $action, ['update_select' => $updateSelect]);

        ///Check for a submitted form and process it
        if ($method == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    //form is valid so process the data
                    $model->saveEntity($entity);

                    $this->addFlash(
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

                    if ($form->get('buttons')->get('save')->isClicked()) {
                        $viewParameters = [
                            'objectAction' => 'view',
                            'objectId'     => $entity->getId(),
                        ];
                        $returnUrl = $this->generateUrl('mautic_mobile_notification_action', $viewParameters);
                        $template  = 'MauticNotificationBundle:MobileNotification:view';
                    } else {
                        //return edit view so that all the session stuff is loaded
                        return $this->editAction($entity->getId(), true);
                    }
                }
            } else {
                $viewParameters = ['page' => $page];
                $returnUrl      = $this->generateUrl('mautic_mobile_notification_index', $viewParameters);
                $template       = 'MauticNotificationBundle:MobileNotification:index';
                //clear any modified content
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

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
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

        $integration = $this->get('mautic.helper.integration')->getIntegrationObject('OneSignal');

        return $this->delegateView(
            [
                'viewParameters' => [
                    'form'         => $this->setFormTheme($form, 'MauticNotificationBundle:MobileNotification:form.html.php', 'MauticNotificationBundle:FormTheme\MobileNotification'),
                    'notification' => $entity,
                    'integration'  => $integration,
                ],
                'contentTemplate' => 'MauticNotificationBundle:MobileNotification:form.html.php',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_mobile_notification_index',
                    'mauticContent' => 'mobile_notification',
                    'updateSelect'  => InputHelper::clean($this->request->query->get('updateSelect')),
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
     * @param      $objectId
     * @param bool $ignorePost
     * @param bool $forceTypeSelection
     *
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function editAction($objectId, $ignorePost = false, $forceTypeSelection = false)
    {
        /** @var \Mautic\NotificationBundle\Model\NotificationModel $model */
        $model   = $this->getModel('notification');
        $method  = $this->request->getMethod();
        $entity  = $model->getEntity($objectId);
        $session = $this->get('session');
        $page    = $session->get('mautic.mobile_notification.page', 1);

        //set the return URL
        $returnUrl = $this->generateUrl('mautic_mobile_notification_index', ['page' => $page]);

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'MauticNotificationBundle:MobileNotification:index',
            'passthroughVars' => [
                'activeLink'    => 'mautic_mobile_notification_index',
                'mauticContent' => 'mobile_notification',
            ],
        ];

        //not found
        if ($entity === null) {
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
        } elseif (!$this->get('mautic.security')->hasEntityAccess(
            'notification:mobile_notifications:viewown',
            'notification:mobile_notifications:viewother',
            $entity->getCreatedBy()
        )
        ) {
            return $this->accessDenied();
        } elseif ($model->isLocked($entity)) {
            //deny access if the entity is locked
            return $this->isLocked($postActionVars, $entity, 'notification');
        }

        //Create the form
        $action = $this->generateUrl('mautic_mobile_notification_action', ['objectAction' => 'edit', 'objectId' => $objectId]);

        $updateSelect = ($method == 'POST')
            ? $this->request->request->get('notification[updateSelect]', false, true)
            : $this->request->get('updateSelect', false);

        $form = $model->createForm($entity, $this->get('form.factory'), $action, ['update_select' => $updateSelect]);

        ///Check for a submitted form and process it
        if (!$ignorePost && $method == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    //form is valid so process the data
                    $model->saveEntity($entity, $form->get('buttons')->get('save')->isClicked());

                    $this->addFlash(
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
                //clear any modified content
                $session->remove('mautic.mobile_notification.'.$objectId.'.content');
                //unlock the entity
                $model->unlockEntity($entity);
            }

            $template    = 'MauticNotificationBundle:MobileNotification:view';
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

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
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
            //lock the entity
            $model->lockEntity($entity);
        }

        return $this->delegateView(
            [
                'viewParameters' => [
                    'form'               => $this->setFormTheme($form, 'MauticNotificationBundle:MobileNotification:form.html.php', 'MauticNotificationBundle:FormTheme\MobileNotification'),
                    'notification'       => $entity,
                    'forceTypeSelection' => $forceTypeSelection,
                ],
                'contentTemplate' => 'MauticNotificationBundle:MobileNotification:form.html.php',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_mobile_notification_index',
                    'mauticContent' => 'mobile_notification',
                    'updateSelect'  => InputHelper::clean($this->request->query->get('updateSelect')),
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
     * @param $objectId
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function cloneAction($objectId)
    {
        $model  = $this->getModel('notification');
        $entity = $model->getEntity($objectId);

        if ($entity != null) {
            if (!$this->get('mautic.security')->isGranted('notification:mobile_notifications:create')
                || !$this->get('mautic.security')->hasEntityAccess(
                    'notification:mobile_notifications:viewown',
                    'notification:mobile_notifications:viewother',
                    $entity->getCreatedBy()
                )
            ) {
                return $this->accessDenied();
            }

            $entity      = clone $entity;
            $session     = $this->get('session');
            $contentName = 'mautic.mobile_notification.'.$entity->getId().'.content';

            $session->set($contentName, $entity->getContent());
        }

        return $this->newAction($entity);
    }

    /**
     * Deletes the entity.
     *
     * @param   $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction($objectId)
    {
        $page      = $this->get('session')->get('mautic.mobile_notification.page', 1);
        $returnUrl = $this->generateUrl('mautic_mobile_notification_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'MauticNotificationBundle:MobileNotification:index',
            'passthroughVars' => [
                'activeLink'    => 'mautic_mobile_notification_index',
                'mauticContent' => 'mobile_notification',
            ],
        ];

        if ($this->request->getMethod() == 'POST') {
            $model  = $this->getModel('notification');
            $entity = $model->getEntity($objectId);

            if ($entity === null) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.notification.error.notfound',
                    'msgVars' => ['%id%' => $objectId],
                ];
            } elseif (!$this->get('mautic.security')->hasEntityAccess(
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
        } //else don't do anything

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
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchDeleteAction()
    {
        $page      = $this->get('session')->get('mautic.mobile_notification.page', 1);
        $returnUrl = $this->generateUrl('mautic_mobile_notification_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'MauticNotificationBundle:MobileNotification:index',
            'passthroughVars' => [
                'activeLink'    => '#mautic_mobile_notification_index',
                'mauticContent' => 'mobile_notification',
            ],
        ];

        if ($this->request->getMethod() == 'POST') {
            $model = $this->getModel('notification');
            $ids   = json_decode($this->request->query->get('ids', '{}'));

            $deleteIds = [];

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $model->getEntity($objectId);

                if ($entity === null) {
                    $flashes[] = [
                        'type'    => 'error',
                        'msg'     => 'mautic.notification.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ];
                } elseif (!$this->get('mautic.security')->hasEntityAccess(
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
        } //else don't do anything

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
     * @param $objectId
     *
     * @return JsonResponse|Response
     */
    public function previewAction($objectId)
    {
        /** @var \Mautic\NotificationBundle\Model\NotificationModel $model */
        $model        = $this->getModel('notification');
        $notification = $model->getEntity($objectId);

        if ($notification != null
            && $this->get('mautic.security')->hasEntityAccess(
                'notification:mobile_notifications:editown',
                'notification:mobile_notifications:editother'
            )
        ) {
        }

        return $this->delegateView(
            [
                'viewParameters' => [
                    'notification' => $notification,
                ],
                'contentTemplate' => 'MauticNotificationBundle:MobileNotification:preview.html.php',
            ]
        );
    }

    /**
     * @param     $objectId
     * @param int $page
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function contactsAction($objectId, $page = 1)
    {
        return $this->generateContactsGrid(
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
