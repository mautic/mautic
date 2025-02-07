<?php

namespace Mautic\DynamicContentBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Form\Type\DateRangeType;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\DynamicContentBundle\Model\DynamicContentModel;
use Mautic\PageBundle\Model\PageModel;
use Mautic\PageBundle\Model\TrackableModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DynamicContentController extends FormController
{
    protected function getPermissions(): array
    {
        return (array) $this->security->isGranted(
            [
                'dynamiccontent:dynamiccontents:viewown',
                'dynamiccontent:dynamiccontents:viewother',
                'dynamiccontent:dynamiccontents:create',
                'dynamiccontent:dynamiccontents:editown',
                'dynamiccontent:dynamiccontents:editother',
                'dynamiccontent:dynamiccontents:deleteown',
                'dynamiccontent:dynamiccontents:deleteother',
                'dynamiccontent:dynamiccontents:publishown',
                'dynamiccontent:dynamiccontents:publishother',
            ],
            'RETURN_ARRAY'
        );
    }

    public function indexAction(Request $request, $page = 1)
    {
        $model = $this->getModel('dynamicContent');

        $permissions = $this->getPermissions();

        if (!$permissions['dynamiccontent:dynamiccontents:viewown'] && !$permissions['dynamiccontent:dynamiccontents:viewother']) {
            return $this->accessDenied();
        }

        $this->setListFilters();

        $limit = $request->getSession()->get('mautic.dynamicContent.limit', $this->coreParametersHelper->get('default_pagelimit'));
        $start = (1 === $page) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        // fetch
        $search = $request->get('search', $request->getSession()->get('mautic.dynamicContent.filter', ''));
        $request->getSession()->set('mautic.dynamicContent.filter', $search);

        $filter = [
            'string' => $search,
            'force'  => [
                ['column' => 'e.variantParent', 'expr' => 'isNull'],
                ['column' => 'e.translationParent', 'expr' => 'isNull'],
            ],
        ];

        $orderBy    = $request->getSession()->get('mautic.dynamicContent.orderby', 'e.name');
        $orderByDir = $request->getSession()->get('mautic.dynamicContent.orderbydir', 'DESC');

        $entities = $model->getEntities(
            [
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir,
            ]
        );

        // set what page currently on so that we can return here after form submission/cancellation
        $request->getSession()->set('mautic.dynamicContent.page', $page);

        $tmpl = $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index';

        // retrieve a list of categories
        $pageModel  = $this->getModel('page');
        \assert($pageModel instanceof PageModel);
        $categories = $pageModel->getLookupResults('category', '', 0);

        return $this->delegateView(
            [
                'contentTemplate' => '@MauticDynamicContent/DynamicContent/list.html.twig',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_dynamicContent_index',
                    'mauticContent' => 'dynamicContent',
                    'route'         => $this->generateUrl('mautic_dynamicContent_index', ['page' => $page]),
                ],
                'viewParameters' => [
                    'searchValue' => $search,
                    'items'       => $entities,
                    'categories'  => $categories,
                    'page'        => $page,
                    'limit'       => $limit,
                    'permissions' => $permissions,
                    'model'       => $model,
                    'tmpl'        => $tmpl,
                ],
            ]
        );
    }

    public function newAction(Request $request, $entity = null)
    {
        if (!$this->security->isGranted('dynamiccontent:dynamiccontents:create')) {
            return $this->accessDenied();
        }

        if (!$entity instanceof DynamicContent) {
            $entity = new DynamicContent();
        }

        $model = $this->getModel('dynamicContent');
        \assert($model instanceof DynamicContentModel);
        $method       = $request->getMethod();
        $page         = $request->getSession()->get('mautic.dynamicContent.page', 1);
        $retUrl       = $this->generateUrl('mautic_dynamicContent_index', ['page' => $page]);
        $action       = $this->generateUrl('mautic_dynamicContent_action', ['objectAction' => 'new']);
        $dwc          = $request->request->all()['dwc'] ?? [];
        $updateSelect = 'POST' === $method
            ? ($dwc['updateSelect'] ?? false)
            : $request->get('updateSelect', false);
        $form         = $model->createForm($entity, $this->formFactory, $action, ['update_select' => $updateSelect]);

        if (Request::METHOD_POST === $method) {
            $valid = false;

            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $model->saveEntity($entity);

                    $this->addFlashMessage(
                        'mautic.core.notice.created',
                        [
                            '%name%'      => $entity->getName(),
                            '%menu_link%' => 'mautic_dynamicContent_index',
                            '%url%'       => $this->generateUrl(
                                'mautic_dynamicContent_action',
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
                        $retUrl   = $this->generateUrl('mautic_dynamicContent_action', $viewParameters);
                        $template = 'Mautic\DynamicContentBundle\Controller\DynamicContentController::viewAction';
                    } else {
                        // return edit view so that all the session stuff is loaded
                        return $this->editAction($request, $entity->getId(), true);
                    }
                }
            } else {
                $viewParameters = ['page' => $page];
                $retUrl         = $this->generateUrl('mautic_dynamicContent_index', $viewParameters);
                $template       = 'Mautic\DynamicContentBundle\Controller\DynamicContentController::indexAction';
            }

            $passthrough = [
                'activeLink'    => '#mautic_dynamicContent_index',
                'mauticContent' => 'dynamicContent',
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
                        'returnUrl'       => $retUrl,
                        'viewParameters'  => $viewParameters,
                        'contentTemplate' => $template,
                        'passthroughVars' => $passthrough,
                    ]
                );
            } elseif ($valid && !$cancelled) {
                return $this->editAction($request, $entity->getId(), true);
            }
        }

        $passthrough['route'] = $action;

        return $this->delegateView(
            [
                'viewParameters' => [
                    'form' => $form->createView(),
                ],
                'contentTemplate' => '@MauticDynamicContent/DynamicContent/form.html.twig',
                'passthroughVars' => $passthrough,
            ]
        );
    }

    /**
     * Generate's edit form and processes post data.
     *
     * @param bool|false $ignorePost
     *
     * @return array|JsonResponse|RedirectResponse|Response
     */
    public function editAction(Request $request, $objectId, $ignorePost = false)
    {
        /** @var DynamicContentModel $model */
        $model  = $this->getModel('dynamicContent');
        $entity = $model->getEntity($objectId);
        $page   = $request->getSession()->get('mautic.dynamicContent.page', 1);
        $retUrl = $this->generateUrl('mautic_dynamicContent_index', ['page' => $page]);

        $postActionVars = [
            'returnUrl'       => $retUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\DynamicContentBundle\Controller\DynamicContentController::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_dynamicContent_index',
                'mauticContent' => 'dynamicContent',
            ],
        ];

        if (null === $entity) {
            return $this->postActionRedirect(
                array_merge(
                    $postActionVars,
                    [
                        'flashes' => [
                            [
                                'type'    => 'error',
                                'msg'     => 'mautic.dynamicContent.error.notfound',
                                'msgVars' => ['%id%' => $objectId],
                            ],
                        ],
                    ]
                )
            );
        } elseif (!$this->security->hasEntityAccess(true, 'dynamiccontent:dynamiccontents:editother', $entity->getCreatedBy())) {
            return $this->accessDenied();
        } elseif ($model->isLocked($entity)) {
            // deny access if the entity is locked
            return $this->isLocked($postActionVars, $entity, 'dynamicContent');
        }

        $action       = $this->generateUrl('mautic_dynamicContent_action', ['objectAction' => 'edit', 'objectId' => $objectId]);
        $method       = $request->getMethod();
        $dwc          = $request->request->all['dwc'] ?? [];
        $updateSelect = 'POST' === $method
            ? ($dwc['updateSelect'] ?? false)
            : $request->get('updateSelect', false);

        $form = $model->createForm($entity, $this->formFactory, $action, ['update_select' => $updateSelect]);

        // /Check for a submitted form and process it
        if (!$ignorePost && 'POST' === $method) {
            $valid = false;

            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    // form is valid so process the data
                    $model->saveEntity($entity, $this->getFormButton($form, ['buttons', 'save'])->isClicked());

                    $this->addFlashMessage(
                        'mautic.core.notice.updated',
                        [
                            '%name%'      => $entity->getName(),
                            '%menu_link%' => 'mautic_dynamicContent_index',
                            '%url%'       => $this->generateUrl(
                                'mautic_dynamicContent_action',
                                [
                                    'objectAction' => 'edit',
                                    'objectId'     => $entity->getId(),
                                ]
                            ),
                        ]
                    );
                }
            } else {
                // unlock the entity
                $model->unlockEntity($entity);
            }

            if ($cancelled || ($valid && $this->getFormButton($form, ['buttons', 'save'])->isClicked())) {
                return $this->viewAction($request, $entity->getId());
            }
        } else {
            // lock the entity
            $model->lockEntity($entity);
        }

        return $this->delegateView(
            [
                'viewParameters' => [
                    'form'          => $form->createView(),
                    'currentListId' => $objectId,
                ],
                'contentTemplate' => '@MauticDynamicContent/DynamicContent/form.html.twig',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_dynamicContent_index',
                    'route'         => $action,
                    'mauticContent' => 'dynamicContent',
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
        $model = $this->getModel('dynamicContent');
        \assert($model instanceof DynamicContentModel);
        $security = $this->security;
        $entity   = $model->getEntity($objectId);

        // set the page we came from
        $page = $request->getSession()->get('mautic.dynamicContent.page', 1);

        if (null === $entity) {
            // set the return URL
            $returnUrl = $this->generateUrl('mautic_dynamicContent_index', ['page' => $page]);

            return $this->postActionRedirect(
                [
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => ['page' => $page],
                    'contentTemplate' => 'Mautic\DynamicContentBundle\Controller\DynamicContentController::indexAction',
                    'passthroughVars' => [
                        'activeLink'    => '#mautic_dynamicContent_index',
                        'mauticContent' => 'dynamicContent',
                    ],
                    'flashes' => [
                        [
                            'type'    => 'error',
                            'msg'     => 'mautic.dynamicContent.error.notfound',
                            'msgVars' => ['%id%' => $objectId],
                        ],
                    ],
                ]
            );
        } elseif (!$security->hasEntityAccess(
            'dynamiccontent:dynamiccontents:viewown',
            'dynamiccontent:dynamiccontents:viewother',
            $entity->getCreatedBy()
        )
        ) {
            return $this->accessDenied();
        }

        /* @var DynamicContent $parent */
        /* @var DynamicContent[] $children */
        [$translationParent, $translationChildren] = $entity->getTranslations();

        // Audit Log
        $auditLogModel = $this->getModel('core.auditlog');
        \assert($auditLogModel instanceof AuditLogModel);
        $logs          = $auditLogModel->getLogForObject('dynamicContent', $entity->getId(), $entity->getDateAdded());

        // Init the date range filter form
        $dateRangeValues = $request->query->all()['daterange'] ?? $request->request->all()['daterange'] ?? [];
        $action          = $this->generateUrl('mautic_dynamicContent_action', ['objectAction' => 'view', 'objectId' => $objectId]);
        $dateRangeForm   = $this->formFactory->create(DateRangeType::class, $dateRangeValues, ['action' => $action]);
        $entityViews     = $model->getHitsLineChartData(
            null,
            new \DateTime($dateRangeForm->get('date_from')->getData()),
            new \DateTime($dateRangeForm->get('date_to')->getData()),
            null,
            ['dynamic_content_id' => $entity->getId(), 'flag' => 'total_and_unique']
        );

        $trackableModel = $this->getModel('page.trackable');
        \assert($trackableModel instanceof TrackableModel);
        $trackables = $trackableModel->getTrackableList('dynamicContent', $entity->getId());

        return $this->delegateView(
            [
                'returnUrl'       => $action,
                'contentTemplate' => '@MauticDynamicContent/DynamicContent/details.html.twig',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_dynamicContent_index',
                    'mauticContent' => 'dynamicContent',
                ],
                'viewParameters' => [
                    'entity'       => $entity,
                    'permissions'  => $this->getPermissions(),
                    'logs'         => $logs,
                    'isEmbedded'   => $request->get('isEmbedded') ?: false,
                    'translations' => [
                        'parent'   => $translationParent,
                        'children' => $translationChildren,
                    ],
                    'trackables'    => $trackables,
                    'entityViews'   => $entityViews,
                    'dateRangeForm' => $dateRangeForm->createView(),
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
        $model  = $this->getModel('dynamicContent');
        $entity = $model->getEntity($objectId);

        if (null != $entity) {
            if (!$this->security->isGranted('dynamiccontent:dynamiccontents:create')
                || !$this->security->hasEntityAccess(
                    'dynamiccontent:dynamiccontents:viewown',
                    'dynamiccontent:dynamiccontents:viewother',
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
        $page      = $request->getSession()->get('mautic.dynamicContent.page', 1);
        $returnUrl = $this->generateUrl('mautic_dynamicContent_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\DynamicContentBundle\Controller\DynamicContentController::indexAction',
            'passthroughVars' => [
                'activeLink'    => 'mautic_dynamicContent_index',
                'mauticContent' => 'dynamicContent',
            ],
        ];

        if (Request::METHOD_POST === $request->getMethod()) {
            $model  = $this->getModel('dynamicContent');
            \assert($model instanceof DynamicContentModel);
            $entity = $model->getEntity($objectId);

            if (null === $entity) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.dynamicContent.error.notfound',
                    'msgVars' => ['%id%' => $objectId],
                ];

                return $this->postActionRedirect(array_merge($postActionVars, ['flashes' => $flashes]));
            } elseif (!$this->security->hasEntityAccess(
                'dynamiccontent:dynamiccontents:deleteown',
                'dynamiccontent:dynamiccontents:deleteother',
                $entity->getCreatedBy()
            )
            ) {
                return $this->accessDenied();
            } elseif ($model->isLocked($entity)) {
                return $this->isLocked($postActionVars, $entity, 'notification');
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

        return $this->postActionRedirect(array_merge($postActionVars, ['flashes' => $flashes]));
    }

    /**
     * Deletes a group of entities.
     */
    public function batchDeleteAction(Request $request): Response
    {
        $page      = $request->getSession()->get('mautic.dynamicContent.page', 1);
        $returnUrl = $this->generateUrl('mautic_dynamicContent_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\DynamicContentBundle\Controller\DynamicContentController::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_dynamicContent_index',
                'mauticContent' => 'dynamicContent',
            ],
        ];

        if (Request::METHOD_POST === $request->getMethod()) {
            $model = $this->getModel('dynamicContent');
            \assert($model instanceof DynamicContentModel);
            $ids = json_decode($request->query->get('ids', '{}'));

            $deleteIds = [];

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $model->getEntity($objectId);

                if (null === $entity) {
                    $flashes[] = [
                        'type'    => 'error',
                        'msg'     => 'mautic.dynamicContent.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ];
                } elseif (!$this->security->hasEntityAccess(
                    'dynamiccontent:dynamiccontents:viewown',
                    'dynamiccontent:dynamiccontents:viewother',
                    $entity->getCreatedBy()
                )
                ) {
                    $flashes[] = $this->accessDenied(true);
                } elseif ($model->isLocked($entity)) {
                    $flashes[] = $this->isLocked($postActionVars, $entity, 'dynamicContent', true);
                } else {
                    $deleteIds[] = $objectId;
                }
            }

            // Delete everything we are able to
            if (!empty($deleteIds)) {
                $entities = $model->deleteEntities($deleteIds);

                $flashes[] = [
                    'type'    => 'notice',
                    'msg'     => 'mautic.dynamicContent.notice.batch_deleted',
                    'msgVars' => [
                        '%count%' => count($entities),
                    ],
                ];
            }
        } // else don't do anything

        return $this->postActionRedirect(array_merge($postActionVars, ['flashes' => $flashes]));
    }
}
