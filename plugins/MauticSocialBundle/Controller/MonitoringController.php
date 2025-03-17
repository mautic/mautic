<?php

namespace MauticPlugin\MauticSocialBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Factory\PageHelperFactoryInterface;
use Mautic\CoreBundle\Form\Type\DateRangeType;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\LeadBundle\Controller\EntityContactsTrait;
use MauticPlugin\MauticSocialBundle\Entity\Monitoring;
use MauticPlugin\MauticSocialBundle\Model\MonitoringModel;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MonitoringController extends FormController
{
    use EntityContactsTrait;

    /*
     * @param int $page
     */
    public function indexAction(Request $request, MonitoringModel $model, $page = 1)
    {
        if (!$this->security->isGranted('mauticSocial:monitoring:view')) {
            return $this->accessDenied();
        }

        $session = $request->getSession();

        $this->setListFilters();

        // set limits
        $limit = $session->get('mautic.social.monitoring.limit', $this->getParameter('mautic.default_pagelimit'));
        $start = (1 === $page) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search = $request->get('search', $session->get('mautic.social.monitoring.filter', ''));
        $session->set('mautic.social.monitoring.filter', $search);

        $filter = ['string' => $search, 'force' => []];

        $orderBy    = $session->get('mautic.social.monitoring.orderby', 'e.title');
        $orderByDir = $session->get('mautic.social.monitoring.orderbydir', 'DESC');

        $monitoringList = $model->getEntities(
            [
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir,
            ]
        );

        $count = count($monitoringList);
        if ($count && $count < ($start + 1)) {
            // the number of entities are now less then the current asset so redirect to the last asset
            if (1 === $count) {
                $lastPage = 1;
            } else {
                $lastPage = (floor($limit / $count)) ?: 1;
            }
            $session->set('mautic.social.monitoring.page', $lastPage);
            $returnUrl = $this->generateUrl('mautic_social_index', ['page' => $lastPage]);

            return $this->postActionRedirect(
                [
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => ['page' => $lastPage],
                    'contentTemplate' => 'MauticPlugin\MauticSocialBundle\Controller\MonitoringController::indexAction',
                    'passthroughVars' => [
                        'activeLink'    => '#mautic_social_index',
                        'mauticContent' => 'monitoring',
                    ],
                ]
            );
        }

        // set what asset currently on so that we can return here after form submission/cancellation
        $session->set('mautic.social.monitoring.page', $page);

        $tmpl = $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index';

        return $this->delegateView(
            [
                'viewParameters' => [
                    'searchValue' => $search,
                    'items'       => $monitoringList,
                    'limit'       => $limit,
                    'model'       => $model,
                    'tmpl'        => $tmpl,
                    'page'        => $page,
                ],
                'contentTemplate' => '@MauticSocial/Monitoring/list.html.twig',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_social_index',
                    'mauticContent' => 'monitoring',
                    'route'         => $this->generateUrl('mautic_social_index', ['page' => $page]),
                ],
            ]
        );
    }

    /**
     * Generates new form and processes post data.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function newAction(Request $request, MonitoringModel $model, IpLookupHelper $ipLookupHelper)
    {
        if (!$this->security->isGranted('mauticSocial:monitoring:create')) {
            return $this->accessDenied();
        }

        $action = $this->generateUrl('mautic_social_action', ['objectAction' => 'new']);

        $entity  = $model->getEntity();
        $method  = $request->getMethod();
        $session = $request->getSession();

        // get the list of types from the model
        $networkTypes = $model->getNetworkTypes();

        // get the network type from the request on submit. helpful for validation error
        // rebuilds structure of the form when it gets updated on submit
        $monitoring  = $request->request->all()['monitoring'] ?? [];
        $networkType = 'POST' === $method ? ($monitoring['networkType'] ?? '') : '';

        // build the form
        $form = $model->createForm(
            $entity,
            $this->formFactory,
            $action,
            [
                // pass through the types and the selected default type
                'networkTypes' => $networkTypes,
                'networkType'  => $networkType,
            ]
        );

        // Set the page we came from
        $page = $session->get('mautic.social.monitoring.page', 1);
        // /Check for a submitted form and process it
        if ('POST' === $method) {
            $viewParameters = ['page' => $page];
            $template       = 'MauticPlugin\MauticSocialBundle\Controller\MonitoringController::indexAction';
            $valid          = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    // form is valid so process the data
                    $model->saveEntity($entity);

                    // update the audit log
                    $this->updateAuditLog($entity, $ipLookupHelper, 'create');

                    $this->addFlashMessage(
                        'mautic.core.notice.created',
                        [
                            '%name%'      => $entity->getTitle(),
                            '%menu_link%' => 'mautic_social_index',
                            '%url%'       => $this->generateUrl(
                                'mautic_social_action',
                                [
                                    'objectAction' => 'edit',
                                    'objectId'     => $entity->getId(),
                                ]
                            ),
                        ]
                    );

                    if (!$this->getFormButton($form, ['buttons', 'save'])->isClicked()) {
                        // return edit view so that all the session stuff is loaded
                        return $this->editAction($request, $ipLookupHelper, $entity->getId(), true);
                    }

                    $viewParameters = [
                        'objectAction' => 'view',
                        'objectId'     => $entity->getId(),
                    ];
                    $template = 'MauticPlugin\MauticSocialBundle\Controller\MonitoringController::viewAction';
                }
            }
            $returnUrl = $this->generateUrl('mautic_social_index', $viewParameters);

            /** @var SubmitButton $saveSubmitButton */
            $saveSubmitButton = $form->get('buttons')->get('save');

            if ($cancelled || ($valid && $saveSubmitButton->isClicked())) {
                return $this->postActionRedirect(
                    [
                        'returnUrl'       => $returnUrl,
                        'viewParameters'  => $viewParameters,
                        'contentTemplate' => $template,
                        'passthroughVars' => [
                            'activeLink'    => 'mautic_social_index',
                            'mauticContent' => 'monitoring',
                        ],
                    ]
                );
            }
        }

        return $this->delegateView(
            [
                'viewParameters' => [
                    'tmpl'   => $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index',
                    'entity' => $entity,
                    'form'   => $form->createView(),
                ],
                'contentTemplate' => '@MauticSocial/Monitoring/form.html.twig',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_social_index',
                    'mauticContent' => 'monitoring',
                    'route'         => $this->generateUrl(
                        'mautic_social_action',
                        [
                            'objectAction' => 'new',
                            'objectId'     => $entity->getId(),
                        ]
                    ),
                ],
            ]
        );
    }

    /**
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function editAction(Request $request, IpLookupHelper $ipLookupHelper, $objectId, bool $ignorePost = false)
    {
        if (!$this->security->isGranted('mauticSocial:monitoring:edit')) {
            return $this->accessDenied();
        }

        $action = $this->generateUrl('mautic_social_action', ['objectAction' => 'edit', 'objectId' => $objectId]);

        /** @var MonitoringModel $model */
        $model = $this->getModel('social.monitoring');

        $entity  = $model->getEntity($objectId);
        $session = $request->getSession();

        // Set the page we came from
        $page = $session->get('mautic.social.monitoring.page', 1);

        // set the return URL
        $returnUrl = $this->generateUrl('mautic_social_index', ['page' => $page]);

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'MauticSocial:Monitoring:index',
            'passthroughVars' => [
                'activeLink'    => 'mautic_social_index',
                'mauticContent' => 'monitoring',
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
                                'msg'     => 'mautic.social.monitoring.error.notfound',
                                'msgVars' => ['%id%' => $objectId],
                            ],
                        ],
                    ]
                )
            );
        }

        // get the list of types from the model
        $networkTypes = $model->getNetworkTypes();

        // get the network type from the request on submit. helpful for validation error
        // rebuilds structure of the form when it gets updated on submit
        $method      = $request->getMethod();
        $monitoring  = $request->request->all()['monitoring'] ?? [];
        $networkType = 'POST' === $method ? ($monitoring['networkType'] ?? '') : $entity->getNetworkType();

        // build the form
        $form = $model->createForm(
            $entity,
            $this->formFactory,
            $action,
            [
                // pass through the types and the selected default type
                'networkTypes' => $networkTypes,
                'networkType'  => $networkType,
            ]
        );

        // /Check for a submitted form and process it
        if (!$ignorePost && 'POST' === $method) {
            $valid = false;

            /** @var SubmitButton $saveSubmitButton */
            $saveSubmitButton = $form->get('buttons')->get('save');

            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    // form is valid so process the data
                    $model->saveEntity($entity, $saveSubmitButton->isClicked());

                    // update the audit log
                    $this->updateAuditLog($entity, $ipLookupHelper, 'update');

                    $this->addFlashMessage(
                        'mautic.core.notice.updated',
                        [
                            '%name%'      => $entity->getTitle(),
                            '%menu_link%' => 'mautic_email_index',
                            '%url%'       => $this->generateUrl(
                                'mautic_social_action',
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
                $model->unlockEntity($entity);
            }

            if ($cancelled || ($valid && $saveSubmitButton->isClicked())) {
                $viewParameters = [
                    'objectAction' => 'view',
                    'objectId'     => $entity->getId(),
                ];

                return $this->postActionRedirect(
                    array_merge(
                        $postActionVars,
                        [
                            'returnUrl'       => $this->generateUrl('mautic_social_action', $viewParameters),
                            'viewParameters'  => $viewParameters,
                            'contentTemplate' => 'MauticPlugin\MauticSocialBundle\Controller\MonitoringController::viewAction',
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
                    'tmpl'   => $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index',
                    'entity' => $entity,
                    'form'   => $form->createView(),
                ],
                'contentTemplate' => '@MauticSocial/Monitoring/form.html.twig',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_social_index',
                    'mauticContent' => 'monitoring',
                    'route'         => $this->generateUrl(
                        'mautic_social_action',
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
     * Loads a specific form into the detailed panel.
     *
     * @param int $objectId
     *
     * @return JsonResponse|Response
     */
    public function viewAction(Request $request, $objectId)
    {
        if (!$this->security->isGranted('mauticSocial:monitoring:view')) {
            return $this->accessDenied();
        }

        $session = $request->getSession();

        /** @var MonitoringModel $model */
        $model = $this->getModel('social.monitoring');

        /** @var \MauticPlugin\MauticSocialBundle\Entity\PostCountRepository $postCountRepo */
        $postCountRepo = $this->getModel('social.postcount')->getRepository();

        $security         = $this->security;
        $monitoringEntity = $model->getEntity($objectId);

        // set the asset we came from
        $page = $session->get('mautic.social.monitoring.page', 1);

        $tmpl = $request->isXmlHttpRequest() ? $request->get('tmpl', 'details') : 'details';

        if (null === $monitoringEntity) {
            // set the return URL
            $returnUrl = $this->generateUrl('mautic_social_index', ['page' => $page]);

            return $this->postActionRedirect(
                [
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => ['page' => $page],
                    'contentTemplate' => 'MauticPlugin\MauticSocialBundle\Controller\MonitoringController::indexAction',
                    'passthroughVars' => [
                        'activeLink'    => '#mautic_social_index',
                        'mauticContent' => 'monitoring',
                    ],
                    'flashes' => [
                        [
                            'type'    => 'error',
                            'msg'     => 'mautic.social.monitoring.error.notfound',
                            'msgVars' => ['%id%' => $objectId],
                        ],
                    ],
                ]
            );
        }

        // Audit Log
        $auditLogModel = $this->getModel('core.auditlog');
        \assert($auditLogModel instanceof AuditLogModel);
        $logs = $auditLogModel->getLogForObject('monitoring', $objectId);

        $returnUrl = $this->generateUrl(
            'mautic_social_action',
            [
                'objectAction' => 'view',
                'objectId'     => $monitoringEntity->getId(),
            ]
        );

        // Init the date range filter form
        $dateRangeValues = $request->get('daterange', []);
        $dateRangeForm   = $this->formFactory->create(DateRangeType::class, $dateRangeValues, ['action' => $returnUrl]);
        $dateFrom        = new \DateTime($dateRangeForm['date_from']->getData());
        $dateTo          = new \DateTime($dateRangeForm['date_to']->getData());

        $chart     = new LineChart(null, $dateFrom, $dateTo);
        $leadStats = $postCountRepo->getLeadStatsPost(
            $dateFrom,
            $dateTo,
            ['monitor_id' => $monitoringEntity->getId()]
        );
        $chart->setDataset($this->translator->trans('mautic.social.twitter.tweet.count'), $leadStats);

        return $this->delegateView(
            [
                'returnUrl'      => $returnUrl,
                'viewParameters' => [
                    'activeMonitoring' => $monitoringEntity,
                    'logs'             => $logs,
                    'isEmbedded'       => $request->get('isEmbedded') ?: false,
                    'tmpl'             => $tmpl,
                    'security'         => $security,
                    'leadStats'        => $chart->render(),
                    'monitorLeads'     => $this->forward(
                        'MauticPlugin\MauticSocialBundle\Controller\MonitoringController::contactsAction',
                        [
                            'objectId'   => $monitoringEntity->getId(),
                            'page'       => $page,
                            'ignoreAjax' => true,
                        ]
                    )->getContent(),
                    'dateRangeForm' => $dateRangeForm->createView(),
                ],
                'contentTemplate' => '@MauticSocial/Monitoring/'.$tmpl.'.html.twig',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_social_index',
                    'mauticContent' => 'monitoring',
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
    public function deleteAction(Request $request, IpLookupHelper $ipLookupHelper, $objectId)
    {
        if (!$this->security->isGranted('mauticSocial:monitoring:delete')) {
            return $this->accessDenied();
        }

        $session   = $request->getSession();
        $page      = $session->get('mautic.social.monitoring.page', 1);
        $returnUrl = $this->generateUrl('mautic_social_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'MauticPlugin\MauticSocialBundle\Controller\MonitoringController::indexAction',
            'passthroughVars' => [
                'activeLink'    => 'mautic_social_index',
                'mauticContent' => 'monitoring',
            ],
        ];

        if ('POST' === $request->getMethod()) {
            /** @var MonitoringModel $model */
            $model  = $this->getModel('social.monitoring');
            $entity = $model->getEntity($objectId);

            if (null === $entity) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.social.monitoring.error.notfound',
                    'msgVars' => ['%id%' => $objectId],
                ];
            } elseif ($model->isLocked($entity)) {
                return $this->isLocked($postActionVars, $entity, 'plugin.mauticSocial.monitoring');
            }

            // update the audit log
            $this->updateAuditLog($entity, $ipLookupHelper, 'delete');

            // then delete the record
            $model->deleteEntity($entity);

            $flashes[] = [
                'type'    => 'notice',
                'msg'     => 'mautic.core.notice.deleted',
                'msgVars' => [
                    '%name%' => $entity->getTitle(),
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
     *
     * @return Response
     */
    public function batchDeleteAction(Request $request)
    {
        if (!$this->security->isGranted('mauticSocial:monitoring:delete')) {
            return $this->accessDenied();
        }

        $session   = $request->getSession();
        $page      = $session->get('mautic.social.monitoring.page', 1);
        $returnUrl = $this->generateUrl('mautic_social_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'MauticPlugin\MauticSocialBundle\Controller\MonitoringController::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_social_index',
                'mauticContent' => 'monitoring',
            ],
        ];

        if ('POST' === $request->getMethod()) {
            /** @var MonitoringModel $model */
            $model = $this->getModel('social.monitoring');

            $ids       = json_decode($request->query->get('ids', ''));
            $deleteIds = [];

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $model->getEntity($objectId);

                if (null === $entity) {
                    $flashes[] = [
                        'type'    => 'error',
                        'msg'     => 'mautic.social.monitoring.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ];
                } elseif ($model->isLocked($entity)) {
                    $flashes[] = $this->isLocked($postActionVars, $entity, 'monitoring', true);
                } else {
                    $deleteIds[] = $objectId;
                }
            }

            // Delete everything we are able to
            if (!empty($deleteIds)) {
                $entities = $model->deleteEntities($deleteIds);

                $flashes[] = [
                    'type'    => 'notice',
                    'msg'     => 'mautic.social.monitoring.notice.batch_deleted',
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
            'mauticSocial:monitoring:view',
            'social',
            'monitoring_leads',
            null, // @todo - implement when individual social channels are supported by the plugin
            'monitor_id'
        );
    }

    /*
     * Update the audit log
     */
    public function updateAuditLog(Monitoring $monitoring, IpLookupHelper $ipLookupHelper, $action): void
    {
        $log = [
            'bundle'    => 'plugin.mauticSocial',
            'object'    => 'monitoring',
            'objectId'  => $monitoring->getId(),
            'action'    => $action,
            'details'   => ['name' => $monitoring->getTitle()],
            'ipAddress' => $ipLookupHelper->getIpAddressFromRequest(),
        ];

        $auditLog = $this->getModel('core.auditlog');
        \assert($auditLog instanceof AuditLogModel);
        $auditLog->writeToLog($log);
    }
}
