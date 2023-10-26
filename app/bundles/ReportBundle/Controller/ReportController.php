<?php

namespace Mautic\ReportBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Factory\PageHelperFactoryInterface;
use Mautic\CoreBundle\Form\Type\DateRangeType;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Form\Type\DynamicFiltersType;
use Mautic\ReportBundle\Model\ExportResponse;
use Symfony\Component\HttpFoundation;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class ReportController extends FormController
{
    /**
     * @param int $page
     *
     * @return HttpFoundation\JsonResponse|HttpFoundation\RedirectResponse|HttpFoundation\Response
     */
    public function indexAction($page = 1)
    {
        /* @type \Mautic\ReportBundle\Model\ReportModel $model */
        $model = $this->getModel('report');

        //set some permissions
        $permissions = $this->container->get('mautic.security')->isGranted(
            [
                'report:reports:viewown',
                'report:reports:viewother',
                'report:reports:create',
                'report:reports:editown',
                'report:reports:editother',
                'report:reports:deleteown',
                'report:reports:deleteother',
                'report:reports:publishown',
                'report:reports:publishother',
            ],
            'RETURN_ARRAY'
        );

        if (!$permissions['report:reports:viewown'] && !$permissions['report:reports:viewother']) {
            return $this->accessDenied();
        }

        $this->setListFilters();

        /** @var PageHelperFactoryInterface $pageHelperFacotry */
        $pageHelperFacotry = $this->get('mautic.page.helper.factory');
        $pageHelper        = $pageHelperFacotry->make('mautic.report', $page);

        $limit  = $pageHelper->getLimit();
        $start  = $pageHelper->getStart();
        $search = $this->request->get('search', $this->container->get('session')->get('mautic.report.filter', ''));
        $filter = ['string' => $search, 'force' => []];
        $this->container->get('session')->set('mautic.report.filter', $search);

        if (!$permissions['report:reports:viewother']) {
            $filter['force'][] = ['column' => 'r.createdBy', 'expr' => 'eq', 'value' => $this->user->getId()];
        }

        $orderBy    = $this->container->get('session')->get('mautic.report.orderby', 'r.name');
        $orderByDir = $this->container->get('session')->get('mautic.report.orderbydir', 'DESC');
        $reports    = $model->getEntities(
            [
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir,
            ]
        );

        $count = count($reports);
        if ($count && $count < ($start + 1)) {
            $lastPage  = $pageHelper->countPage($count);
            $returnUrl = $this->generateUrl('mautic_report_index', ['page' => $lastPage]);
            $pageHelper->rememberPage($lastPage);

            return $this->postActionRedirect(
                [
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => ['page' => $lastPage],
                    'contentTemplate' => 'MauticReportBundle:Report:index',
                    'passthroughVars' => [
                        'activeLink'    => '#mautic_report_index',
                        'mauticContent' => 'report',
                    ],
                ]
            );
        }

        $pageHelper->rememberPage($page);

        return $this->delegateView(
            [
                'viewParameters' => [
                    'searchValue' => $search,
                    'items'       => $reports,
                    'totalItems'  => $count,
                    'page'        => $page,
                    'limit'       => $limit,
                    'permissions' => $permissions,
                    'model'       => $model,
                    'tmpl'        => $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index',
                    'security'    => $this->container->get('mautic.security'),
                ],
                'contentTemplate' => 'MauticReportBundle:Report:list.html.php',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_report_index',
                    'mauticContent' => 'report',
                    'route'         => $this->generateUrl('mautic_report_index', ['page' => $page]),
                ],
            ]
        );
    }

    /**
     * Clone an entity.
     *
     * @param int $objectId
     *
     * @return HttpFoundation\JsonResponse|HttpFoundation\RedirectResponse|HttpFoundation\Response
     */
    public function cloneAction($objectId)
    {
        /* @type \Mautic\ReportBundle\Model\ReportModel $model */
        $model  = $this->getModel('report');
        $entity = $model->getEntity($objectId);

        if (null != $entity) {
            if (!$this->container->get('mautic.security')->isGranted('report:reports:create')
                || !$this->container->get('mautic.security')->hasEntityAccess(
                    'report:reports:viewown',
                    'report:reports:viewother',
                    $entity->getCreatedBy()
                )
            ) {
                return $this->accessDenied();
            }

            $entity = clone $entity;
            $entity->setId(null);
            $entity->setIsPublished(false);
        }

        return $this->newAction($entity);
    }

    /**
     * Deletes the entity.
     *
     * @param $objectId
     *
     * @return HttpFoundation\JsonResponse|HttpFoundation\RedirectResponse
     */
    public function deleteAction($objectId)
    {
        $page      = $this->container->get('session')->get('mautic.report.page', 1);
        $returnUrl = $this->generateUrl('mautic_report_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'MauticReportBundle:Report:index',
            'passthroughVars' => [
                'activeLink'    => '#mautic_report_index',
                'mauticContent' => 'report',
            ],
        ];

        if ('POST' == $this->request->getMethod()) {
            /* @type \Mautic\ReportBundle\Model\ReportModel $model */
            $model  = $this->getModel('report');
            $entity = $model->getEntity($objectId);

            $check = $this->checkEntityAccess(
                $postActionVars,
                $entity,
                $objectId,
                ['report:reports:deleteown', 'report:reports:deleteother'],
                $model,
                'report'
            );
            if (true !== $check) {
                return $check;
            }

            $model->deleteEntity($entity);

            $identifier = $this->get('translator')->trans($entity->getName());
            $flashes[]  = [
                'type'    => 'notice',
                'msg'     => 'mautic.core.notice.deleted',
                'msgVars' => [
                    '%name%' => $identifier,
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
        $page      = $this->container->get('session')->get('mautic.report.page', 1);
        $returnUrl = $this->generateUrl('mautic_report_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'MauticReportBundle:Report:index',
            'passthroughVars' => [
                'activeLink'    => '#mautic_report_index',
                'mauticContent' => 'report',
            ],
        ];

        if ('POST' == $this->request->getMethod()) {
            $model     = $this->getModel('report');
            $ids       = json_decode($this->request->query->get('ids', '{}'));
            $deleteIds = [];

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $model->getEntity($objectId);

                if (null === $entity) {
                    $flashes[] = [
                        'type'    => 'error',
                        'msg'     => 'mautic.report.report.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ];
                } elseif (!$this->container->get('mautic.security')->hasEntityAccess(
                    'report:reports:deleteown',
                    'report:reports:deleteother',
                    $entity->getCreatedBy()
                )
                ) {
                    $flashes[] = $this->accessDenied(true);
                } elseif ($model->isLocked($entity)) {
                    $flashes[] = $this->isLocked($postActionVars, $entity, 'report', true);
                } else {
                    $deleteIds[] = $objectId;
                }
            }

            // Delete everything we are able to
            if (!empty($deleteIds)) {
                $entities = $model->deleteEntities($deleteIds);

                $flashes[] = [
                    'type'    => 'notice',
                    'msg'     => 'mautic.report.report.notice.batch_deleted',
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
     * Generates edit form and processes post data.
     *
     * @param int  $objectId   Item ID
     * @param bool $ignorePost Flag to ignore POST data
     *
     * @return HttpFoundation\JsonResponse|HttpFoundation\RedirectResponse|HttpFoundation\Response
     */
    public function editAction($objectId, $ignorePost = false)
    {
        /* @type \Mautic\ReportBundle\Model\ReportModel $model */
        $model   = $this->getModel('report');
        $entity  = $model->getEntity($objectId);
        $session = $this->container->get('session');
        $page    = $session->get('mautic.report.page', 1);

        //set the return URL
        $returnUrl = $this->generateUrl('mautic_report_index', ['page' => $page]);

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'MauticReportBundle:Report:index',
            'passthroughVars' => [
                'activeLink'    => 'mautic_report_index',
                'mauticContent' => 'report',
            ],
        ];

        //not found
        $check = $this->checkEntityAccess(
            $postActionVars,
            $entity,
            $objectId,
            ['report:reports:viewown', 'report:reports:viewother'],
            $model,
            'report'
        );
        if (true !== $check) {
            return $check;
        }

        //Create the form
        $action = $this->generateUrl('mautic_report_action', ['objectAction' => 'edit', 'objectId' => $objectId]);
        $form   = $model->createForm($entity, $this->get('form.factory'), $action);

        ///Check for a submitted form and process it
        if (!$ignorePost && 'POST' == $this->request->getMethod()) {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                // Columns have to be reset in order for Symfony to honor the new submitted order
                $oldColumns = $entity->getColumns();
                $entity->setColumns([]);

                $oldGraphs = $entity->getGraphs();
                $entity->setGraphs([]);
                if ($valid = $this->isFormValid($form)) {
                    //form is valid so process the data
                    $model->saveEntity($entity, $form->get('buttons')->get('save')->isClicked());

                    $this->addFlash(
                        'mautic.core.notice.updated',
                        [
                            '%name%'      => $entity->getName(),
                            '%menu_link%' => 'mautic_report_index',
                            '%url%'       => $this->generateUrl(
                                'mautic_report_action',
                                [
                                    'objectAction' => 'edit',
                                    'objectId'     => $entity->getId(),
                                ]
                            ),
                        ]
                    );

                    $returnUrl = $this->generateUrl(
                        'mautic_report_view',
                        [
                            'objectId' => $entity->getId(),
                        ]
                    );
                    $viewParams = ['objectId' => $entity->getId()];
                    $template   = 'MauticReportBundle:Report:view';
                } else {
                    //reset old columns
                    $entity->setColumns($oldColumns);
                    $entity->setGraphs($oldGraphs);
                }
            } else {
                //unlock the entity
                $model->unlockEntity($entity);

                $returnUrl  = $this->generateUrl('mautic_report_index', ['page' => $page]);
                $viewParams = ['report' => $page];
                $template   = 'MauticReportBundle:Report:index';
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                // Clear session items in case columns changed
                $session->remove('mautic.report.'.$entity->getId().'.orderby');
                $session->remove('mautic.report.'.$entity->getId().'.orderbydir');

                return $this->postActionRedirect(
                    array_merge(
                        $postActionVars,
                        [
                            'returnUrl'       => $returnUrl,
                            'viewParameters'  => $viewParams,
                            'contentTemplate' => $template,
                        ]
                    )
                );
            } elseif ($valid) {
                // Rebuild the form for updated columns
                $form = $model->createForm($entity, $this->get('form.factory'), $action);
            }
        } else {
            //lock the entity
            $model->lockEntity($entity);
        }

        return $this->delegateView(
            [
                'viewParameters' => [
                    'report' => $entity,
                    'form'   => $this->setFormTheme($form, 'MauticReportBundle:Report:form.html.php', 'MauticReportBundle:FormTheme\Report'),
                ],
                'contentTemplate' => 'MauticReportBundle:Report:form.html.php',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_report_index',
                    'mauticContent' => 'report',
                    'route'         => $this->generateUrl(
                        'mautic_report_action',
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
     * Generates new form and processes post data.
     *
     * @param \Mautic\ReportBundle\Entity\Report|null $entity
     *
     * @return HttpFoundation\JsonResponse|HttpFoundation\RedirectResponse|HttpFoundation\Response
     */
    public function newAction($entity = null)
    {
        if (!$this->container->get('mautic.security')->isGranted('report:reports:create')) {
            return $this->accessDenied();
        }

        /* @type \Mautic\ReportBundle\Model\ReportModel $model */
        $model = $this->getModel('report');

        if (!($entity instanceof Report)) {
            /** @var \Mautic\ReportBundle\Entity\Report $entity */
            $entity = $model->getEntity();
        }

        $session = $this->container->get('session');
        $page    = $session->get('mautic.report.page', 1);

        $action = $this->generateUrl('mautic_report_action', ['objectAction' => 'new']);
        $form   = $model->createForm($entity, $this->get('form.factory'), $action);

        ///Check for a submitted form and process it
        if ('POST' == $this->request->getMethod()) {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    //form is valid so process the data
                    $model->saveEntity($entity);

                    $this->addFlash(
                        'mautic.core.notice.created',
                        [
                            '%name%'      => $entity->getName(),
                            '%menu_link%' => 'mautic_report_index',
                            '%url%'       => $this->generateUrl(
                                'mautic_report_action',
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
                        'objectId' => $entity->getId(),
                    ];
                    $returnUrl = $this->generateUrl('mautic_report_view', $viewParameters);
                    $template  = 'MauticReportBundle:Report:view';
                }
            } else {
                $viewParameters = ['page' => $page];
                $returnUrl      = $this->generateUrl('mautic_report_index', $viewParameters);
                $template       = 'MauticReportBundle:Report:index';
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                return $this->postActionRedirect(
                    [
                        'returnUrl'       => $returnUrl,
                        'viewParameters'  => $viewParameters,
                        'contentTemplate' => $template,
                        'passthroughVars' => [
                            'activeLink'    => 'mautic_asset_index',
                            'mauticContent' => 'asset',
                        ],
                    ]
                );
            }
        }

        return $this->delegateView(
            [
                'viewParameters' => [
                    'report' => $entity,
                    'form'   => $this->setFormTheme($form, 'MauticReportBundle:Report:form.html.php', 'MauticReportBundle:FormTheme\Report'),
                ],
                'contentTemplate' => 'MauticReportBundle:Report:form.html.php',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_report_index',
                    'mauticContent' => 'report',
                    'route'         => $this->generateUrl(
                        'mautic_report_action',
                        [
                            'objectAction' => 'new',
                        ]
                    ),
                ],
            ]
        );
    }

    /**
     * Shows a report.
     *
     * @param int $objectId   Report ID
     * @param int $reportPage
     *
     * @return HttpFoundation\JsonResponse|HttpFoundation\Response
     */
    public function viewAction($objectId, $reportPage = 1)
    {
        /* @type \Mautic\ReportBundle\Model\ReportModel $model */
        $model    = $this->getModel('report');
        $entity   = $model->getEntity($objectId);
        $security = $this->container->get('mautic.security');

        if (null === $entity) {
            $page = $this->container->get('session')->get('mautic.report.page', 1);

            return $this->postActionRedirect(
                [
                    'returnUrl'       => $this->generateUrl('mautic_report_index', ['page' => $page]),
                    'viewParameters'  => ['page' => $page],
                    'contentTemplate' => 'MauticReportBundle:Report:index',
                    'passthroughVars' => [
                        'activeLink'    => '#mautic_report_index',
                        'mauticContent' => 'report',
                    ],
                    'flashes' => [
                        [
                            'type'    => 'error',
                            'msg'     => 'mautic.report.report.error.notfound',
                            'msgVars' => ['%id%' => $objectId],
                        ],
                    ],
                ]
            );
        } elseif (!$security->hasEntityAccess('report:reports:viewown', 'report:reports:viewother', $entity->getCreatedBy())) {
            return $this->accessDenied();
        }

        $this->setListFilters();

        $mysqlFormat = 'Y-m-d';
        $session     = $this->container->get('session');

        // Init the forms
        $action = $this->generateUrl('mautic_report_action', ['objectAction' => 'view', 'objectId' => $objectId]);

        // Get the date range filter values from the request of from the session
        $dateRangeValues = $this->request->get('daterange', []);

        if (!empty($dateRangeValues['date_from'])) {
            $from = new \DateTime($dateRangeValues['date_from']);
            $session->set('mautic.report.date.from', $from->format($mysqlFormat));
        } elseif ($fromDate = $session->get('mautic.report.date.from')) {
            $dateRangeValues['date_from'] = $fromDate;
        }
        if (!empty($dateRangeValues['date_to'])) {
            $to = new \DateTime($dateRangeValues['date_to']);
            $session->set('mautic.report.date.to', $to->format($mysqlFormat));
        } elseif ($toDate = $session->get('mautic.report.date.to')) {
            $dateRangeValues['date_to'] = $toDate;
        }

        $dateRangeForm = $this->get('form.factory')->create(DateRangeType::class, $dateRangeValues, ['action' => $action]);
        if ('POST' == $this->request->getMethod() && $this->request->request->has('daterange')) {
            if ($this->isFormValid($dateRangeForm)) {
                $to                         = new \DateTime($dateRangeForm['date_to']->getData());
                $dateRangeValues['date_to'] = $to->format($mysqlFormat);
                $session->set('mautic.report.date.to', $dateRangeValues['date_to']);

                $from                         = new \DateTime($dateRangeForm['date_from']->getData());
                $dateRangeValues['date_from'] = $from->format($mysqlFormat);
                $session->set('mautic.report.date.from', $dateRangeValues['date_from']);
            }
        }

        // Setup dynamic filters
        $filterDefinitions = $model->getFilterList($entity->getSource());
        /** @var array $dynamicFilters */
        $dynamicFilters = $session->get('mautic.report.'.$objectId.'.filters', []);
        $filterSettings = [];

        if (count($dynamicFilters) > 0 && count($entity->getFilters()) > 0) {
            foreach ($entity->getFilters() as $filter) {
                foreach ($dynamicFilters as $dfcol => $dfval) {
                    if (1 === $filter['dynamic'] && $filter['column'] === $dfcol) {
                        $dynamicFilters[$dfcol]['expr'] = $filter['condition'];
                        break;
                    }
                }
            }
        }

        foreach ($dynamicFilters as $filter) {
            $filterSettings[$filterDefinitions->definitions[$filter['column']]['alias']] = $filter['value'];
        }

        $dynamicFilterForm = $this->get('form.factory')->create(
            DynamicFiltersType::class,
            $filterSettings,
            [
                'action'            => $action,
                'report'            => $entity,
                'filterDefinitions' => $filterDefinitions,
            ]
        );

        $reportData = $model->getReportData(
            $entity,
            $this->container->get('form.factory'),
            [
                'dynamicFilters' => $dynamicFilters,
                'paginate'       => true,
                'reportPage'     => $reportPage,
                'dateFrom'       => new \DateTime($dateRangeForm->get('date_from')->getData()),
                'dateTo'         => new \DateTime($dateRangeForm->get('date_to')->getData()),
            ]
        );

        return $this->delegateView(
            [
                'viewParameters' => [
                    'data'         => $reportData['data'],
                    'columns'      => $reportData['columns'],
                    'dataColumns'  => $reportData['dataColumns'],
                    'totalResults' => $reportData['totalResults'],
                    'debug'        => $reportData['debug'],
                    'report'       => $entity,
                    'reportPage'   => $reportPage,
                    'graphs'       => $reportData['graphs'],
                    'tmpl'         => $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index',
                    'limit'        => $reportData['limit'],
                    'permissions'  => $security->isGranted(
                        [
                            'report:reports:viewown',
                            'report:reports:viewother',
                            'report:reports:create',
                            'report:reports:editown',
                            'report:reports:editother',
                            'report:reports:deleteown',
                            'report:reports:deleteother',
                        ],
                        'RETURN_ARRAY'
                    ),
                    'dateRangeForm'     => $dateRangeForm->createView(),
                    'dynamicFilterForm' => $dynamicFilterForm->createView(),
                ],
                'contentTemplate' => $reportData['contentTemplate'],
                'passthroughVars' => [
                    'activeLink'    => '#mautic_report_index',
                    'mauticContent' => 'report',
                    'route'         => $this->generateUrl(
                        'mautic_report_view',
                        [
                            'objectId'   => $entity->getId(),
                            'reportPage' => $reportPage,
                        ]
                    ),
                ],
            ]
        );
    }

    /**
     * Checks access to an entity.
     *
     * @param object                               $entity
     * @param int                                  $objectId
     * @param \Mautic\CoreBundle\Model\CommonModel $model
     * @param string                               $modelName
     *
     * @return HttpFoundation\JsonResponse|HttpFoundation\RedirectResponse|void
     */
    private function checkEntityAccess($postActionVars, $entity, $objectId, array $permissions, $model, $modelName)
    {
        if (null === $entity) {
            return $this->postActionRedirect(
                array_merge(
                    $postActionVars,
                    [
                        'flashes' => [
                            [
                                'type'    => 'error',
                                'msg'     => 'mautic.report.report.error.notfound',
                                'msgVars' => ['%id%' => $objectId],
                            ],
                        ],
                    ]
                )
            );
        } elseif (!$this->container->get('mautic.security')->hasEntityAccess($permissions[0], $permissions[1], $entity->getCreatedBy())) {
            return $this->accessDenied();
        } elseif ($model->isLocked($entity)) {
            //deny access if the entity is locked
            return $this->isLocked($postActionVars, $entity, $modelName);
        }

        return true;
    }

    /**
     * @param int    $objectId
     * @param string $format
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     *
     * @throws \Exception
     */
    public function exportAction($objectId, $format = 'csv')
    {
        /** @var \Mautic\ReportBundle\Model\ReportModel $model */
        $model    = $this->getModel('report');
        $entity   = $model->getEntity($objectId);
        $security = $this->container->get('mautic.security');

        if (null === $entity) {
            $page = $this->container->get('session')->get('mautic.report.page', 1);

            return $this->postActionRedirect(
                [
                    'returnUrl'       => $this->generateUrl('mautic_report_index', ['page' => $page]),
                    'viewParameters'  => ['page' => $page],
                    'contentTemplate' => 'MauticReportBundle:Report:index',
                    'passthroughVars' => [
                        'activeLink'    => '#mautic_report_index',
                        'mauticContent' => 'report',
                    ],
                    'flashes' => [
                        [
                            'type'    => 'error',
                            'msg'     => 'mautic.report.report.error.notfound',
                            'msgVars' => ['%id%' => $objectId],
                        ],
                    ],
                ]
            );
        } elseif (!$security->hasEntityAccess('report:reports:viewown', 'report:reports:viewother', $entity->getCreatedBy())) {
            return $this->accessDenied();
        }

        $session  = $this->get('session');
        $fromDate = $session->get('mautic.report.date.from', (new \DateTime('-30 days'))->format('Y-m-d'));
        $toDate   = $session->get('mautic.report.date.to', (new \DateTime())->format('Y-m-d'));

        $date    = (new DateTimeHelper())->toLocalString();
        $name    = str_replace(' ', '_', $date).'_'.InputHelper::alphanum($entity->getName(), false, '-');
        $options = ['dateFrom' => new \DateTime($fromDate), 'dateTo' => new \DateTime($toDate)];

        $dynamicFilters            = $session->get('mautic.report.'.$objectId.'.filters', []);
        $options['dynamicFilters'] = $dynamicFilters;

        if ('csv' === $format) {
            $response = new HttpFoundation\StreamedResponse(
                function () use ($model, $entity, $format, $options) {
                    $options['paginate']        = true;
                    $options['ignoreGraphData'] = true;
                    $options['limit']           = (int) $this->coreParametersHelper->getParameter('report_export_batch_size', 1000);
                    $options['page']            = 1;
                    $handle                     = fopen('php://output', 'r+');
                    do {
                        $reportData = $model->getReportData($entity, null, $options);

                        // Note this so that it's not recalculated on each batch
                        $options['totalResults'] = $reportData['totalResults'];

                        $model->exportResults($format, $entity, $reportData, $handle, $options['page']);
                        ++$options['page'];
                    } while (!empty($reportData['data']));

                    fclose($handle);
                }
            );
            $fileName = $name.'.'.$format;
            ExportResponse::setResponseHeaders($response, $fileName);
        } else {
            if ('xlsx' === $format) {
                $options['ignoreGraphData'] = true;
            }
            $reportData = $model->getReportData($entity, null, $options);
            $response   = $model->exportResults($format, $entity, $reportData);
        }

        return $response;
    }

    /**
     * @param int    $reportId
     * @param string $format
     *
     * @return BinaryFileResponse
     *
     * @throws \Exception
     */
    public function downloadAction($reportId, $format = 'csv')
    {
        if ('csv' !== $format) {
            throw new \Exception($this->translator->trans('mautic.format.invalid', ['%format%' => $format, '%validFormats%' => 'csv']));
        }

        /** @var \Mautic\ReportBundle\Model\ReportModel $model */
        $model = $this->getModel('report');

        /** @var \Mautic\ReportBundle\Entity\Report $report */
        $report = $model->getEntity($reportId);

        /** @var \Mautic\CoreBundle\Security\Permissions\CorePermissions $security */
        $security = $this->container->get('mautic.security');

        /** @var \Mautic\ReportBundle\Scheduler\Model\FileHandler $fileHandler */
        $fileHandler = $this->container->get('mautic.report.model.file_handler');

        if (empty($report)) {
            return $this->notFound($this->translator->trans('mautic.report.notfound', ['%id%' => $reportId]));
        }

        if (!$security->hasEntityAccess('report:reports:viewown', 'report:reports:viewother', $report->getCreatedBy())) {
            return $this->accessDenied();
        }

        if (!$fileHandler->compressedCsvFileForReportExists($report)) {
            if ($report->isScheduled()) {
                $message = 'mautic.report.download.missing';
            } else {
                $message = 'mautic.report.download.missing.but.scheduled';
                $report->setAsScheduledNow($this->user->getEmail());
                $model->saveEntity($report);
            }

            return $this->notFound($this->translator->trans($message, ['%id%' => $reportId]));
        }

        $response = new BinaryFileResponse($fileHandler->getPathToCompressedCsvFileForReport($report));

        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, "report-{$report->getId()}.zip");

        return $response;
    }
}
