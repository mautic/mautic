<?php

namespace Mautic\StageBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractFormController;
use Mautic\CoreBundle\Factory\PageHelperFactoryInterface;
use Mautic\StageBundle\Entity\Stage;
use Mautic\StageBundle\Model\StageModel;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class StageController extends AbstractFormController
{
    /**
     * @param int $page
     *
     * @return JsonResponse|Response
     */
    public function indexAction(Request $request, PageHelperFactoryInterface $pageHelperFactory, $page = 1)
    {
        // set some permissions
        $permissions = $this->security->isGranted(
            [
                'stage:stages:view',
                'stage:stages:create',
                'stage:stages:edit',
                'stage:stages:delete',
                'stage:stages:publish',
            ],
            'RETURN_ARRAY'
        );

        if (!$permissions['stage:stages:view']) {
            return $this->accessDenied();
        }

        $this->setListFilters();

        $pageHelper = $pageHelperFactory->make('mautic.stage', $page);

        $limit      = $pageHelper->getLimit();
        $start      = $pageHelper->getStart();
        $search     = $request->get('search', $request->getSession()->get('mautic.stage.filter', ''));
        $filter     = ['string' => $search, 'force' => []];
        $orderBy    = $request->getSession()->get('mautic.stage.orderby', 's.name');
        $orderByDir = $request->getSession()->get('mautic.stage.orderbydir', 'ASC');
        $stageModel = $this->getModel('stage');
        \assert($stageModel instanceof StageModel);
        $stages = $stageModel->getEntities(
            [
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir,
            ]
        );

        $request->getSession()->set('mautic.stage.filter', $search);

        $count = count($stages);
        if ($count && $count < ($start + 1)) {
            $lastPage  = $pageHelper->countPage($count);
            $returnUrl = $this->generateUrl('mautic_stage_index', ['page' => $lastPage]);
            $pageHelper->rememberPage($lastPage);

            return $this->postActionRedirect(
                [
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => ['page' => $lastPage],
                    'contentTemplate' => 'Mautic\StageBundle\Controller\StageController::indexAction',
                    'passthroughVars' => [
                        'activeLink'    => '#mautic_stage_index',
                        'mauticContent' => 'stage',
                    ],
                ]
            );
        }

        $pageHelper->rememberPage($page);

        // get the list of actions
        $actions = $stageModel->getStageActions();

        return $this->delegateView(
            [
                'viewParameters' => [
                    'searchValue' => $search,
                    'items'       => $stages,
                    'actions'     => $actions['actions'],
                    'page'        => $page,
                    'limit'       => $limit,
                    'permissions' => $permissions,
                    'tmpl'        => $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index',
                ],
                'contentTemplate' => '@MauticStage/Stage/list.html.twig',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_stage_index',
                    'mauticContent' => 'stage',
                    'route'         => $this->generateUrl('mautic_stage_index', ['page' => $page]),
                ],
            ]
        );
    }

    /**
     * Generates new form and processes post data.
     *
     * @param \Mautic\StageBundle\Entity\Stage $entity
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function newAction(Request $request, FormFactoryInterface $formFactory, $entity = null)
    {
        $model = $this->getModel('stage');
        \assert($model instanceof StageModel);

        if (!($entity instanceof Stage)) {
            /** @var \Mautic\StageBundle\Entity\Stage $entity */
            $entity = $model->getEntity();
        }

        if (!$this->security->isGranted('stage:stages:create')) {
            return $this->accessDenied();
        }

        // set the page we came from
        $page       = $request->getSession()->get('mautic.stage.page', 1);
        $method     = $request->getMethod();
        $stage      = $request->request->get('stage') ?? [];
        $actionType = 'POST' === $method ? ($stage['type'] ?? '') : '';
        $action     = $this->generateUrl('mautic_stage_action', ['objectAction' => 'new']);
        $actions    = $model->getStageActions();
        $form       = $model->createForm(
            $entity,
            $formFactory,
            $action,
            [
                'stageActions' => $actions,
                'actionType'   => $actionType,
            ]
        );
        $viewParameters = ['page' => $page];

        // /Check for a submitted form and process it
        if (Request::METHOD_POST === $method) {
            $valid = false;

            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    // form is valid so process the data
                    $model->saveEntity($entity);

                    $this->addFlashMessage(
                        'mautic.core.notice.created',
                        [
                            '%name%'      => $entity->getName(),
                            '%menu_link%' => 'mautic_stage_index',
                            '%url%'       => $this->generateUrl(
                                'mautic_stage_action',
                                [
                                    'objectAction' => 'edit',
                                    'objectId'     => $entity->getId(),
                                ]
                            ),
                        ]
                    );

                    if ($this->getFormButton($form, ['buttons', 'save'])->isClicked()) {
                        $returnUrl = $this->generateUrl('mautic_stage_index', $viewParameters);
                        $template  = 'Mautic\StageBundle\Controller\StageController::indexAction';
                    } else {
                        // return edit view so that all the session stuff is loaded
                        return $this->editAction($request, $formFactory, $entity->getId(), true);
                    }
                }
            } else {
                $returnUrl = $this->generateUrl('mautic_stage_index', $viewParameters);
                $template  = 'Mautic\StageBundle\Controller\StageController::indexAction';
            }

            if ($cancelled || ($valid && $this->getFormButton($form, ['buttons', 'save'])->isClicked())) {
                return $this->postActionRedirect(
                    [
                        'returnUrl'       => $returnUrl,
                        'viewParameters'  => $viewParameters,
                        'contentTemplate' => $template,
                        'passthroughVars' => [
                            'activeLink'    => '#mautic_stage_index',
                            'mauticContent' => 'stage',
                        ],
                    ]
                );
            }
        }

        $themes = ['MauticStageBundle:FormTheme\Action'];
        if ($actionType && !empty($actions['actions'][$actionType]['formTheme'])) {
            $themes[] = $actions['actions'][$actionType]['formTheme'];
        }

        return $this->delegateView(
            [
                'viewParameters' => [
                    'tmpl'      => $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index',
                    'entity'    => $entity,
                    'form'      => $form->createView(),
                    'actions'   => $actions['actions'],
                ],
                'contentTemplate' => '@MauticStage/Stage/form.html.twig',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_stage_index',
                    'mauticContent' => 'stage',
                    'route'         => $this->generateUrl(
                        'mautic_stage_action',
                        [
                            'objectAction' => (!empty($valid) ? 'edit' : 'new'), // valid means a new form was applied
                            'objectId'     => $entity->getId(),
                        ]
                    ),
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
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function editAction(Request $request, FormFactoryInterface $formFactory, $objectId, $ignorePost = false)
    {
        $model = $this->getModel('stage');
        \assert($model instanceof StageModel);
        $entity = $model->getEntity($objectId);

        // set the page we came from
        $page = $request->getSession()->get('mautic.stage.page', 1);

        $viewParameters = ['page' => $page];

        // set the return URL
        $returnUrl = $this->generateUrl('mautic_stage_index', ['page' => $page]);

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => $viewParameters,
            'contentTemplate' => 'Mautic\StageBundle\Controller\StageController::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_stage_index',
                'mauticContent' => 'stage',
            ],
        ];

        // form not found
        if (null === $entity) {
            return $this->postActionRedirect(
                array_merge(
                    $postActionVars,
                    [
                        'flashes' => [
                            [
                                'type'    => 'error',
                                'msg'     => 'mautic.stage.error.notfound',
                                'msgVars' => ['%id%' => $objectId],
                            ],
                        ],
                    ]
                )
            );
        } elseif (!$this->security->isGranted('stage:stages:edit')) {
            return $this->accessDenied();
        } elseif ($model->isLocked($entity)) {
            // deny access if the entity is locked
            return $this->isLocked($postActionVars, $entity, 'stage');
        }

        $actionType = 'moved to stage';

        $action  = $this->generateUrl('mautic_stage_action', ['objectAction' => 'edit', 'objectId' => $objectId]);
        $actions = $model->getStageActions();
        $form    = $model->createForm(
            $entity,
            $formFactory,
            $action,
            [
                'stageActions' => $actions,
                'actionType'   => $actionType,
            ]
        );

        // /Check for a submitted form and process it
        if (!$ignorePost && 'POST' == $request->getMethod()) {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    // form is valid so process the data
                    $model->saveEntity($entity, $this->getFormButton($form, ['buttons', 'save'])->isClicked());

                    $this->addFlashMessage(
                        'mautic.core.notice.updated',
                        [
                            '%name%'      => $entity->getName(),
                            '%menu_link%' => 'mautic_stage_index',
                            '%url%'       => $this->generateUrl(
                                'mautic_stage_action',
                                [
                                    'objectAction' => 'edit',
                                    'objectId'     => $entity->getId(),
                                ]
                            ),
                        ]
                    );

                    if ($this->getFormButton($form, ['buttons', 'save'])->isClicked()) {
                        $returnUrl = $this->generateUrl('mautic_stage_index', $viewParameters);
                        $template  = 'Mautic\StageBundle\Controller\StageController::indexAction';
                    }
                }
            } else {
                // unlock the entity
                $model->unlockEntity($entity);

                $returnUrl = $this->generateUrl('mautic_stage_index', $viewParameters);
                $template  = 'Mautic\StageBundle\Controller\StageController::indexAction';
            }

            if ($cancelled || ($valid && $this->getFormButton($form, ['buttons', 'save'])->isClicked())) {
                return $this->postActionRedirect(
                    array_merge(
                        $postActionVars,
                        [
                            'returnUrl'       => $returnUrl,
                            'viewParameters'  => $viewParameters,
                            'contentTemplate' => $template,
                        ]
                    )
                );
            }
        } else {
            // lock the entity
            $model->lockEntity($entity);
        }

        $themes = ['MauticStageBundle:FormTheme\Action'];
        if (!empty($actions['actions'][$actionType]['formTheme'])) {
            $themes[] = $actions['actions'][$actionType]['formTheme'];
        }

        return $this->delegateView(
            [
                'viewParameters' => [
                    'tmpl'    => $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index',
                    'entity'  => $entity,
                    'form'    => $form->createView(),
                    'actions' => $actions['actions'],
                ],
                'contentTemplate' => '@MauticStage/Stage/form.html.twig',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_stage_index',
                    'mauticContent' => 'stage',
                    'route'         => $this->generateUrl(
                        'mautic_stage_action',
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
     * @param int $objectId
     *
     * @return array|JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function cloneAction(Request $request, FormFactoryInterface $formFactory, $objectId)
    {
        $model  = $this->getModel('stage');
        $entity = $model->getEntity($objectId);

        if (null != $entity) {
            if (!$this->security->isGranted('stage:stages:create')) {
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
        $page      = $request->getSession()->get('mautic.stage.page', 1);
        $returnUrl = $this->generateUrl('mautic_stage_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\StageBundle\Controller\StageController::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_stage_index',
                'mauticContent' => 'stage',
            ],
        ];

        if (Request::METHOD_POST === $request->getMethod()) {
            $model = $this->getModel('stage');
            \assert($model instanceof StageModel);
            $entity = $model->getEntity($objectId);

            if (null === $entity) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.stage.error.notfound',
                    'msgVars' => ['%id%' => $objectId],
                ];
            } elseif (!$this->security->isGranted('stage:stages:delete')) {
                return $this->accessDenied();
            } elseif ($model->isLocked($entity)) {
                return $this->isLocked($postActionVars, $entity, 'stage');
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
     * @return Response
     */
    public function batchDeleteAction(Request $request)
    {
        $page      = $request->getSession()->get('mautic.stage.page', 1);
        $returnUrl = $this->generateUrl('mautic_stage_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\StageBundle\Controller\StageController::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_stage_index',
                'mauticContent' => 'stage',
            ],
        ];

        if (Request::METHOD_POST === $request->getMethod()) {
            $model = $this->getModel('stage');
            \assert($model instanceof StageModel);
            $ids       = json_decode($request->query->get('ids', '{}'));
            $deleteIds = [];

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $model->getEntity($objectId);

                if (null === $entity) {
                    $flashes[] = [
                        'type'    => 'error',
                        'msg'     => 'mautic.stage.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ];
                } elseif (!$this->security->isGranted('stage:stages:delete')) {
                    $flashes[] = $this->accessDenied(true);
                } elseif ($model->isLocked($entity)) {
                    $flashes[] = $this->isLocked($postActionVars, $entity, 'stage', true);
                } else {
                    $deleteIds[] = $objectId;
                }
            }

            // Delete everything we are able to
            if (!empty($deleteIds)) {
                $entities = $model->deleteEntities($deleteIds);

                $flashes[] = [
                    'type'    => 'notice',
                    'msg'     => 'mautic.stage.notice.batch_deleted',
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
}
