<?php

namespace Mautic\ReportBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Factory\PageHelperFactoryInterface;
use Mautic\CoreBundle\Form\Type\DateRangeType;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\ReportBundle\Crate\ReportDataResult;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Form\Type\DynamicFiltersType;
use Mautic\ReportBundle\Model\ExportResponse;
use Mautic\ReportBundle\Model\ReportModel;
use Mautic\ReportBundle\Scheduler\Model\FileHandler;
use Symfony\Component\HttpFoundation;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class ReportController extends FormController
{
    /**
     * @param int $page
     *
     * @return HttpFoundation\JsonResponse|HttpFoundation\RedirectResponse|HttpFoundation\Response
     */
    public function indexAction(Request $request, PageHelperFactoryInterface $pageHelperFactory, $page = 1)
    {
        /* @type \Mautic\ReportBundle\Model\ReportModel $model */
        $model = $this->getModel('report');

        // set some permissions
        $permissions = $this->security->isGranted(
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

        $pageHelper        = $pageHelperFactory->make('mautic.report', $page);

        $limit  = $pageHelper->getLimit();
        $start  = $pageHelper->getStart();
        $search = $request->get('search', $request->getSession()->get('mautic.report.filter', ''));
        $filter = ['string' => $search, 'force' => []];
        $request->getSession()->set('mautic.report.filter', $search);

        if (!$permissions['report:reports:viewother']) {
            $filter['force'][] = ['column' => 'r.createdBy', 'expr' => 'eq', 'value' => $this->user->getId()];
        }

        $orderBy    = $request->getSession()->get('mautic.report.orderby', 'r.name');
        $orderByDir = $request->getSession()->get('mautic.report.orderbydir', 'DESC');
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
                    'contentTemplate' => 'Mautic\ReportBundle\Controller\ReportController::indexAction',
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
                    'tmpl'        => $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index',
                    'security'    => $this->security,
                ],
                'contentTemplate' => '@MauticReport/Report/list.html.twig',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_report_index',
                    'mauticContent' => 'report',
                    'route'         => $this->generateUrl('mautic_report_index', ['page' => $page]),
                ],
            ]
        );
    }

    /**
     * @param int $objectId
     */
    public function cloneAction(ReportModel $model, Request $request, $objectId): Response
    {
        $entity = $model->getEntity($objectId);

        if ($entity) {
            if (!$this->security->isGranted('report:reports:create')
                || !$this->security->hasEntityAccess(
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

        return $this->newAction($model, $request, $entity);
    }

    /**
     * Deletes the entity.
     *
     * @return array<string, string|array<string, string>>|bool|HttpFoundation\JsonResponse|HttpFoundation\RedirectResponse|HttpFoundation\Response
     */
    public function deleteAction(Request $request, $objectId)
    {
        $page      = $request->getSession()->get('mautic.report.page', 1);
        $returnUrl = $this->generateUrl('mautic_report_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\ReportBundle\Controller\ReportController::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_report_index',
                'mauticContent' => 'report',
            ],
        ];

        if (HttpFoundation\Request::METHOD_POST === $request->getMethod()) {
            $model = $this->getModel('report');
            \assert($model instanceof ReportModel);
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

            $identifier = $this->translator->trans($entity->getName());
            $flashes[]  = [
                'type'    => 'notice',
                'msg'     => 'mautic.core.notice.deleted',
                'msgVars' => [
                    '%name%' => $identifier,
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
     * @return HttpFoundation\Response
     */
    public function batchDeleteAction(Request $request)
    {
        $page      = $request->getSession()->get('mautic.report.page', 1);
        $returnUrl = $this->generateUrl('mautic_report_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\ReportBundle\Controller\ReportController::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_report_index',
                'mauticContent' => 'report',
            ],
        ];

        if (HttpFoundation\Request::METHOD_POST === $request->getMethod()) {
            $model = $this->getModel('report');
            \assert($model instanceof ReportModel);
            $ids       = json_decode($request->query->get('ids', '{}'));
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
                } elseif (!$this->security->hasEntityAccess(
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
     * Generates edit form and processes post data.
     *
     * @param int  $objectId   Item ID
     * @param bool $ignorePost Flag to ignore POST data
     *
     * @return HttpFoundation\JsonResponse|HttpFoundation\RedirectResponse|HttpFoundation\Response
     */
    public function editAction(Request $request, $objectId, $ignorePost = false)
    {
        $model = $this->getModel('report');
        \assert($model instanceof ReportModel);
        $entity  = $model->getEntity($objectId);
        $session = $request->getSession();
        $page    = $session->get('mautic.report.page', 1);

        // set the return URL
        $returnUrl = $this->generateUrl('mautic_report_index', ['page' => $page]);

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\ReportBundle\Controller\ReportController::indexAction',
            'passthroughVars' => [
                'activeLink'    => 'mautic_report_index',
                'mauticContent' => 'report',
            ],
        ];

        // not found
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

        // Create the form
        $action = $this->generateUrl('mautic_report_action', ['objectAction' => 'edit', 'objectId' => $objectId]);
        $form   = $model->createForm($entity, $this->formFactory, $action);

        // /Check for a submitted form and process it
        if (!$ignorePost && 'POST' == $request->getMethod()) {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                // Columns have to be reset in order for Symfony to honor the new submitted order
                $oldColumns = $entity->getColumns();
                $entity->setColumns([]);

                $oldGraphs = $entity->getGraphs();
                $entity->setGraphs([]);
                if ($valid = $this->isFormValid($form)) {
                    // form is valid so process the data
                    $model->saveEntity($entity, $this->getFormButton($form, ['buttons', 'save'])->isClicked());

                    $this->addFlashMessage(
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
                    $template   = 'Mautic\ReportBundle\Controller\ReportController::viewAction';
                } else {
                    // reset old columns
                    $entity->setColumns($oldColumns);
                    $entity->setGraphs($oldGraphs);
                }
            } else {
                // unlock the entity
                $model->unlockEntity($entity);

                $returnUrl  = $this->generateUrl('mautic_report_index', ['page' => $page]);
                $viewParams = ['report' => $page];
                $template   = 'Mautic\ReportBundle\Controller\ReportController::indexAction';
            }

            if ($cancelled || ($valid && $this->getFormButton($form, ['buttons', 'save'])->isClicked())) {
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
                $form = $model->createForm($entity, $this->formFactory, $action);
            }
        } else {
            // lock the entity
            $model->lockEntity($entity);
        }

        return $this->delegateView(
            [
                'viewParameters' => [
                    'report' => $entity,
                    'form'   => $form->createView(),
                ],
                'contentTemplate' => '@MauticReport/Report/form.html.twig',
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

    public function newAction(ReportModel $model, Request $request, ?Report $entity = null): Response
    {
        if (!$this->security->isGranted('report:reports:create')) {
            return $this->accessDenied();
        }

        if (!($entity instanceof Report)) {
            $entity = $model->getEntity();
        }

        $session = $request->getSession();
        $page    = $session->get('mautic.report.page', 1);

        $action = $this->generateUrl('mautic_report_action', ['objectAction' => 'new']);
        $form   = $model->createForm($entity, $this->formFactory, $action);

        // /Check for a submitted form and process it
        if (HttpFoundation\Request::METHOD_POST === $request->getMethod()) {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    // form is valid so process the data
                    $model->saveEntity($entity);

                    $this->addFlashMessage(
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

                    if (!$this->getFormButton($form, ['buttons', 'save'])->isClicked()) {
                        // return edit view so that all the session stuff is loaded
                        return $this->editAction($request, $entity->getId(), true);
                    }

                    $viewParameters = ['objectId' => $entity->getId()];
                    $returnUrl      = $this->generateUrl('mautic_report_view', $viewParameters);
                    $template       = 'Mautic\ReportBundle\Controller\ReportController::viewAction';
                }
            } else {
                $viewParameters = ['page' => $page];
                $returnUrl      = $this->generateUrl('mautic_report_index', $viewParameters);
                $template       = 'Mautic\ReportBundle\Controller\ReportController::indexAction';
            }

            if ($cancelled || ($valid && $this->getFormButton($form, ['buttons', 'save'])->isClicked())) {
                return $this->postActionRedirect(
                    [
                        'returnUrl'       => $returnUrl,
                        'viewParameters'  => $viewParameters,
                        'contentTemplate' => $template,
                        'passthroughVars' => [
                            'activeLink'    => '#mautic_report_index',
                            'mauticContent' => 'report',
                        ],
                    ]
                );
            }
        }

        return $this->delegateView(
            [
                'viewParameters' => [
                    'report' => $entity,
                    'form'   => $form->createView(),
                ],
                'contentTemplate' => '@MauticReport/Report/form.html.twig',
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
    public function viewAction(Request $request, $objectId, $reportPage = 1)
    {
        $model = $this->getModel('report');
        \assert($model instanceof ReportModel);
        $entity   = $model->getEntity($objectId);
        $security = $this->security;

        if (null === $entity) {
            $page = $request->getSession()->get('mautic.report.page', 1);

            return $this->postActionRedirect(
                [
                    'returnUrl'       => $this->generateUrl('mautic_report_index', ['page' => $page]),
                    'viewParameters'  => ['page' => $page],
                    'contentTemplate' => 'Mautic\ReportBundle\Controller\ReportController::indexAction',
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
        $session     = $request->getSession();

        // Init the forms
        $action = $this->generateUrl('mautic_report_action', ['objectAction' => 'view', 'objectId' => $objectId]);

        // Get the date range filter values from the request of from the session
        $dateRangeValues = $request->get('daterange', []);

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

        $dateRangeForm = $this->formFactory->create(DateRangeType::class, $dateRangeValues, ['action' => $action]);
        if ('POST' === $request->getMethod() && $request->request->has('daterange')) {
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

        $dynamicFilterForm = $this->formFactory->create(
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
            $this->formFactory,
            [
                'dynamicFilters' => $dynamicFilters,
                'paginate'       => true,
                'reportPage'     => $reportPage,
                'dateFrom'       => new \DateTime($dateRangeForm->get('date_from')->getData()),
                'dateTo'         => new \DateTime($dateRangeForm->get('date_to')->getData()),
            ]
        );

        $reportDataResult = new ReportDataResult($reportData);

        return $this->delegateView(
            [
                'viewParameters' => [
                    'data'             => $reportData['data'],
                    'columns'          => $reportData['columns'],
                    'dataColumns'      => $reportData['dataColumns'],
                    'totalResults'     => $reportData['totalResults'],
                    'debug'            => $reportData['debug'],
                    'report'           => $entity,
                    'reportPage'       => $reportPage,
                    'graphs'           => $reportData['graphs'],
                    'reportDataResult' => $reportDataResult,
                    'tmpl'             => $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index',
                    'limit'            => $reportData['limit'],
                    'permissions'      => $security->isGranted(
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
     * @param array<mixed> $postActionVars
     * @param array<mixed> $permissions
     *
     * @return array<string, string|array<string, string>>|bool|HttpFoundation\JsonResponse|HttpFoundation\RedirectResponse|HttpFoundation\Response
     */
    private function checkEntityAccess(array $postActionVars, ?Report $entity, int $objectId, array $permissions, ReportModel $model, string $modelName)
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
        } elseif (!$this->security->hasEntityAccess($permissions[0], $permissions[1], $entity->getCreatedBy())) {
            return $this->accessDenied();
        } elseif ($model->isLocked($entity)) {
            // deny access if the entity is locked
            return $this->isLocked($postActionVars, $entity, $modelName);
        }

        return true;
    }

    /**
     * @param int    $objectId
     * @param string $format
     *
     * @return HttpFoundation\Response
     *
     * @throws \Exception
     */
    public function exportAction(Request $request, $objectId, $format = 'csv')
    {
        /** @var \Mautic\ReportBundle\Model\ReportModel $model */
        $model    = $this->getModel('report');
        $entity   = $model->getEntity($objectId);
        $security = $this->security;

        if (null === $entity) {
            $page = $request->getSession()->get('mautic.report.page', 1);

            return $this->postActionRedirect(
                [
                    'returnUrl'       => $this->generateUrl('mautic_report_index', ['page' => $page]),
                    'viewParameters'  => ['page' => $page],
                    'contentTemplate' => 'Mautic\ReportBundle\Controller\ReportController::indexAction',
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

        $session  = $request->getSession();
        $fromDate = $session->get('mautic.report.date.from', (new \DateTime('-30 days'))->format('Y-m-d'));
        $toDate   = $session->get('mautic.report.date.to', (new \DateTime())->format('Y-m-d'));

        $date    = (new DateTimeHelper())->toLocalString();
        $name    = str_replace(' ', '_', $date).'_'.InputHelper::alphanum($entity->getName(), false, '-');
        $options = ['dateFrom' => new \DateTime($fromDate), 'dateTo' => new \DateTime($toDate)];

        $dynamicFilters            = $session->get('mautic.report.'.$objectId.'.filters', []);
        $options['dynamicFilters'] = $dynamicFilters;

        if ('csv' === $format) {
            $response = new HttpFoundation\StreamedResponse(
                function () use ($model, $entity, $format, $options): void {
                    $options['paginate']        = true;
                    $options['ignoreGraphData'] = true;
                    $options['limit']           = (int) $this->coreParametersHelper->getParameter('report_export_batch_size', 1000);
                    $options['page']            = 1;
                    $handle                     = fopen('php://output', 'r+');
                    $batchTotals                = [];
                    $batchDataSize              = 0;
                    do {
                        $reportData = $model->getReportData($entity, null, $options);

                        // Calculate number of pages only once
                        if (1 === $options['page']) {
                            $totalPages = (int) ceil($reportData['totalResults'] / $options['limit']);
                        }

                        // Build the data rows
                        $isLastBatch      = (isset($totalPages) && $totalPages === $options['page']);
                        $reportDataResult = new ReportDataResult($reportData, $batchTotals, $batchDataSize, $isLastBatch);

                        // Store batch totals and size
                        $batchTotals = $reportDataResult->getTotals();
                        $batchDataSize += $reportDataResult->getDataCount();

                        // Note this so that it's not recalculated on each batch
                        $options['totalResults'] = $reportData['totalResults'];

                        $model->exportResults($format, $entity, $reportDataResult, $handle, $options['page']);
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
            $reportData       = $model->getReportData($entity, null, $options);
            $reportDataResult = new ReportDataResult($reportData);
            $response         = $model->exportResults($format, $entity, $reportDataResult);
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
    public function downloadAction(FileHandler $fileHandler, $reportId, $format = 'csv')
    {
        if ('csv' !== $format) {
            throw new \Exception($this->translator->trans('mautic.format.invalid', ['%format%' => $format, '%validFormats%' => 'csv']));
        }

        /** @var \Mautic\ReportBundle\Model\ReportModel $model */
        $model = $this->getModel('report');

        /** @var \Mautic\ReportBundle\Entity\Report $report */
        $report = $model->getEntity($reportId);

        /** @var \Mautic\CoreBundle\Security\Permissions\CorePermissions $security */
        $security = $this->security;

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
