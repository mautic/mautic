<?php

namespace Mautic\StageBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractFormController;
use Mautic\CoreBundle\Factory\PageHelperFactoryInterface;
use Mautic\StageBundle\Entity\Stage;
use Mautic\StageBundle\Entity\StageRepository;
use Mautic\StageBundle\Form\Type\StageMergeType;
use Mautic\StageBundle\Model\StageModel;
use Mautic\StageBundle\Security\Permissions\StagePermissions;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\Attribute\Required;

final class StageController extends AbstractFormController
{
    private StageRepository $stageRepository;

    private StageModel $stageModel;

    #[Required]
    public function autowireStageController(
        StageModel $stageModel,
        StageRepository $stageRepository,
    ): void {
        $this->stageModel = $stageModel;
        $this->stageRepository = $stageRepository;
    }

    public function indexAction(Request $request, PageHelperFactoryInterface $pageHelperFactory, int $page = 1): Response
    {
        // set some permissions
        $permissions = $this->security->isGranted(
            [
                StagePermissions::PERMISSION_VIEW,
                StagePermissions::PERMISSION_CREATE,
                StagePermissions::PERMISSION_EDIT,
                StagePermissions::PERMISSION_DELETE,
                StagePermissions::PERMISSION_PUBLISH,
            ],
            'RETURN_ARRAY'
        );

        if (!$permissions[StagePermissions::PERMISSION_VIEW]) {
            $this->throwAccessDenied();
        }

        $this->setListFilters();

        $pageHelper = $pageHelperFactory->make('mautic.stage', $page);

        $limit      = $pageHelper->getLimit();
        $start      = $pageHelper->getStart();
        $search     = $request->get('search', $request->getSession()->get('mautic.stage.filter', ''));
        $filter     = ['string' => $search, 'force' => []];
        $orderBy    = $request->getSession()->get('mautic.stage.orderby', 's.name');
        $orderByDir = $request->getSession()->get('mautic.stage.orderbydir', 'ASC');
        $stages = $this->stageModel->getEntities(
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
        $actions = $this->stageModel->getStageActions();

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
     * @param Stage $entity
     */
    public function newAction(Request $request, $entity = null): Response
    {
        if (!$entity instanceof Stage) {
            /** @var Stage $entity */
            $entity = $this->stageModel->getEntity();
        }

        if (!$this->security->isGranted(StagePermissions::PERMISSION_CREATE)) {
            $this->throwAccessDenied();
        }

        // set the page we came from
        $page       = $request->getSession()->get('mautic.stage.page', 1);
        $method     = $request->getMethod();
        $stage      = $request->request->all()['stage'] ?? [];
        $actionType = 'POST' === $method ? ($stage['type'] ?? '') : '';
        $action     = $this->generateUrl('mautic_stage_action', ['objectAction' => 'new']);
        $actions    = $this->stageModel->getStageActions();
        $form       = $this->stageModel->createForm(
            $entity,
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
                    $this->stageModel->saveEntity($entity);

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
                        return $this->editAction($request, $entity->getId(), true);
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

        $stageWeights = $this->stageRepository->getStageWeights();

        return $this->delegateView(
            [
                'viewParameters' => [
                    'tmpl'         => $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index',
                    'entity'       => $entity,
                    'form'         => $form->createView(),
                    'actions'      => $actions['actions'],
                    'stageWeights' => $stageWeights,
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
     * @param int $objectId
     */
    public function editAction(Request $request, $objectId, bool $ignorePost = false): Response
    {
        $entity = $this->stageModel->getEntity($objectId);

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
        }
        if (!$this->security->isGranted(StagePermissions::PERMISSION_EDIT)) {
            $this->throwAccessDenied();
        } elseif ($this->stageModel->isLocked($entity)) {
            // deny access if the entity is locked
            return $this->isLocked($postActionVars, $entity, 'stage');
        }

        $actionType = 'moved to stage';

        $action  = $this->generateUrl('mautic_stage_action', ['objectAction' => 'edit', 'objectId' => $objectId]);
        $actions = $this->stageModel->getStageActions();
        $form    = $this->stageModel->createForm(
            $entity,
            $action,
            [
                'stageActions' => $actions,
                'actionType'   => $actionType,
            ]
        );

        // /Check for a submitted form and process it
        if (!$ignorePost && Request::METHOD_POST === $request->getMethod()) {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    // form is valid so process the data
                    $this->stageModel->saveEntity($entity, $this->getFormButton($form, ['buttons', 'save'])->isClicked());

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
                $this->stageModel->unlockEntity($entity);

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
            $this->stageModel->lockEntity($entity);
        }

        $themes = ['MauticStageBundle:FormTheme\Action'];
        if (!empty($actions['actions'][$actionType]['formTheme'])) {
            $themes[] = $actions['actions'][$actionType]['formTheme'];
        }

        $stageWeights = $this->stageRepository->getStageWeights();

        return $this->delegateView(
            [
                'viewParameters' => [
                    'tmpl'         => $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index',
                    'entity'       => $entity,
                    'form'         => $form->createView(),
                    'actions'      => $actions['actions'],
                    'stageWeights' => $stageWeights,
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
     */
    public function cloneAction(Request $request, $objectId): Response
    {
        $entity = $this->stageModel->getEntity($objectId);

        if (null !== $entity) {
            if (!$this->security->isGranted(StagePermissions::PERMISSION_CREATE)) {
                $this->throwAccessDenied();
            }

            $entity = clone $entity;
            $entity->setIsPublished(false);
        }

        return $this->newAction($request, $entity);
    }

    public function mergeAction(Request $request, StageModel $model, int $objectId): Response
    {
        $secondaryStage = $model->getEntity($objectId);
        $page           = $request->getSession()->get('mautic.stage.page', 1);

        $returnUrl      = $this->generateUrl('mautic_stage_index', ['page' => $page]);
        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\\StageBundle\\Controller\\StageController::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_stage_index',
                'mauticContent' => 'stage',
            ],
        ];
        if (null === $secondaryStage) {
            return $this->postActionRedirect(
                array_merge($postActionVars, [
                    'flashes' => [[
                        'type'    => 'error',
                        'msg'     => 'mautic.stage.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ]],
                ])
            );
        }

        if (!$this->security->isGranted(StagePermissions::PERMISSION_EDIT)
            || !$this->security->isGranted(StagePermissions::PERMISSION_DELETE)) {
            $this->throwAccessDenied();
        }

        $stages = $this->stageRepository->getStages(false, (string) $secondaryStage->getId());

        $action = $this->generateUrl('mautic_stage_action', ['objectAction' => 'merge', 'objectId' => $secondaryStage->getId()]);

        $form = $this->createForm(
            StageMergeType::class,
            [],
            [
                'stages' => $stages,
                'action' => $action,
            ]
        );

        if (Request::METHOD_POST === $request->getMethod()) {
            return $this->handleMergeFormSubmission($request, $form, $model, $secondaryStage, $postActionVars, $page);
        }

        $tmpl = $request->get('tmpl', 'index');

        return $this->delegateView(
            [
                'viewParameters' => [
                    'tmpl'         => $tmpl,
                    'action'       => $action,
                    'form'         => $form->createView(),
                    'currentRoute' => $this->generateUrl(
                        'mautic_stage_action',
                        [
                            'objectAction' => 'merge',
                            'objectId'     => $secondaryStage->getId(),
                        ]
                    ),
                ],
                'contentTemplate' => '@MauticStage/Stage/merge.html.twig',
                'passthroughVars' => [
                    'route'  => false,
                    'target' => ('update' === $tmpl) ? '.stage-merge-options' : null,
                ],
            ]
        );
    }

    /**
     * Deletes the entity.
     *
     * @param int $objectId
     */
    public function deleteAction(Request $request, $objectId): Response
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
            $entity = $this->stageModel->getEntity($objectId);

            if (null === $entity) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.stage.error.notfound',
                    'msgVars' => ['%id%' => $objectId],
                ];
            } elseif (!$this->security->isGranted(StagePermissions::PERMISSION_DELETE)) {
                $this->throwAccessDenied();
            } elseif ($this->stageModel->isLocked($entity)) {
                return $this->isLocked($postActionVars, $entity, 'stage');
            }

            $this->stageModel->deleteEntity($entity);

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
     */
    public function batchDeleteAction(Request $request): Response
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
            $ids       = json_decode($request->query->get('ids', '{}'));
            $deleteIds = [];

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $this->stageModel->getEntity($objectId);

                if (null === $entity) {
                    $flashes[] = [
                        'type'    => 'error',
                        'msg'     => 'mautic.stage.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ];
                } elseif (!$this->security->isGranted(StagePermissions::PERMISSION_DELETE)) {
                    $flashes[] = $this->getAccessDeniedFlash();
                } elseif ($this->stageModel->isLocked($entity)) {
                    $flashes[] = $this->isLocked($postActionVars, $entity, 'stage', true);
                } else {
                    $deleteIds[] = $objectId;
                }
            }

            // Delete everything we are able to
            if ([] !== $deleteIds) {
                $entities = $this->stageModel->deleteEntities($deleteIds);

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

    /**
     * Handle merge form submission to reduce cognitive complexity.
     *
     * @param array<string, mixed> $postActionVars
     */
    private function handleMergeFormSubmission(Request $request, FormInterface $form, StageModel $model, Stage $secondaryStage, array $postActionVars, int $page): Response
    {
        if ($this->isFormCancelled($form)) {
            return $this->postActionRedirect(array_merge($postActionVars, [
                'passthroughVars' => [
                    'closeModal'    => 1,
                    'activeLink'    => '#mautic_stage_index',
                    'mauticContent' => 'stage',
                ],
            ]));
        }

        if (!$this->isFormValid($form)) {
            return $this->delegateView([
                'viewParameters' => [
                    'tmpl'         => $request->get('tmpl', 'index'),
                    'action'       => $this->generateUrl('mautic_stage_action', ['objectAction' => 'merge', 'objectId' => $secondaryStage->getId()]),
                    'form'         => $form->createView(),
                    'currentRoute' => $this->generateUrl('mautic_stage_action', [
                        'objectAction' => 'merge',
                        'objectId'     => $secondaryStage->getId(),
                    ]),
                ],
                'contentTemplate' => '@MauticStage/Stage/merge.html.twig',
                'passthroughVars' => [
                    'route'  => false,
                    'target' => ('update' === $request->get('tmpl', 'index')) ? '.stage-merge-options' : null,
                ],
            ]);
        }

        return $this->mergeSubmittedStages($form, $model, $secondaryStage, $postActionVars, $page);
    }

    /**
     * @param array<string, mixed> $postActionVars
     */
    private function mergeSubmittedStages(FormInterface $form, StageModel $model, Stage $secondaryStage, array $postActionVars, int $page): Response
    {
        $data         = $form->getData();
        $primaryId    = $data['stage_to_merge'];
        $primaryStage = $model->getEntity($primaryId);

        if (null === $primaryStage) {
            return $this->postActionRedirect(array_merge($postActionVars, [
                'flashes' => [[
                    'type'    => 'error',
                    'msg'     => 'mautic.stage.error.notfound',
                    'msgVars' => ['%id%' => $primaryId],
                ]],
            ]));
        }

        $lockedStage = $this->getLockedMergeStage($model, $secondaryStage, $primaryStage);
        if (null !== $lockedStage) {
            return $this->isLocked($postActionVars, $lockedStage, 'stage');
        }

        $model->stageMerge($primaryStage, $secondaryStage);

        $viewParameters = ['page' => $page];

        return $this->postActionRedirect([
            'returnUrl'       => $this->generateUrl('mautic_stage_index', $viewParameters),
            'viewParameters'  => $viewParameters,
            'contentTemplate' => 'Mautic\\StageBundle\\Controller\\StageController::indexAction',
            'passthroughVars' => [
                'closeModal'    => 1,
                'activeLink'    => '#mautic_stage_index',
                'mauticContent' => 'stage',
            ],
            'flashes' => [[
                'type'    => 'notice',
                'msg'     => 'mautic.stage.notice.merged',
                'msgVars' => [
                    '%name%' => $secondaryStage->getName(),
                    '%into%' => $primaryStage->getName(),
                ],
            ]],
        ]);
    }

    private function getLockedMergeStage(StageModel $model, Stage $secondaryStage, Stage $primaryStage): ?Stage
    {
        if ($model->isLocked($secondaryStage)) {
            return $secondaryStage;
        }

        if ($model->isLocked($primaryStage)) {
            return $primaryStage;
        }

        return null;
    }
}
