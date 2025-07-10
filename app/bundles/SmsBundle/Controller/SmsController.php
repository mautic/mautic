<?php

namespace Mautic\SmsBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Factory\PageHelperFactoryInterface;
use Mautic\CoreBundle\Form\Type\DateRangeType;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\LeadBundle\Controller\EntityContactsTrait;
use Mautic\SmsBundle\Entity\Sms;
use Mautic\SmsBundle\Model\SmsModel;
use Mautic\SmsBundle\Sms\TransportChain;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SmsController extends FormController
{
    use EntityContactsTrait;

    /**
     * @param int $page
     *
     * @return JsonResponse|Response
     */
    public function indexAction(Request $request, TransportChain $transportChain, $page = 1)
    {
        /** @var SmsModel $model */
        $model = $this->getModel('sms');

        // set some permissions
        $permissions = $this->security->isGranted(
            [
                'sms:smses:viewown',
                'sms:smses:viewother',
                'sms:smses:create',
                'sms:smses:editown',
                'sms:smses:editother',
                'sms:smses:deleteown',
                'sms:smses:deleteother',
                'sms:smses:publishown',
                'sms:smses:publishother',
            ],
            'RETURN_ARRAY'
        );

        if (!$permissions['sms:smses:viewown'] && !$permissions['sms:smses:viewother']) {
            return $this->accessDenied();
        }

        $this->setListFilters();

        $session = $request->getSession();

        // set limits
        $limit = $session->get('mautic.sms.limit', $this->coreParametersHelper->get('default_pagelimit'));
        $start = (1 === $page) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search = $request->get('search', $session->get('mautic.sms.filter', ''));
        $session->set('mautic.sms.filter', $search);

        $filter = ['string' => $search];

        if (!$permissions['sms:smses:viewother']) {
            $filter['force'][] =
                [
                    'column' => 'e.createdBy',
                    'expr'   => 'eq',
                    'value'  => $this->user->getId(),
                ];
        }

        $orderBy    = $session->get('mautic.sms.orderby', 'e.name');
        $orderByDir = $session->get('mautic.sms.orderbydir', $this->getDefaultOrderDirection());

        $smss = $model->getEntities([
            'start'      => $start,
            'limit'      => $limit,
            'filter'     => $filter,
            'orderBy'    => $orderBy,
            'orderByDir' => $orderByDir,
        ]);

        $count = count($smss);
        if ($count && $count < ($start + 1)) {
            // the number of entities are now less then the current page so redirect to the last page
            if (1 === $count) {
                $lastPage = 1;
            } else {
                $lastPage = (floor($count / $limit)) ?: 1;
            }

            $session->set('mautic.sms.page', $lastPage);
            $returnUrl = $this->generateUrl('mautic_sms_index', ['page' => $lastPage]);

            return $this->postActionRedirect([
                'returnUrl'       => $returnUrl,
                'viewParameters'  => ['page' => $lastPage],
                'contentTemplate' => 'Mautic\SmsBundle\Controller\SmsController::indexAction',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_sms_index',
                    'mauticContent' => 'sms',
                ],
            ]);
        }
        $session->set('mautic.sms.page', $page);

        return $this->delegateView([
            'viewParameters' => [
                'searchValue' => $search,
                'items'       => $smss,
                'totalItems'  => $count,
                'page'        => $page,
                'limit'       => $limit,
                'tmpl'        => $request->get('tmpl', 'index'),
                'permissions' => $permissions,
                'model'       => $model,
                'security'    => $this->security,
                'configured'  => count($transportChain->getEnabledTransports()) > 0,
            ],
            'contentTemplate' => '@MauticSms/Sms/list.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_sms_index',
                'mauticContent' => 'sms',
                'route'         => $this->generateUrl('mautic_sms_index', ['page' => $page]),
            ],
        ]);
    }

    /**
     * Loads a specific form into the detailed panel.
     *
     * @return JsonResponse|Response
     */
    public function viewAction(Request $request, $objectId)
    {
        /** @var SmsModel $model */
        $model    = $this->getModel('sms');
        $security = $this->security;

        /** @var Sms $sms */
        $sms = $model->getEntity($objectId);
        // set the page we came from
        $page = $request->getSession()->get('mautic.sms.page', 1);

        if (null === $sms) {
            // set the return URL
            $returnUrl = $this->generateUrl('mautic_sms_index', ['page' => $page]);

            return $this->postActionRedirect([
                'returnUrl'       => $returnUrl,
                'viewParameters'  => ['page' => $page],
                'contentTemplate' => 'Mautic\SmsBundle\Controller\SmsController::indexAction',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_sms_index',
                    'mauticContent' => 'sms',
                ],
                'flashes' => [
                    [
                        'type'    => 'error',
                        'msg'     => 'mautic.sms.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ],
                ],
            ]);
        } elseif (!$this->security->hasEntityAccess(
            'sms:smses:viewown',
            'sms:smses:viewother',
            $sms->getCreatedBy()
        )
        ) {
            return $this->accessDenied();
        }

        // Audit Log
        $auditLogModel = $this->getModel('core.auditlog');
        \assert($auditLogModel instanceof AuditLogModel);
        $logs = $auditLogModel->getLogForObject('sms', $sms->getId(), $sms->getDateAdded());

        // Init the date range filter form
        $dateRangeValues = $request->query->all()['daterange'] ?? $request->request->all()['daterange'] ?? [];
        $action          = $this->generateUrl('mautic_sms_action', ['objectAction' => 'view', 'objectId' => $objectId]);
        $dateRangeForm   = $this->formFactory->create(DateRangeType::class, $dateRangeValues, ['action' => $action]);
        $entityViews     = $model->getHitsLineChartData(
            null,
            new \DateTime($dateRangeForm->get('date_from')->getData()),
            new \DateTime($dateRangeForm->get('date_to')->getData()),
            null,
            ['sms_id' => $sms->getId()]
        );

        // Get click through stats
        $trackableLinks = $model->getSmsClickStats($sms->getId());

        return $this->delegateView([
            'returnUrl'      => $this->generateUrl('mautic_sms_action', ['objectAction' => 'view', 'objectId' => $sms->getId()]),
            'viewParameters' => [
                'sms'         => $sms,
                'trackables'  => $trackableLinks,
                'logs'        => $logs,
                'isEmbedded'  => $request->get('isEmbedded') ?: false,
                'permissions' => $security->isGranted([
                    'sms:smses:viewown',
                    'sms:smses:viewother',
                    'sms:smses:create',
                    'sms:smses:editown',
                    'sms:smses:editother',
                    'sms:smses:deleteown',
                    'sms:smses:deleteother',
                    'sms:smses:publishown',
                    'sms:smses:publishother',
                ], 'RETURN_ARRAY'),
                'security'    => $security,
                'entityViews' => $entityViews,
                'contacts'    => $this->forward(
                    'Mautic\SmsBundle\Controller\SmsController::contactsAction',
                    [
                        'objectId'   => $sms->getId(),
                        'page'       => $request->getSession()->get('mautic.sms.contact.page', 1),
                        'ignoreAjax' => true,
                    ]
                )->getContent(),
                'dateRangeForm' => $dateRangeForm->createView(),
            ],
            'contentTemplate' => '@MauticSms/Sms/details.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_sms_index',
                'mauticContent' => 'sms',
            ],
        ]);
    }

    /**
     * Generates new form and processes post data.
     *
     * @param Sms $entity
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function newAction(Request $request, $entity = null)
    {
        /** @var SmsModel $model */
        $model = $this->getModel('sms');

        if (!$entity instanceof Sms) {
            /** @var Sms $entity */
            $entity = $model->getEntity();
        }

        $method  = $request->getMethod();
        $session = $request->getSession();

        if (!$this->security->isGranted('sms:smses:create')) {
            return $this->accessDenied();
        }

        // set the page we came from
        $page         = $session->get('mautic.sms.page', 1);
        $action       = $this->generateUrl('mautic_sms_action', ['objectAction' => 'new']);
        $sms          = $request->request->all()['sms'] ?? [];
        $updateSelect = 'POST' === $method
            ? ($sms['updateSelect'] ?? false)
            : $request->get('updateSelect', false);

        if ($updateSelect) {
            $entity->setSmsType('template');
        }

        // create the form
        $form = $model->createForm($entity, $this->formFactory, $action, ['update_select' => $updateSelect]);

        // /Check for a submitted form and process it
        if ('POST' == $method) {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    // form is valid so process the data
                    $model->saveEntity($entity);

                    $this->addFlashMessage(
                        'mautic.core.notice.created',
                        [
                            '%name%'      => $entity->getName(),
                            '%menu_link%' => 'mautic_sms_index',
                            '%url%'       => $this->generateUrl(
                                'mautic_sms_action',
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
                        $returnUrl = $this->generateUrl('mautic_sms_action', $viewParameters);
                        $template  = 'Mautic\SmsBundle\Controller\SmsController::viewAction';
                    } else {
                        // return edit view so that all the session stuff is loaded
                        return $this->editAction($request, $entity->getId(), true);
                    }
                }
            } else {
                $viewParameters = ['page' => $page];
                $returnUrl      = $this->generateUrl('mautic_sms_index', $viewParameters);
                $template       = 'Mautic\SmsBundle\Controller\SmsController::indexAction';
                // clear any modified content
                $session->remove('mautic.sms.'.$entity->getId().'.content');
            }

            $passthrough = [
                'activeLink'    => 'mautic_sms_index',
                'mauticContent' => 'sms',
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

        return $this->delegateView(
            [
                'viewParameters' => [
                    'form' => $form->createView(),
                    'sms'  => $entity,
                ],
                'contentTemplate' => '@MauticSms/Sms/form.html.twig',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_sms_index',
                    'mauticContent' => 'sms',
                    'updateSelect'  => InputHelper::clean($request->query->get('updateSelect')),
                    'route'         => $this->generateUrl(
                        'mautic_sms_action',
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
    public function editAction(Request $request, $objectId, $ignorePost = false, $forceTypeSelection = false)
    {
        /** @var SmsModel $model */
        $model   = $this->getModel('sms');
        $method  = $request->getMethod();
        $entity  = $model->getEntity($objectId);
        $session = $request->getSession();
        $page    = $session->get('mautic.sms.page', 1);

        // set the return URL
        $returnUrl = $this->generateUrl('mautic_sms_index', ['page' => $page]);

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\SmsBundle\Controller\SmsController::indexAction',
            'passthroughVars' => [
                'activeLink'    => 'mautic_sms_index',
                'mauticContent' => 'sms',
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
                                'msg'     => 'mautic.sms.error.notfound',
                                'msgVars' => ['%id%' => $objectId],
                            ],
                        ],
                    ]
                )
            );
        } elseif (!$this->security->hasEntityAccess(
            'sms:smses:viewown',
            'sms:smses:viewother',
            $entity->getCreatedBy()
        )
        ) {
            return $this->accessDenied();
        } elseif ($model->isLocked($entity)) {
            // deny access if the entity is locked
            return $this->isLocked($postActionVars, $entity, 'sms');
        }

        // Create the form
        $action       = $this->generateUrl('mautic_sms_action', ['objectAction' => 'edit', 'objectId' => $objectId]);
        $sms          = $request->request->all()['sms'] ?? [];
        $updateSelect = 'POST' === $method
            ? ($sms['updateSelect'] ?? false)
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
                            '%menu_link%' => 'mautic_sms_index',
                            '%url%'       => $this->generateUrl(
                                'mautic_sms_action',
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
                $session->remove('mautic.sms.'.$objectId.'.content');
                // unlock the entity
                $model->unlockEntity($entity);
            }

            $passthrough = [
                'activeLink'    => 'mautic_sms_index',
                'mauticContent' => 'sms',
            ];

            $template = 'Mautic\SmsBundle\Controller\SmsController::viewAction';

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
                            'returnUrl'       => $this->generateUrl('mautic_sms_action', $viewParameters),
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

        return $this->delegateView(
            [
                'viewParameters' => [
                    'form'               => $form->createView(),
                    'sms'                => $entity,
                    'forceTypeSelection' => $forceTypeSelection,
                ],
                'contentTemplate' => '@MauticSms/Sms/form.html.twig',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_sms_index',
                    'mauticContent' => 'sms',
                    'updateSelect'  => InputHelper::clean($request->query->get('updateSelect')),
                    'route'         => $this->generateUrl(
                        'mautic_sms_action',
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
    public function cloneAction(Request $request, $objectId)
    {
        $model  = $this->getModel('sms');
        $entity = $model->getEntity($objectId);

        if (null != $entity) {
            if (!$this->security->isGranted('sms:smses:create')
                || !$this->security->hasEntityAccess(
                    'sms:smses:viewown',
                    'sms:smses:viewother',
                    $entity->getCreatedBy()
                )
            ) {
                return $this->accessDenied();
            }

            $entity = clone $entity;
        }

        return $this->newAction($request, $entity);
    }

    /**
     * Deletes the entity.
     *
     * @return Response
     */
    public function deleteAction(Request $request, $objectId)
    {
        $page      = $request->getSession()->get('mautic.sms.page', 1);
        $returnUrl = $this->generateUrl('mautic_sms_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\SmsBundle\Controller\SmsController::indexAction',
            'passthroughVars' => [
                'activeLink'    => 'mautic_sms_index',
                'mauticContent' => 'sms',
            ],
        ];

        if (Request::METHOD_POST === $request->getMethod()) {
            $model = $this->getModel('sms');
            \assert($model instanceof SmsModel);
            $entity = $model->getEntity($objectId);

            if (null === $entity) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.sms.error.notfound',
                    'msgVars' => ['%id%' => $objectId],
                ];
            } elseif (!$this->security->hasEntityAccess(
                'sms:smses:deleteown',
                'sms:smses:deleteother',
                $entity->getCreatedBy()
            )
            ) {
                return $this->accessDenied();
            } elseif ($model->isLocked($entity)) {
                return $this->isLocked($postActionVars, $entity, 'sms');
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
                ['flashes' => $flashes]
            )
        );
    }

    /**
     * Deletes a group of entities.
     */
    public function batchDeleteAction(Request $request): Response
    {
        $page      = $request->getSession()->get('mautic.sms.page', 1);
        $returnUrl = $this->generateUrl('mautic_sms_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\SmsBundle\Controller\SmsController::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_sms_index',
                'mauticContent' => 'sms',
            ],
        ];

        if (Request::METHOD_POST == $request->getMethod()) {
            $model = $this->getModel('sms');
            \assert($model instanceof SmsModel);
            $ids = json_decode($request->query->get('ids', '{}'));

            $deleteIds = [];

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $model->getEntity($objectId);

                if (null === $entity) {
                    $flashes[] = [
                        'type'    => 'error',
                        'msg'     => 'mautic.sms.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ];
                } elseif (!$this->security->hasEntityAccess(
                    'sms:smses:viewown',
                    'sms:smses:viewother',
                    $entity->getCreatedBy()
                )
                ) {
                    $flashes[] = $this->accessDenied(true);
                } elseif ($model->isLocked($entity)) {
                    $flashes[] = $this->isLocked($postActionVars, $entity, 'sms', true);
                } else {
                    $deleteIds[] = $objectId;
                }
            }

            // Delete everything we are able to
            if (!empty($deleteIds)) {
                $entities = $model->deleteEntities($deleteIds);

                $flashes[] = [
                    'type'    => 'notice',
                    'msg'     => 'mautic.sms.notice.batch_deleted',
                    'msgVars' => [
                        '%count%' => count($entities),
                    ],
                ];
            }
        } // else don't do anything

        return $this->postActionRedirect(
            array_merge(
                $postActionVars,
                ['flashes' => $flashes]
            )
        );
    }

    /**
     * @return JsonResponse|Response
     */
    public function previewAction($objectId)
    {
        /** @var SmsModel $model */
        $model    = $this->getModel('sms');
        $sms      = $model->getEntity($objectId);
        $security = $this->security;

        if (null !== $sms && $security->hasEntityAccess('sms:smses:viewown', 'sms:smses:viewother')) {
            return $this->delegateView([
                'viewParameters' => [
                    'sms' => $sms,
                ],
                'contentTemplate' => '@MauticSms/Sms/preview.html.twig',
            ]);
        }

        return new Response('', Response::HTTP_NOT_FOUND);
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
            'sms:smses:view',
            'sms',
            'sms_message_stats',
            'sms',
            'sms_id'
        );
    }

    protected function getModelName(): string
    {
        return 'sms';
    }

    protected function getDefaultOrderDirection(): string
    {
        return 'DESC';
    }
}
