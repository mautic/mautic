<?php

namespace Mautic\PointBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractFormController;
use Mautic\CoreBundle\Factory\PageHelperFactoryInterface;
use Mautic\PointBundle\Entity\Point;
use Mautic\PointBundle\Model\PointModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Service\Attribute\Required;

final class PointController extends AbstractFormController
{
    private PointModel $pointModel;

    #[Required]
    public function autowirePointController(
        PointModel $pointModel,
    ): void {
        $this->pointModel = $pointModel;
    }

    #[Route('/s/points/{objectAction}/{objectId}', name: 'mautic_point_action', requirements: ['objectId' => '[a-zA-Z0-9_-]+'], defaults: ['objectId' => 0])]
    public function executeAction(Request $request, $objectAction, $objectId = 0, $objectSubId = 0, $objectModel = ''): Response
    {
        return parent::executeAction($request, $objectAction, $objectId, $objectSubId, $objectModel);
    }

    #[Route('/s/points/{page}', name: 'mautic_point_index', requirements: ['page' => '\d+'], defaults: ['page' => 0])]
    public function indexAction(Request $request, PageHelperFactoryInterface $pageHelperFactory, int $page = 1): Response
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
            $this->throwAccessDenied();
        }

        $this->setListFilters();

        $pageHelper = $pageHelperFactory->make('mautic.point', $page);

        $limit      = $pageHelper->getLimit();
        $start      = $pageHelper->getStart();
        $search     = $request->get('search', $request->getSession()->get('mautic.point.filter', ''));
        $filter     = ['string' => $search, 'force' => []];
        $orderBy    = $request->getSession()->get('mautic.point.orderby', 'p.name');
        $orderByDir = $request->getSession()->get('mautic.point.orderbydir', 'ASC');
        $points     = $this->pointModel->getEntities([
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
        $actions = $this->pointModel->getPointActions();

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
     * @param Point $entity
     */
    public function newAction(Request $request, $entity = null): Response
    {
        if (!$entity instanceof Point) {
            /** @var Point $entity */
            $entity = $this->pointModel->getEntity();
        }

        if (!$this->security->isGranted('point:points:create')) {
            $this->throwAccessDenied();
        }

        // set the page we came from
        $page       = $request->getSession()->get('mautic.point.page', 1);
        $method     = $request->getMethod();
        $point      = $request->request->all()['point'] ?? [];
        $actionType = 'POST' === $method ? ($point['type'] ?? '') : '';
        $action     = $this->generateUrl('mautic_point_action', ['objectAction' => 'new']);
        $actions    = $this->pointModel->getPointActions();
        $form       = $this->pointModel->createForm($entity, $action, [
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
                    $this->pointModel->saveEntity($entity);

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
                        return $this->editAction($request, $entity->getId(), true);
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
     */
    public function editAction(Request $request, $objectId, $ignorePost = false): Response
    {
        $entity = $this->pointModel->getEntity($objectId);

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
        }
        if (!$this->security->isGranted('point:points:edit')) {
            $this->throwAccessDenied();
        } elseif ($this->pointModel->isLocked($entity)) {
            // deny access if the entity is locked
            return $this->isLocked($postActionVars, $entity, 'point');
        }

        $method     = $request->getMethod();
        $point      = $request->request->all()['point'] ?? [];
        $actionType = 'POST' === $method ? ($point['type'] ?? '') : $entity->getType();

        $action  = $this->generateUrl('mautic_point_action', ['objectAction' => 'edit', 'objectId' => $objectId]);
        $actions = $this->pointModel->getPointActions();
        $form    = $this->pointModel->createForm($entity, $action, [
            'pointActions' => $actions,
            'actionType'   => $actionType,
        ]);

        // /Check for a submitted form and process it
        if (!$ignorePost && 'POST' === $method) {
            $valid = false;

            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    // form is valid so process the data
                    $this->pointModel->saveEntity($entity, $this->getFormButton($form, ['buttons', 'save'])->isClicked());

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
                $this->pointModel->unlockEntity($entity);

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
            $this->pointModel->lockEntity($entity);
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
     * @param int $objectId
     */
    public function cloneAction(Request $request, $objectId): Response
    {
        $entity = $this->pointModel->getEntity($objectId);

        if (null != $entity) {
            if (!$this->security->isGranted('point:points:create')) {
                $this->throwAccessDenied();
            }

            $entity = clone $entity;
            $entity->setIsPublished(false);
        }

        return $this->newAction($request, $entity);
    }

    /**
     * Deletes the entity.
     *
     * @param int $objectId
     */
    public function deleteAction(Request $request, $objectId): Response
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
            $entity = $this->pointModel->getEntity($objectId);

            if (null === $entity) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.point.error.notfound',
                    'msgVars' => ['%id%' => $objectId],
                ];
            } elseif (!$this->security->isGranted('point:points:delete')) {
                $this->throwAccessDenied();
            } elseif ($this->pointModel->isLocked($entity)) {
                return $this->isLocked($postActionVars, $entity, 'point');
            }

            $this->pointModel->deleteEntity($entity);

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
     */
    public function batchDeleteAction(Request $request): Response
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
            $ids       = json_decode($request->query->get('ids', '{}'));
            $deleteIds = [];

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $this->pointModel->getEntity($objectId);

                if (null === $entity) {
                    $flashes[] = [
                        'type'    => 'error',
                        'msg'     => 'mautic.point.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ];
                } elseif (!$this->security->isGranted('point:points:delete')) {
                    $flashes[] = $this->getAccessDeniedFlash();
                } elseif ($this->pointModel->isLocked($entity)) {
                    $flashes[] = $this->isLocked($postActionVars, $entity, 'point', true);
                } else {
                    $deleteIds[] = $objectId;
                }
            }

            // Delete everything we are able to
            if ([] !== $deleteIds) {
                $entities = $this->pointModel->deleteEntities($deleteIds);

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
