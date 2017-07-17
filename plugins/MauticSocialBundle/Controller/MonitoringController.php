<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\LeadBundle\Controller\EntityContactsTrait;
use MauticPlugin\MauticSocialBundle\Entity\Monitoring;

/**
 * Class MonitoringController.
 */
class MonitoringController extends FormController
{
    use EntityContactsTrait;

    /*
     * @param int $page
     */
    public function indexAction($page = 1)
    {
        $session = $this->get('session');

        if ($this->request->getMethod() == 'POST') {
            $this->setListFilters();
        }

        /** @var \MauticPlugin\MauticSocialBundle\Model\MonitoringModel $model */
        $model = $this->getModel('social.monitoring');

        //set limits
        $limit = $session->get('mautic.social.monitoring.limit', $this->container->getParameter('mautic.default_pagelimit'));
        $start = ($page === 1) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search = $this->request->get('search', $session->get('mautic.social.monitoring.filter', ''));
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
            //the number of entities are now less then the current asset so redirect to the last asset
            if ($count === 1) {
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
                    'contentTemplate' => 'MauticSocialBundle:Monitoring:index',
                    'passthroughVars' => [
                        'activeLink'    => '#mautic_social_index',
                        'mauticContent' => 'monitoring',
                    ],
                ]
            );
        }

        //set what asset currently on so that we can return here after form submission/cancellation
        $session->set('mautic.social.monitoring.page', $page);

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

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
                'contentTemplate' => 'MauticSocialBundle:Monitoring:list.html.php',
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
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction()
    {
        if (!$this->container->get('mautic.security')->isGranted('plugin:mauticSocial:monitoring:create')) {
            return $this->accessDenied();
        }

        $action = $this->generateUrl('mautic_social_action', ['objectAction' => 'new']);

        /** @var \MauticPlugin\MauticSocialBundle\Model\MonitoringModel $model */
        $model = $this->getModel('social.monitoring');

        $entity  = $model->getEntity();
        $method  = $this->request->getMethod();
        $session = $this->get('session');

        // get the list of types from the model
        $networkTypes = $model->getNetworkTypes();

        // get the network type from the request on submit. helpful for validation error
        // rebuilds structure of the form when it gets updated on submit
        $networkType = ($this->request->getMethod() == 'POST') ? $this->request->request->get('monitoring[networkType]', '', true) : '';

        // build the form
        $form = $model->createForm(
            $entity,
            $this->get('form.factory'),
            $action,
            [
                // pass through the types and the selected default type
                'networkTypes' => $networkTypes,
                'networkType'  => $networkType,
            ]
        );

        // Set the page we came from
        $page = $session->get('mautic.social.monitoring.page', 1);
        ///Check for a submitted form and process it
        if ($method == 'POST') {
            $viewParameters = ['page' => $page];
            $template       = 'MauticSocialBundle:Monitoring:index';

            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {

                    //form is valid so process the data
                    $model->saveEntity($entity);

                    // update the audit log
                    $this->updateAuditLog($entity, 'create');

                    $this->addFlash(
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

                    if (!$form->get('buttons')->get('save')->isClicked()) {
                        //return edit view so that all the session stuff is loaded
                        return $this->editAction($entity->getId(), true);
                    }

                    $viewParameters = [
                        'objectAction' => 'view',
                        'objectId'     => $entity->getId(),
                    ];
                    $template = 'MauticSocialBundle:Monitoring:view';
                }
            }
            $returnUrl = $this->generateUrl('mautic_social_index', $viewParameters);

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
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
                    'tmpl'   => $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index',
                    'entity' => $entity,
                    'form'   => $form->createView(),
                ],
                'contentTemplate' => 'MauticSocialBundle:Monitoring:form.html.php',
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
     * @param $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction($objectId)
    {
        if (!$this->container->get('mautic.security')->isGranted('plugin:mauticSocial:monitoring:edit')) {
            return $this->accessDenied();
        }

        $action = $this->generateUrl('mautic_social_action', ['objectAction' => 'edit', 'objectId' => $objectId]);

        /** @var \MauticPlugin\MauticSocialBundle\Model\MonitoringModel $model */
        $model = $this->getModel('social.monitoring');

        $entity  = $model->getEntity($objectId);
        $session = $this->container->get('session');

        // Set the page we came from
        $page = $session->get('mautic.social.monitoring.page', 1);

        //set the return URL
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

        //not found
        if ($entity === null) {
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
        $networkType = ($this->request->getMethod() == 'POST') ? $this->request->request->get('monitoring[networkType]', '', true)
            : $entity->getNetworkType();

        // build the form
        $form = $model->createForm(
            $entity,
            $this->get('form.factory'),
            $action,
            [
                // pass through the types and the selected default type
                'networkTypes' => $networkTypes,
                'networkType'  => $networkType,
            ]
        );

        ///Check for a submitted form and process it
        if ($this->request->getMethod() == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    //form is valid so process the data
                    $model->saveEntity($entity, $form->get('buttons')->get('save')->isClicked());

                    // update the audit log
                    $this->updateAuditLog($entity, 'update');

                    $this->addFlash(
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

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
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
                            'contentTemplate' => 'MauticSocialBundle:Monitoring:view',
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
                    'tmpl'   => $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index',
                    'entity' => $entity,
                    'form'   => $form->createView(),
                ],
                'contentTemplate' => 'MauticSocialBundle:Monitoring:form.html.php',
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
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function viewAction($objectId)
    {
        if (!$this->get('mautic.security')->isGranted('plugin:mauticSocial:monitoring:view')) {
            return $this->accessDenied();
        }

        $session = $this->get('session');

        /** @var \MauticPlugin\MauticSocialBundle\Model\MonitoringModel $model */
        $model = $this->getModel('social.monitoring');

        /** @var \MauticPlugin\MauticSocialBundle\Entity\PostCountRepository $postCountRepo */
        $postCountRepo = $this->getModel('social.postcount')->getRepository();

        $security         = $this->container->get('mautic.security');
        $monitoringEntity = $model->getEntity($objectId);

        //set the asset we came from
        $page = $session->get('mautic.social.monitoring.page', 1);

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'details') : 'details';

        if ($monitoringEntity === null) {
            //set the return URL
            $returnUrl = $this->generateUrl('mautic_social_index', ['page' => $page]);

            return $this->postActionRedirect(
                [
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => ['page' => $page],
                    'contentTemplate' => 'MauticSocialMonitoringBundle:Monitoring:index',
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
        $logs = $this->getModel('core.auditLog')->getLogForObject('monitoring', $objectId);

        $returnUrl = $this->generateUrl(
            'mautic_social_action',
            [
                'objectAction' => 'view',
                'objectId'     => $monitoringEntity->getId(),
            ]
        );

        // Init the date range filter form
        $dateRangeValues = $this->request->get('daterange', []);
        $dateRangeForm   = $this->get('form.factory')->create('daterange', $dateRangeValues, ['action' => $returnUrl]);
        $dateFrom        = new \DateTime($dateRangeForm['date_from']->getData());
        $dateTo          = new \DateTime($dateRangeForm['date_to']->getData());

        $chart     = new LineChart(null, $dateFrom, $dateTo);
        $leadStats = $postCountRepo->getLeadStatsPost(
            $dateFrom,
            $dateTo,
            ['monitor_id' => $monitoringEntity->getId()]
        );
        $chart->setDataset($this->get('translator')->trans('mautic.social.twitter.tweet.count'), $leadStats);

        return $this->delegateView(
            [
                'returnUrl'      => $returnUrl,
                'viewParameters' => [
                    'activeMonitoring' => $monitoringEntity,
                    'logs'             => $logs,
                    'isEmbedded'       => $this->request->get('isEmbedded') ? $this->request->get('isEmbedded') : false,
                    'tmpl'             => $tmpl,
                    'security'         => $security,
                    'leadStats'        => $chart->render(),
                    'monitorLeads'     => $this->forward(
                        'MauticSocialBundle:Monitoring:contacts',
                        [
                            'objectId'   => $monitoringEntity->getId(),
                            'page'       => $page,
                            'ignoreAjax' => true,
                        ]
                    )->getContent(),
                    'dateRangeForm' => $dateRangeForm->createView(),
                ],
                'contentTemplate' => 'MauticSocialBundle:Monitoring:'.$tmpl.'.html.php',
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
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction($objectId)
    {
        if (!$this->get('mautic.security')->isGranted('plugin:mauticSocial:monitoring:delete')) {
            return $this->accessDenied();
        }

        $session   = $this->get('session');
        $page      = $session->get('mautic.social.monitoring.page', 1);
        $returnUrl = $this->generateUrl('mautic_social_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'MauticSocialBundle:Monitoring:index',
            'passthroughVars' => [
                'activeLink'    => 'mautic_social_index',
                'mauticContent' => 'monitoring',
            ],
        ];

        if ($this->request->getMethod() == 'POST') {

            /** @var \MauticPlugin\MauticSocialBundle\Model\MonitoringModel $model */
            $model  = $this->getModel('social.monitoring');
            $entity = $model->getEntity($objectId);

            if ($entity === null) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.social.monitoring.error.notfound',
                    'msgVars' => ['%id%' => $objectId],
                ];
            } elseif ($model->isLocked($entity)) {
                return $this->isLocked($postActionVars, $entity, 'plugin.mauticSocial.monitoring');
            }

            // update the audit log
            $this->updateAuditLog($entity, 'delete');

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
        if (!$this->container->get('mautic.security')->isGranted('plugin:mauticSocial:monitoring:delete')) {
            return $this->accessDenied();
        }

        $session   = $this->get('session');
        $page      = $session->get('mautic.social.monitoring.page', 1);
        $returnUrl = $this->generateUrl('mautic_social_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'MauticSocialBundle:Monitoring:index',
            'passthroughVars' => [
                'activeLink'    => '#mautic_social_index',
                'mauticContent' => 'monitoring',
            ],
        ];

        if ($this->request->getMethod() == 'POST') {
            /** @var \MauticPlugin\MauticSocialBundle\Model\MonitoringModel $model */
            $model = $this->getModel('social.monitoring');

            $ids       = json_decode($this->request->query->get('ids', ''));
            $deleteIds = [];

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $model->getEntity($objectId);

                if ($entity === null) {
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
            'plugin:mauticSocial:monitoring:view',
            'social',
            'monitoring_leads',
            null, // @todo - implement when individual social channels are supported by the plugin
            'monitor_id'
        );
    }

    /*
     * Update the audit log
     */
    public function updateAuditLog(Monitoring $monitoring, $action)
    {
        $log = [
            'bundle'    => 'plugin.mauticSocial',
            'object'    => 'monitoring',
            'objectId'  => $monitoring->getId(),
            'action'    => $action,
            'details'   => ['name' => $monitoring->getTitle()],
            'ipAddress' => $this->container->get('mautic.helper.ip_lookup')->getIpAddressFromRequest(),
        ];

        $this->getModel('core.auditLog')->writeToLog($log);
    }
}
