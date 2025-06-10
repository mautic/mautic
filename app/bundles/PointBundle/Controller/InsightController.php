<?php

namespace Mautic\PointBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Factory\PageHelperFactoryInterface;
use Mautic\PointBundle\Entity\PointInsight;
use Mautic\PointBundle\Model\InsightModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class InsightController extends FormController
{
    public function indexAction(Request $request, PageHelperFactoryInterface $pageHelperFactory, $page = 1): Response
    {
        if (!$this->security->isGranted('point:points:view')) {
            return $this->accessDenied();
        }

        $this->setListFilters();

        $pageHelper = $pageHelperFactory->make('mautic_point.insights', $page);

        $limit      = $pageHelper->getLimit();
        $start      = $pageHelper->getStart();
        $search     = $request->get('search', $request->getSession()->get('mautic.point.insight.filter', ''));
        $filter     = ['string' => $search, 'force' => []];
        $orderBy    = $request->getSession()->get('mautic.point.insight.orderby', 'pi.name');
        $orderByDir = $request->getSession()->get('mautic.point.insight.orderbydir', 'ASC');
        $insights   = $this->getModel('point.insight')->getEntities(
            [
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir,
            ]
        );

        $request->getSession()->set('mautic.point.insight.filter', $search);

        $count = count($insights);
        if ($count && $count < ($start + 1)) {
            $lastPage  = $pageHelper->countPage($count);
            $returnUrl = $this->generateUrl('mautic_point.insights_index', ['page' => $lastPage]);
            $pageHelper->rememberPage($lastPage);

            return $this->postActionRedirect([
                'returnUrl'       => $returnUrl,
                'viewParameters'  => ['page' => $lastPage],
                'contentTemplate' => 'Mautic\PointBundle\Controller\InsightController::indexAction',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_point.insights_index',
                    'mauticContent' => 'pointInsight',
                ],
            ]);
        }

        $pageHelper->rememberPage($page);

        // Set permissions for action buttons
        $permissions = $this->security->isGranted([
            'point:points:view',
            'point:points:create',
            'point:points:edit',
            'point:points:delete',
            'point:points:publish',
        ], 'RETURN_ARRAY');

        return $this->delegateView([
            'viewParameters' => [
                'searchValue' => $search,
                'items'       => $insights,
                'page'        => $page,
                'limit'       => $limit,
                'permissions' => $permissions,
                'tmpl'        => $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index',
                'currentRoute'=> $this->generateUrl('mautic_point.insights_index', ['page' => $page]),
            ],
            'contentTemplate' => '@MauticPoint/Insight/list.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_point.insights_index',
                'mauticContent' => 'pointInsight',
                'route'         => $this->generateUrl('mautic_point.insights_index', ['page' => $page]),
            ],
        ]);
    }

    public function newAction(Request $request): Response
    {
        /** @var InsightModel $model */
        $model = $this->getModel('point.insight');

        if (!$this->security->isGranted('point:points:create')) {
            return $this->accessDenied();
        }

        /** @var PointInsight $entity */
        $entity = $model->getEntity();

        // Set the page we came from
        $page = $request->getSession()->get('mautic.point.insight.page', 1);

        $action = $this->generateUrl('mautic_point.insights_action', ['objectAction' => 'new']);
        $form   = $model->createForm($entity, $this->formFactory, $action);

        // Check for a submitted form and process it
        if ('POST' == $request->getMethod()) {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    // Form is valid so process the data
                    $model->saveEntity($entity);

                    $this->addFlashMessage(
                        'mautic.core.notice.created',
                        [
                            '%name%'      => $entity->getName(),
                            '%menu_link%' => 'mautic_point.insights_index',
                            '%url%'       => $this->generateUrl('mautic_point.insights_action', [
                                'objectAction' => 'edit',
                                'objectId'     => $entity->getId(),
                            ]),
                        ]
                    );

                    if ($form->get('buttons')->get('save')->isClicked()) {
                        return $this->postActionRedirect([
                            'returnUrl'       => $this->generateUrl('mautic_point.insights_index', ['page' => $page]),
                            'viewParameters'  => ['page' => $page],
                            'contentTemplate' => 'Mautic\PointBundle\Controller\InsightController::indexAction',
                            'passthroughVars' => [
                                'activeLink'    => '#mautic_point.insights_index',
                                'mauticContent' => 'pointInsight',
                            ],
                        ]);
                    }

                    if ($form->get('buttons')->get('apply')->isClicked()) {
                        return $this->postActionRedirect([
                            'returnUrl'       => $this->generateUrl('mautic_point.insights_action', [
                                'objectAction' => 'edit',
                                'objectId'     => $entity->getId(),
                            ]),
                            'viewParameters'  => [
                                'objectAction' => 'edit',
                                'objectId'     => $entity->getId(),
                            ],
                            'contentTemplate' => 'Mautic\PointBundle\Controller\InsightController::editAction',
                            'passthroughVars' => [
                                'activeLink'    => '#mautic_point.insights_index',
                                'mauticContent' => 'pointInsight',
                            ],
                        ]);
                    }

                    return $this->postActionRedirect([
                        'returnUrl'       => $this->generateUrl('mautic_point.insights_action', [
                            'objectAction' => 'new',
                        ]),
                        'viewParameters'  => [
                            'objectAction' => 'new',
                        ],
                        'contentTemplate' => 'Mautic\PointBundle\Controller\InsightController::newAction',
                        'passthroughVars' => [
                            'activeLink'    => '#mautic_point.insights_index',
                            'mauticContent' => 'pointInsight',
                        ],
                    ]);
                }
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                return $this->postActionRedirect([
                    'returnUrl'       => $this->generateUrl('mautic_point.insights_index', ['page' => $page]),
                    'viewParameters'  => ['page' => $page],
                    'contentTemplate' => 'Mautic\PointBundle\Controller\InsightController::indexAction',
                    'passthroughVars' => [
                        'activeLink'    => '#mautic_point.insights_index',
                        'mauticContent' => 'pointInsight',
                    ],
                ]);
            }
        }

        return $this->delegateView([
            'viewParameters' => [
                'form' => $form->createView(),
                'entity' => $entity,
            ],
            'contentTemplate' => '@MauticPoint/Insight/form.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_point.insights_index',
                'mauticContent' => 'pointInsight',
                'route'         => $this->generateUrl('mautic_point.insights_action', [
                    'objectAction' => 'new',
                ]),
            ],
        ]);
    }

    /**
     * Edits an existing Point Insight.
     */
    public function editAction(Request $request, $objectId): Response
    {
        /** @var InsightModel $model */
        $model  = $this->getModel('point.insight');
        $entity = $model->getEntity($objectId);

        // Set the page we came from
        $page = $request->getSession()->get('mautic.point.insight.page', 1);

        // Set the return URL
        $returnUrl = $this->generateUrl('mautic_point.insights_index', ['page' => $page]);

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\PointBundle\Controller\InsightController::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_point.insights_index',
                'mauticContent' => 'pointInsight',
            ],
        ];

        // Form not found
        if (null === $entity) {
            return $this->postActionRedirect(
                array_merge($postActionVars, [
                    'flashes' => [
                        [
                            'type'    => 'error',
                            'msg'     => 'mautic.point.insight.error.notfound',
                            'msgVars' => ['%id%' => $objectId],
                        ],
                    ],
                ])
            );
        }

        // Access denied
        if (!$this->security->isGranted('point:points:edit')) {
            return $this->accessDenied();
        }

        $action = $this->generateUrl('mautic_point.insights_action', ['objectAction' => 'edit', 'objectId' => $objectId]);
        $form   = $model->createForm($entity, $this->formFactory, $action);

        // Check for a submitted form and process it
        if ('POST' == $request->getMethod()) {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    // Form is valid so process the data
                    $model->saveEntity($entity);

                    $this->addFlashMessage(
                        'mautic.core.notice.updated',
                        [
                            '%name%'      => $entity->getName(),
                            '%menu_link%' => 'mautic_point.insights_index',
                            '%url%'       => $this->generateUrl('mautic_point.insights_action', [
                                'objectAction' => 'edit',
                                'objectId'     => $entity->getId(),
                            ]),
                        ]
                    );

                    if ($form->get('buttons')->get('save')->isClicked()) {
                        return $this->postActionRedirect($postActionVars);
                    }
                }
            } else {
                // Cancelled
                return $this->postActionRedirect($postActionVars);
            }

            if ($valid) {
                // Rebuild the form to prevent form state from being lost as the form's been submitted
                $action = $this->generateUrl('mautic_point.insights_action', ['objectAction' => 'edit', 'objectId' => $objectId]);
                $form   = $model->createForm($entity, $this->formFactory, $action);
            }
        }

        return $this->delegateView([
            'viewParameters' => [
                'form' => $form->createView(),
                'entity' => $entity,
            ],
            'contentTemplate' => '@MauticPoint/Insight/form.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_point.insights_index',
                'mauticContent' => 'pointInsight',
                'route'         => $this->generateUrl('mautic_point.insights_action', [
                    'objectAction' => 'edit',
                    'objectId'     => $entity->getId(),
                ]),
            ],
        ]);
    }

    /**
     * Clones an existing Point Insight.
     */
    public function cloneAction(Request $request, $objectId): Response
    {
        /** @var InsightModel $model */
        $model  = $this->getModel('point.insight');
        $entity = $model->getEntity($objectId);

        // Set the page we came from
        $page = $request->getSession()->get('mautic.point.insight.page', 1);

        $postActionVars = [
            'returnUrl'       => $this->generateUrl('mautic_point.insights_index', ['page' => $page]),
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\PointBundle\Controller\InsightController::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_point.insights_index',
                'mauticContent' => 'pointInsight',
            ],
        ];

        if (null === $entity) {
            return $this->postActionRedirect(
                array_merge($postActionVars, [
                    'flashes' => [
                        [
                            'type'    => 'error',
                            'msg'     => 'mautic.point.insight.error.notfound',
                            'msgVars' => ['%id%' => $objectId],
                        ],
                    ],
                ])
            );
        }

        if (!$this->security->isGranted('point:points:create')) {
            return $this->accessDenied();
        }

        // Create a clone of the entity
        $clone = clone $entity;
        $clone->setName($entity->getName());

        $action = $this->generateUrl('mautic_point.insights_action', ['objectAction' => 'clone', 'objectId' => $objectId]);
        $form   = $model->createForm($clone, $this->formFactory, $action);

        // Check for a submitted form and process it
        if ('POST' == $request->getMethod()) {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    // Form is valid so process the data
                    $model->saveEntity($clone);

                    $this->addFlashMessage(
                        'mautic.core.notice.created',
                        [
                            '%name%'      => $clone->getName(),
                            '%menu_link%' => 'mautic_point.insights_index',
                            '%url%'       => $this->generateUrl('mautic_point.insights_action', [
                                'objectAction' => 'edit',
                                'objectId'     => $clone->getId(),
                            ]),
                        ]
                    );

                    if ($form->get('buttons')->get('save')->isClicked()) {
                        return $this->postActionRedirect($postActionVars);
                    }

                    if ($form->get('buttons')->get('apply')->isClicked()) {
                        return $this->postActionRedirect([
                            'returnUrl'       => $this->generateUrl('mautic_point.insights_action', [
                                'objectAction' => 'edit',
                                'objectId'     => $clone->getId(),
                            ]),
                            'viewParameters'  => [
                                'objectAction' => 'edit',
                                'objectId'     => $clone->getId(),
                            ],
                            'contentTemplate' => 'Mautic\PointBundle\Controller\InsightController::editAction',
                            'passthroughVars' => [
                                'activeLink'    => '#mautic_point.insights_index',
                                'mauticContent' => 'pointInsight',
                            ],
                        ]);
                    }

                    return $this->postActionRedirect([
                        'returnUrl'       => $this->generateUrl('mautic_point.insights_action', [
                            'objectAction' => 'new',
                        ]),
                        'viewParameters'  => [
                            'objectAction' => 'new',
                        ],
                        'contentTemplate' => 'Mautic\PointBundle\Controller\InsightController::newAction',
                        'passthroughVars' => [
                            'activeLink'    => '#mautic_point.insights_index',
                            'mauticContent' => 'pointInsight',
                        ],
                    ]);
                }
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                return $this->postActionRedirect($postActionVars);
            }
        }

        return $this->delegateView([
            'viewParameters' => [
                'form' => $form->createView(),
                'entity' => $clone,
            ],
            'contentTemplate' => '@MauticPoint/Insight/form.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_point.insights_index',
                'mauticContent' => 'pointInsight',
                'route'         => $this->generateUrl('mautic_point.insights_action', [
                    'objectAction' => 'clone',
                    'objectId'     => $objectId,
                ]),
            ],
        ]);
    }


    /**
     * Deletes a Point Insight.
     */
    public function deleteAction(Request $request, $objectId): Response
    {
        $page      = $request->getSession()->get('mautic.point.insight.page', 1);
        $returnUrl = $this->generateUrl('mautic_point.insights_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\PointBundle\Controller\InsightController::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_point.insights_index',
                'mauticContent' => 'pointInsight',
            ],
        ];

        if ('POST' == $request->getMethod()) {
            /** @var InsightModel $model */
            $model  = $this->getModel('point.insight');
            $entity = $model->getEntity($objectId);

            if (null === $entity) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.point.insight.error.notfound',
                    'msgVars' => ['%id%' => $objectId],
                ];
            } elseif (!$this->security->isGranted('point:points:delete')) {
                return $this->accessDenied();
            } else {
                $model->deleteEntity($entity);

                $flashes[] = [
                    'type'    => 'notice',
                    'msg'     => 'mautic.core.notice.deleted',
                    'msgVars' => [
                        '%name%' => $entity->getName(),
                        '%id%'   => $objectId,
                    ],
                ];
            }
        }

        if (!empty($flashes)) {
            $postActionVars['flashes'] = $flashes;
        }

        return $this->postActionRedirect($postActionVars);
    }
}
