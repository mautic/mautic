<?php

namespace Mautic\PointBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractFormController;
use Mautic\CoreBundle\Factory\PageHelperFactoryInterface;
use Mautic\PointBundle\Entity\Point;
use Mautic\PointBundle\Model\PointModel;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PointController extends AbstractFormController
{
    /**
     * @param int $page
     *
     * @return JsonResponse|Response
     */
    public function indexAction(Request $request, PageHelperFactoryInterface $pageHelperFactory, $page = 1)
    {
        // set some permissions
        $permissions = $this->security->isGranted([
            'point:points:view',
            'point:points:create',
            'point:points:edit',
            'point:points:delete',
            'point:points:publish',
        ], 'RETURN_ARRAY');

        if (!$permissions['point:points:view']) {
            return $this->accessDenied();
        }

        $this->setListFilters();

        $pageHelper = $pageHelperFactory->make('mautic.point', $page);

        $limit      = $pageHelper->getLimit();
        $start      = $pageHelper->getStart();
        $search     = $request->get('search', $request->getSession()->get('mautic.point.filter', ''));
        $filter     = ['string' => $search, 'force' => []];
        $orderBy    = $request->getSession()->get('mautic.point.orderby', 'p.name');
        $orderByDir = $request->getSession()->get('mautic.point.orderbydir', 'ASC');
        $pointModel = $this->getModel('point');
        \assert($pointModel instanceof PointModel);
        $points     = $pointModel->getEntities([
            'start'      => $start,
            'limit'      => $limit,
            'filter'     => $filter,
            'orderBy'    => $orderBy,
            'orderByDir' => $orderByDir,
        ]);

        $request->getSession()->set('mautic.point.filter', $search);

        $count = count($points);
        if ($count && $count < ($start + 1)) {
            $lastPage  = $pageHelper->countPage($count);
            $returnUrl = $this->generateUrl('mautic_point_index', ['page' => $lastPage]);
            $pageHelper->rememberPage($lastPage);

            return $this->postActionRedirect([
                'returnUrl'       => $returnUrl,
                'viewParameters'  => ['page' => $lastPage],
                'contentTemplate' => 'Mautic\PointBundle\Controller\PointController::indexAction',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_point_index',
                    'mauticContent' => 'point',
                ],
            ]);
        }

        $pageHelper->rememberPage($page);

        // get the list of actions
        $actions = $pointModel->getPointActions();

        return $this->delegateView([
            'viewParameters' => [
                'searchValue' => $search,
                'items'       => $points,
                'actions'     => $actions['actions'],
                'page'        => $page,
                'limit'       => $limit,
                'permissions' => $permissions,
                'tmpl'        => $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index',
            ],
            'contentTemplate' => '@MauticPoint/Point/list.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_point_index',
                'mauticContent' => 'point',
                'route'         => $this->generateUrl('mautic_point_index', ['page' => $page]),
            ],
        ]);
    }

    /**
     * Generates new form and processes post data.
     *
     * @param \Mautic\PointBundle\Entity\Point $entity
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function newAction(Request $request, FormFactoryInterface $formFactory, $entity = null)
    {
        $model = $this->getModel('point');
        \assert($model instanceof PointModel);

        if (!($entity instanceof Point)) {
            /** @var \Mautic\PointBundle\Entity\Point $entity */
            $entity = $model->getEntity();
        }

        if (!$this->security->isGranted('point:points:create')) {
            return $this->accessDenied();
        }

        // set the page we came from
        $page       = $request->getSession()->get('mautic.point.page', 1);
        $method     = $request->getMethod();
        $point      = $request->request->get('point') ?? [];
        $actionType = 'POST' === $method ? ($point['type'] ?? '') : '';
        $action     = $this->generateUrl('mautic_point_action', ['objectAction' => 'new']);
        $actions    = $model->getPointActions();
        $form       = $model->createForm($entity, $formFactory, $action, [
            'pointActions' => $actions,
            'actionType'   => $actionType,
        ]);
        $viewParameters = ['page' => $page];

        // /Check for a submitted form and process it
        if (Request::METHOD_POST === $method) {
            $valid = false;

            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    // form is valid so process the data
                    $model->saveEntity($entity);

                    $this->addFlashMessage('mautic.core.notice.created', [
                        '%name%'      => $entity->getName(),
                        '%menu_link%' => 'mautic_point_index',
                        '%url%'       => $this->generateUrl('mautic_point_action', [
                            'objectAction' => 'edit',
                            'objectId'     => $entity->getId(),
                        ]),
                    ]);

                    if ($this->getFormButton($form, ['buttons', 'save'])->isClicked()) {
                        $returnUrl = $this->generateUrl('mautic_point_index', $viewParameters);
                        $template  = 'Mautic\PointBundle\Controller\PointController::indexAction';
                    } else {
                        // return edit view so that all the session stuff is loaded
                        return $this->editAction($request, $formFactory, $entity->getId(), true);
                    }
                }
            } else {
                $returnUrl = $this->generateUrl('mautic_point_index', $viewParameters);
                $template  = 'Mautic\PointBundle\Controller\PointController::indexAction';
            }

            if ($cancelled || ($valid && $this->getFormButton($form, ['buttons', 'save'])->isClicked())) {
                return $this->postActionRedirect([
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => $viewParameters,
                    'contentTemplate' => $template,
                    'passthroughVars' => [
                        'activeLink'    => '#mautic_point_index',
                        'mauticContent' => 'point',
                    ],
                ]);
            }
        }

        $themes = ['@MauticPoint/FormTheme/Action/_pointaction_properties_row.html.twig'];
        if ($actionType && !empty($actions['actions'][$actionType]['formTheme'])) {
            $themes[] = $actions['actions'][$actionType]['formTheme'];
        }

        return $this->delegateView([
            'viewParameters' => [
                'tmpl'       => $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index',
                'entity'     => $entity,
                'form'       => $form->createView(),
                'actions'    => $actions['actions'],
                'formThemes' => $themes,
            ],
            'contentTemplate' => '@MauticPoint/Point/form.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_point_index',
                'mauticContent' => 'point',
                'route'         => $this->generateUrl('mautic_point_action', [
                        'objectAction' => (!empty($valid) ? 'edit' : 'new'), // valid means a new form was applied
                        'objectId'     => $entity->getId(),
                    ]
                ),
            ],
        ]);
    }

    /**
     * Generates edit form and processes post data.
     *
     * @param int  $objectId
     * @param bool $ignorePost
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function editAction(Request $request, FormFactoryInterface $formFactory, $objectId, $ignorePost = false)
    {
        $model = $this->getModel('point');
        \assert($model instanceof PointModel);
        $entity = $model->getEntity($objectId);

        // set the page we came from
        $page = $request->getSession()->get('mautic.point.page', 1);

        $viewParameters = ['page' => $page];

        // set the return URL
        $returnUrl = $this->generateUrl('mautic_point_index', ['page' => $page]);

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => $viewParameters,
            'contentTemplate' => 'Mautic\PointBundle\Controller\PointController::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_point_index',
                'mauticContent' => 'point',
            ],
        ];

        // form not found
        if (null === $entity) {
            return $this->postActionRedirect(
                array_merge($postActionVars, [
                    'flashes' => [
                        [
                            'type'    => 'error',
                            'msg'     => 'mautic.point.error.notfound',
                            'msgVars' => ['%id%' => $objectId],
                        ],
                    ],
                ])
            );
        } elseif (!$this->security->isGranted('point:points:edit')) {
            return $this->accessDenied();
        } elseif ($model->isLocked($entity)) {
            // deny access if the entity is locked
            return $this->isLocked($postActionVars, $entity, 'point');
        }

        $method     = $request->getMethod();
        $point      = $request->request->get('point') ?? [];
        $actionType = 'POST' === $method ? ($point['type'] ?? '') : $entity->getType();

        $action  = $this->generateUrl('mautic_point_action', ['objectAction' => 'edit', 'objectId' => $objectId]);
        $actions = $model->getPointActions();
        $form    = $model->createForm($entity, $formFactory, $action, [
            'pointActions' => $actions,
            'actionType'   => $actionType,
        ]);

        // /Check for a submitted form and process it
        if (!$ignorePost && 'POST' === $method) {
            $valid = false;

            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    // form is valid so process the data
                    $model->saveEntity($entity, $this->getFormButton($form, ['buttons', 'save'])->isClicked());

                    $this->addFlashMessage('mautic.core.notice.updated', [
                        '%name%'      => $entity->getName(),
                        '%menu_link%' => 'mautic_point_index',
                        '%url%'       => $this->generateUrl('mautic_point_action', [
                            'objectAction' => 'edit',
                            'objectId'     => $entity->getId(),
                        ]),
                    ]);

                    if ($this->getFormButton($form, ['buttons', 'save'])->isClicked()) {
                        $returnUrl = $this->generateUrl('mautic_point_index', $viewParameters);
                        $template  = 'Mautic\PointBundle\Controller\PointController::indexAction';
                    }
                }
            } else {
                // unlock the entity
                $model->unlockEntity($entity);

                $returnUrl = $this->generateUrl('mautic_point_index', $viewParameters);
                $template  = 'Mautic\PointBundle\Controller\PointController::indexAction';
            }

            if ($cancelled || ($valid && $this->getFormButton($form, ['buttons', 'save'])->isClicked())) {
                return $this->postActionRedirect(
                    array_merge($postActionVars, [
                        'returnUrl'       => $returnUrl,
                        'viewParameters'  => $viewParameters,
                        'contentTemplate' => $template,
                    ])
                );
            }
        } else {
            // lock the entity
            $model->lockEntity($entity);
        }

        $themes = ['@MauticPoint/FormTheme/Action/_pointaction_properties_row.html.twig'];
        if (!empty($actions['actions'][$actionType]['formTheme'])) {
            $themes[] = $actions['actions'][$actionType]['formTheme'];
        }

        return $this->delegateView([
            'viewParameters' => [
                'tmpl'       => $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index',
                'entity'     => $entity,
                'form'       => $form->createView(),
                'actions'    => $actions['actions'],
                'formThemes' => $themes,
            ],
            'contentTemplate' => '@MauticPoint/Point/form.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_point_index',
                'mauticContent' => 'point',
                'route'         => $this->generateUrl('mautic_point_action', [
                        'objectAction' => 'edit',
                        'objectId'     => $entity->getId(),
                    ]
                ),
            ],
        ]);
    }

    /**
     * Clone an entity.
     *
     * @param int $objectId
     *
     * @return array|JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function cloneAction(Request $request, FormFactoryInterface $formFactory, $objectId)
    {
        $model  = $this->getModel('point');
        $entity = $model->getEntity($objectId);

        if (null != $entity) {
            if (!$this->security->isGranted('point:points:create')) {
                return $this->accessDenied();
            }

            $entity = clone $entity;
            $entity->setIsPublished(false);
        }

        return $this->newAction($request, $formFactory, $entity);
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
        $page      = $request->getSession()->get('mautic.point.page', 1);
        $returnUrl = $this->generateUrl('mautic_point_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\PointBundle\Controller\PointController::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_point_index',
                'mauticContent' => 'point',
            ],
        ];

        if (Request::METHOD_POST === $request->getMethod()) {
            $model = $this->getModel('point');
            \assert($model instanceof PointModel);
            $entity = $model->getEntity($objectId);

            if (null === $entity) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.point.error.notfound',
                    'msgVars' => ['%id%' => $objectId],
                ];
            } elseif (!$this->security->isGranted('point:points:delete')) {
                return $this->accessDenied();
            } elseif ($model->isLocked($entity)) {
                return $this->isLocked($postActionVars, $entity, 'point');
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
            array_merge($postActionVars, [
                'flashes' => $flashes,
            ])
        );
    }

    /**
     * Deletes a group of entities.
     *
     * @return Response
     */
    public function batchDeleteAction(Request $request)
    {
        $page      = $request->getSession()->get('mautic.point.page', 1);
        $returnUrl = $this->generateUrl('mautic_point_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\PointBundle\Controller\PointController::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_point_index',
                'mauticContent' => 'point',
            ],
        ];

        if (Request::METHOD_POST === $request->getMethod()) {
            $model = $this->getModel('point');
            \assert($model instanceof PointModel);
            $ids       = json_decode($request->query->get('ids', '{}'));
            $deleteIds = [];

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $model->getEntity($objectId);

                if (null === $entity) {
                    $flashes[] = [
                        'type'    => 'error',
                        'msg'     => 'mautic.point.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ];
                } elseif (!$this->security->isGranted('point:points:delete')) {
                    $flashes[] = $this->accessDenied(true);
                } elseif ($model->isLocked($entity)) {
                    $flashes[] = $this->isLocked($postActionVars, $entity, 'point', true);
                } else {
                    $deleteIds[] = $objectId;
                }
            }

            // Delete everything we are able to
            if (!empty($deleteIds)) {
                $entities = $model->deleteEntities($deleteIds);

                $flashes[] = [
                    'type'    => 'notice',
                    'msg'     => 'mautic.point.notice.batch_deleted',
                    'msgVars' => [
                        '%count%' => count($entities),
                    ],
                ];
            }
        } // else don't do anything

        return $this->postActionRedirect(
            array_merge($postActionVars, [
                'flashes' => $flashes,
            ])
        );
    }
}
