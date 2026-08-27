<?php

namespace Mautic\CategoryBundle\Controller;

use Mautic\CategoryBundle\CategoryEvents;
use Mautic\CategoryBundle\Event\CategoryTypesEvent;
use Mautic\CategoryBundle\Model\CategoryModel;
use Mautic\CoreBundle\Controller\AbstractFormController;
use Mautic\CoreBundle\Exception\DeleteEntityDependencyException;
use Mautic\CoreBundle\Service\FlashBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\Attribute\Required;

final class CategoryController extends AbstractFormController
{
    private CategoryModel $categoryModel;

    #[Required]
    public function autowireCategoryController(
        CategoryModel $categoryModel,
    ): void {
        $this->categoryModel = $categoryModel;
    }

    /**
     * @param int    $objectId
     * @param string $objectModel
     */
    public function executeCategoryAction(Request $request, $bundle, $objectAction, $objectId = 0, $objectModel = ''): Response
    {
        if (method_exists($this, $objectAction.'Action')) {
            return $this->forward(
                self::class.'::'.$objectAction.'Action',
                [
                    'bundle'      => $bundle,
                    'objectId'    => $objectId,
                    'objectModel' => $objectModel,
                ],
                $request->query->all()
            );
        }

        return $this->notFound();
    }

    /**
     * @param string $bundle
     * @param int    $page
     */
    public function indexAction(Request $request, $bundle, $page = 1): Response
    {
        $session = $request->getSession();

        $categoryFilter = (string) $session->get('mautic.category.filter', '');
        $search = $request->query->get('search', $categoryFilter);

        $categoryType = (string) $session->get('mautic.category.type', $bundle);
        $bundle = $request->query->get('bundle', $categoryType);

        if ($bundle) {
            $session->set('mautic.category.type', $bundle);
        }

        // hack to make pagination work for default list view
        if ('all' == $bundle) {
            $bundle = 'category';
        }

        $session->set('mautic.category.filter', $search);
        $permissionBase = $this->categoryModel->getPermissionBase($bundle);
        $permissions    = $this->security->isGranted(
            [
                $permissionBase.':view',
                $permissionBase.':create',
                $permissionBase.':edit',
                $permissionBase.':delete',
            ],
            'RETURN_ARRAY'
        );

        if (!$permissions[$permissionBase.':view']) {
            $this->throwAccessDenied();
        }

        $this->setListFilters();

        $viewParams = [
            'page'   => $page,
            'bundle' => $bundle,
        ];

        // set limits
        $limit = $session->get('mautic.category.limit', $this->coreParametersHelper->get('default_pagelimit'));
        $start = (1 === $page) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $filter = ['string' => $search];

        if ('category' != $bundle) {
            $filter['force'] = [
                [
                    'column' => 'c.bundle',
                    'expr'   => 'eq',
                    'value'  => $bundle,
                ],
            ];
        }

        $orderBy    = $request->getSession()->get('mautic.category.orderby', 'c.title');
        $orderByDir = $request->getSession()->get('mautic.category.orderbydir', 'DESC');

        $entities = $this->categoryModel->getEntities(
            [
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir,
            ]
        );

        $count = count($entities);
        if ($count && $count < ($start + 1)) {
            // the number of entities are now less then the current page so redirect to the last page
            if (1 === $count) {
                $lastPage = 1;
            } else {
                $lastPage = (ceil($count / $limit)) ?: 1;
            }
            $viewParams['page'] = $lastPage;
            $session->set('mautic.category.page', $lastPage);
            $returnUrl = $this->generateUrl('mautic_category_index', $viewParams);

            return $this->postActionRedirect(
                [
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => ['page' => $lastPage],
                    'contentTemplate' => 'Mautic\CategoryBundle\Controller\CategoryController::indexAction',
                    'passthroughVars' => [
                        'activeLink'    => '#mautic_'.$bundle.'category_index',
                        'mauticContent' => 'category',
                    ],
                ]
            );
        }

        $categoryTypes = ['category' => $this->translator->trans('mautic.core.select')];

        if ($this->dispatcher->hasListeners(CategoryEvents::CATEGORY_ON_BUNDLE_LIST_BUILD)) {
            $event = new CategoryTypesEvent();
            $this->dispatcher->dispatch($event, CategoryEvents::CATEGORY_ON_BUNDLE_LIST_BUILD);
            $categoryTypes = array_merge($categoryTypes, $event->getCategoryTypes());
        }

        // set what page currently on so that we can return here after form submission/cancellation
        $session->set('mautic.category.page', $page);

        $tmpl = $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index';

        return $this->delegateView(
            [
                'returnUrl'      => $this->generateUrl('mautic_category_index', $viewParams),
                'viewParameters' => [
                    'bundle'         => $bundle,
                    'permissionBase' => $permissionBase,
                    'searchValue'    => $search,
                    'items'          => $entities,
                    'page'           => $page,
                    'limit'          => $limit,
                    'permissions'    => $permissions,
                    'tmpl'           => $tmpl,
                    'categoryTypes'  => $categoryTypes,
                ],
                'contentTemplate' => '@MauticCategory/Category/list.html.twig',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_'.$bundle.'category_index',
                    'mauticContent' => 'category',
                    'route'         => $this->generateUrl('mautic_category_index', $viewParams),
                ],
            ]
        );
    }

    /**
     * Generates new form and processes post data.
     */
    public function newAction(Request $request, $bundle): JsonResponse|Response
    {
        $session    = $request->getSession();
        $entity     = $this->categoryModel->getEntity();
        $success    = 0;
        $cancelled  = $valid  = false;
        $method     = $request->getMethod();
        $inForm     = $this->getInFormValue($request, $method);

        // not found
        if (!$this->security->isGranted($this->categoryModel->getPermissionBase($bundle).':create')) {
            return $this->modalAccessDenied();
        }
        // Create the form
        $action = $this->generateUrl('mautic_category_action', [
            'objectAction' => 'new',
            'bundle'       => $bundle,
        ]);
        $form = $this->categoryModel->createForm($entity, $action, ['bundle' => $bundle, 'show_bundle_select' => 'category' === $bundle]);
        $form['inForm']->setData($inForm);
        // /Check for a submitted form and process it
        if (Request::METHOD_POST === $method) {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $success = 1;

                    // form is valid so process the data
                    $this->categoryModel->saveEntity($entity, $this->getFormButton($form, ['buttons', 'save'])->isClicked());

                    $this->addFlashMessage('mautic.category.notice.created', [
                        '%name%' => $entity->getTitle(),
                    ]);
                }
            } else {
                $success = 1;
            }
        }

        $closeModal = ($cancelled || ($valid && $this->getFormButton($form, ['buttons', 'save'])->isClicked()));

        if ($closeModal) {
            if ($inForm) {
                return new JsonResponse([
                    'mauticContent' => 'category',
                    'closeModal'    => 1,
                    'inForm'        => 1,
                    'categoryName'  => $entity->getTitle(),
                    'categoryId'    => $entity->getId(),
                ]);
            }

            $viewParameters = [
                'page'   => $session->get('mautic.category.page'),
                'bundle' => $bundle,
            ];

            return $this->postActionRedirect([
                'returnUrl'       => $this->generateUrl('mautic_category_index', $viewParameters),
                'viewParameters'  => $viewParameters,
                'contentTemplate' => 'Mautic\CategoryBundle\Controller\CategoryController::indexAction',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_'.$bundle.'category_index',
                    'mauticContent' => 'category',
                    'closeModal'    => 1,
                ],
            ]);
        }
        if (!empty($valid)) {
            // return edit view to prevent duplicates
            return $this->editAction($request, $bundle, $entity->getId(), true);
        }

        return $this->ajaxAction(
            $request,
            [
                'contentTemplate' => '@MauticCategory/Category/form.html.twig',
                'viewParameters'  => [
                    'form'           => $form->createView(),
                    'activeCategory' => $entity,
                    'bundle'         => $bundle,
                ],
                'passthroughVars' => [
                    'mauticContent' => 'category',
                    'success'       => $success,
                    'route'         => false,
                ],
            ]
        );
    }

    /**
     * Generates edit form and processes post data.
     */
    public function editAction(Request $request, ?string $bundle, $objectId, bool $ignorePost = false): JsonResponse|Response
    {
        $session = $request->getSession();
        $entity    = $this->categoryModel->getEntity($objectId);
        $success   = $closeModal   = 0;
        $cancelled = $valid = false;
        $method    = $request->getMethod();
        $inForm    = $this->getInFormValue($request, $method);
        $response  = null;
        // not found
        if (null === $entity) {
            $closeModal = true;
        } elseif (!$this->security->isGranted($this->categoryModel->getPermissionBase($bundle).':edit')) {
            $response = $this->modalAccessDenied();
        } elseif ($this->categoryModel->isLocked($entity)) {
            $flashMsg = $this->isLocked([], $entity, 'category', true);
            $this->addFlashMessage($flashMsg['msg'], $flashMsg['msgVars'], FlashBag::LEVEL_ERROR);

            $response = new JsonResponse([
                'closeModal' => true,
                'flashes'    => $this->getFlashContent(),
            ]);
        }

        if (null !== $response) {
            return $response;
        }

        // Create the form
        $action = $this->generateUrl(
            'mautic_category_action',
            [
                'objectAction' => 'edit',
                'objectId'     => $objectId,
                'bundle'       => $bundle,
            ]
        );
        $form = $this->categoryModel->createForm($entity, $action, ['bundle' => $bundle]);
        $form['inForm']->setData($inForm);

        // /Check for a submitted form and process it
        if (!$ignorePost && 'POST' === $method) {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $success = 1;

                    // form is valid so process the data
                    $this->categoryModel->saveEntity($entity, $this->getFormButton($form, ['buttons', 'save'])->isClicked());

                    $this->addFlashMessage(
                        'mautic.category.notice.updated',
                        [
                            '%name%' => $entity->getTitle(),
                        ]
                    );

                    if ($this->isButtonClicked($form, 'apply')) {
                        // Rebuild the form with new action so that apply doesn't keep creating a clone
                        $action = $this->generateUrl(
                            'mautic_category_action',
                            [
                                'objectAction' => 'edit',
                                'objectId'     => $entity->getId(),
                                'bundle'       => $bundle,
                            ]
                        );
                        $form = $this->categoryModel->createForm($entity, $action, ['bundle' => $bundle]);
                    }
                }
            } else {
                $success = 1;

                // unlock the entity
                $this->categoryModel->unlockEntity($entity);
            }
        } else {
            // lock the entity
            $this->categoryModel->lockEntity($entity);
        }

        $closeModal = ($closeModal || $cancelled || ($valid && $this->getFormButton($form, ['buttons', 'save'])->isClicked()));

        if ($closeModal) {
            if ($inForm) {
                $response = new JsonResponse(
                    [
                        'mauticContent' => 'category',
                        'closeModal'    => 1,
                        'inForm'        => 1,
                        'categoryName'  => $entity->getTitle(),
                        'categoryId'    => $entity->getId(),
                    ]
                );
            } else {
                $viewParameters = [
                    'page'   => $session->get('mautic.category.page'),
                    'bundle' => $bundle,
                ];

                $response = $this->postActionRedirect(
                    [
                        'returnUrl'       => $this->generateUrl('mautic_category_index', $viewParameters),
                        'viewParameters'  => $viewParameters,
                        'contentTemplate' => 'Mautic\CategoryBundle\Controller\CategoryController::indexAction',
                        'passthroughVars' => [
                            'activeLink'    => '#mautic_'.$bundle.'category_index',
                            'mauticContent' => 'category',
                            'closeModal'    => 1,
                        ],
                    ]
                );
            }
        } else {
            $response = $this->ajaxAction(
                $request,
                [
                    'contentTemplate' => '@MauticCategory/Category/form.html.twig',
                    'viewParameters'  => [
                        'form'           => $form->createView(),
                        'activeCategory' => $entity,
                        'bundle'         => $bundle,
                    ],
                    'passthroughVars' => [
                        'mauticContent' => 'category',
                        'success'       => $success,
                        'route'         => false,
                    ],
                ]
            );
        }

        return $response;
    }

    /**
     * Deletes the entity.
     */
    public function deleteAction(Request $request, ?string $bundle, $objectId): Response
    {
        $session    = $request->getSession();
        $page       = $session->get('mautic.category.page', 1);
        $viewParams = [
            'page'   => $page,
            'bundle' => $bundle,
        ];
        $returnUrl = $this->generateUrl('mautic_category_index', $viewParams);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => $viewParams,
            'contentTemplate' => 'Mautic\CategoryBundle\Controller\CategoryController::indexAction',
            'passthroughVars' => [
                'activeLink'    => 'mautic_'.$bundle.'category_index',
                'mauticContent' => 'category',
            ],
        ];

        if (Request::METHOD_POST === $request->getMethod()) {
            $entity = $this->categoryModel->getEntity($objectId);

            if (null === $entity) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.category.error.notfound',
                    'msgVars' => ['%id%' => $objectId],
                ];
            } elseif (!$this->security->isGranted($this->categoryModel->getPermissionBase($bundle).':delete')) {
                $this->throwAccessDenied();
            } elseif ($this->categoryModel->isLocked($entity)) {
                return $this->isLocked($postActionVars, $entity, 'category.category');
            }

            try {
                $this->categoryModel->deleteEntity($entity);

                $flashes[] = [
                    'type'    => 'notice',
                    'msg'     => 'mautic.core.notice.deleted',
                    'msgVars' => [
                        '%name%' => $entity->getTitle(),
                        '%id%'   => $objectId,
                    ],
                ];
            } catch (DeleteEntityDependencyException $exception) {
                foreach ($exception->getErrors() as $error) {
                    $flashes[] = [
                        'type' => 'error',
                        'msg'  => $error,
                    ];
                }
            }
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
    public function batchDeleteAction(Request $request, ?string $bundle): Response
    {
        $session    = $request->getSession();
        $page       = $session->get('mautic.category.page', 1);
        $viewParams = [
            'page'   => $page,
            'bundle' => $bundle,
        ];
        $returnUrl = $this->generateUrl('mautic_category_index', $viewParams);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => $viewParams,
            'contentTemplate' => 'Mautic\CategoryBundle\Controller\CategoryController::indexAction',
            'passthroughVars' => [
                'activeLink'    => 'mautic_'.$bundle.'category_index',
                'mauticContent' => 'category',
            ],
        ];

        if (Request::METHOD_POST === $request->getMethod()) {
            $ids       = json_decode($request->query->get('ids', '{}'));
            $deleteIds = [];

            // Loop over the IDs to perform access checks and delete
            $deletedExceptions = [];
            foreach ($ids as $objectId) {
                $entity = $this->categoryModel->getEntity($objectId);

                if (null === $entity) {
                    $flashes[] = [
                        'type'    => 'error',
                        'msg'     => 'mautic.category.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ];
                } elseif (!$this->security->isGranted($this->categoryModel->getPermissionBase($bundle).':delete')) {
                    $flashes[] = $this->getAccessDeniedFlash();
                } elseif ($this->categoryModel->isLocked($entity)) {
                    $flashes[] = $this->isLocked($postActionVars, $entity, 'category', true);
                } else {
                    try {
                        // Delete everything we are able to
                        $this->categoryModel->deleteEntity($entity);
                        $deleteIds[] = $objectId;
                    } catch (DeleteEntityDependencyException $exception) {
                        $deletedExceptions[] = $exception;
                    }
                }
            }

            if ([] !== $deleteIds) {
                $flashes[] = [
                    'type'    => 'notice',
                    'msg'     => 'mautic.category.notice.batch_deleted',
                    'msgVars' => [
                        '%count%' => count($deleteIds),
                    ],
                ];
            }

            foreach ($deletedExceptions as $deletedException) {
                foreach ($deletedException->getErrors() as $error) {
                    $flashes[] = [
                        'type' => 'error',
                        'msg'  => $error,
                    ];
                }
            }
        } // else don't do anything

        return $this->postActionRedirect(
            array_merge($postActionVars, [
                'flashes' => $flashes,
            ])
        );
    }

    private function getInFormValue(Request $request, string $method): int
    {
        $inForm = $request->get('inForm', 0);
        if (Request::METHOD_POST === $method) {
            $category_form = $request->request->all()['category_form'] ?? [];
            $inForm        = $category_form['inForm'] ?? 0;
        }

        return (int) $inForm;
    }
}
