<?php

namespace Mautic\CategoryBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CategoryBundle\CategoryEvents;
use Mautic\CategoryBundle\Event\CategoryTypesEvent;
use Mautic\CategoryBundle\Model\CategoryModel;
use Mautic\CoreBundle\Controller\AbstractFormController;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Translation\Translator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class CategoryController extends AbstractFormController
{
    public function __construct(
        private FormFactoryInterface $formFactory,
        ManagerRegistry $doctrine,
        MauticFactory $factory,
        ModelFactory $modelFactory,
        UserHelper $userHelper,
        CoreParametersHelper $coreParametersHelper,
        EventDispatcherInterface $dispatcher,
        Translator $translator,
        FlashBag $flashBag,
        RequestStack $requestStack,
        CorePermissions $security
    ) {
        parent::__construct($doctrine, $factory, $modelFactory, $userHelper, $coreParametersHelper, $dispatcher, $translator, $flashBag, $requestStack, $security);
    }

    /**
     * @param int    $objectId
     * @param string $objectModel
     */
    public function executeCategoryAction(Request $request, $bundle, $objectAction, $objectId = 0, $objectModel = ''): Response
    {
        if (method_exists($this, $objectAction.'Action')) {
            return $this->forward(
                static::class.'::'.$objectAction.'Action',
                [
                    'bundle'      => $bundle,
                    'objectId'    => $objectId,
                    'objectModel' => $objectModel,
                ],
                $request->query->all()
            );
        }

        return $this->accessDenied();
    }

    /**
     * @param int $page
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request, $bundle, $page = 1)
    {
        $session = $request->getSession();

        $search = $request->query->get('search', $session->get('mautic.category.filter', ''));
        $bundle = $request->query->get('bundle', $session->get('mautic.category.type', $bundle));

        if ($bundle) {
            $session->set('mautic.category.type', $bundle);
        }

        // hack to make pagination work for default list view
        if ('all' == $bundle) {
            $bundle = 'category';
        }

        $session->set('mautic.category.filter', $search);

        // set some permissions
        $categoryModel  = $this->getModel('category');
        \assert($categoryModel instanceof CategoryModel);
        $permissionBase = $categoryModel->getPermissionBase($bundle);
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
            return $this->accessDenied();
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

        $entities = $categoryModel->getEntities(
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

        $dispatcher = $this->dispatcher;
        if ($dispatcher->hasListeners(CategoryEvents::CATEGORY_ON_BUNDLE_LIST_BUILD)) {
            $event = new CategoryTypesEvent();
            $dispatcher->dispatch($event, CategoryEvents::CATEGORY_ON_BUNDLE_LIST_BUILD);
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
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request, $bundle)
    {
        $model = $this->getModel('category');
        \assert($model instanceof CategoryModel);
        $session    = $request->getSession();
        $entity     = $model->getEntity();
        $success    = 0;
        $cancelled  = $valid  = false;
        $method     = $request->getMethod();
        $inForm     = $this->getInFormValue($request, $method);
        $showSelect = $request->get('show_bundle_select', false);

        // not found
        if (!$this->security->isGranted($model->getPermissionBase($bundle).':create')) {
            return $this->modalAccessDenied();
        }
        // Create the form
        $action = $this->generateUrl('mautic_category_action', [
            'objectAction' => 'new',
            'bundle'       => $bundle,
        ]);
        $form = $model->createForm($entity, $this->formFactory, $action, ['bundle' => $bundle, 'show_bundle_select' => $showSelect]);
        $form['inForm']->setData($inForm);
        // /Check for a submitted form and process it
        if (Request::METHOD_POST === $method) {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $success = 1;

                    // form is valid so process the data
                    $model->saveEntity($entity, $this->getFormButton($form, ['buttons', 'save'])->isClicked());

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
        } elseif (!empty($valid)) {
            // return edit view to prevent duplicates
            return $this->editAction($request, $bundle, $entity->getId(), true);
        } else {
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
    }

    /**
     * Generates edit form and processes post data.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $bundle, $objectId, $ignorePost = false)
    {
        $session = $request->getSession();
        $model   = $this->getModel('category');
        \assert($model instanceof CategoryModel);
        $entity    = $model->getEntity($objectId);
        $success   = $closeModal   = 0;
        $cancelled = $valid = false;
        $method    = $request->getMethod();
        $inForm    = $this->getInFormValue($request, $method);
        // not found
        if (null === $entity) {
            $closeModal = true;
        } elseif (!$this->security->isGranted($model->getPermissionBase($bundle).':view')) {
            return $this->modalAccessDenied();
        } elseif ($model->isLocked($entity)) {
            $viewParams = [
                'page'   => $session->get('mautic.category.page', 1),
                'bundle' => $bundle,
            ];
            $postActionVars = [
                'returnUrl'       => $this->generateUrl('mautic_category_index', $viewParams),
                'viewParameters'  => $viewParams,
                'contentTemplate' => 'Mautic\CategoryBundle\Controller\CategoryController::indexAction',
                'passthroughVars' => [
                    'activeLink'    => 'mautic_'.$bundle.'category_index',
                    'mauticContent' => 'category',
                    'closeModal'    => 1,
                ],
            ];

            return $this->isLocked($postActionVars, $entity, 'category.category');
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
        $form = $model->createForm($entity, $this->formFactory, $action, ['bundle' => $bundle]);
        $form['inForm']->setData($inForm);

        // /Check for a submitted form and process it
        if (!$ignorePost && 'POST' == $method) {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $success = 1;

                    // form is valid so process the data
                    $model->saveEntity($entity, $this->getFormButton($form, ['buttons', 'save'])->isClicked());

                    $this->addFlashMessage(
                        'mautic.category.notice.updated',
                        [
                            '%name%' => $entity->getTitle(),
                        ]
                    );

                    /** @var SubmitButton $applySubmitButton */
                    $applySubmitButton = $form->get('buttons')->get('apply');
                    if ($applySubmitButton->isClicked()) {
                        // Rebuild the form with new action so that apply doesn't keep creating a clone
                        $action = $this->generateUrl(
                            'mautic_category_action',
                            [
                                'objectAction' => 'edit',
                                'objectId'     => $entity->getId(),
                                'bundle'       => $bundle,
                            ]
                        );
                        $form = $model->createForm($entity, $this->formFactory, $action, ['bundle' => $bundle]);
                    }
                }
            } else {
                $success = 1;

                // unlock the entity
                $model->unlockEntity($entity);
            }
        } else {
            // lock the entity
            $model->lockEntity($entity);
        }

        $closeModal = ($closeModal || $cancelled || ($valid && $this->getFormButton($form, ['buttons', 'save'])->isClicked()));

        if ($closeModal) {
            if ($inForm) {
                return new JsonResponse(
                    [
                        'mauticContent' => 'category',
                        'closeModal'    => 1,
                        'inForm'        => 1,
                        'categoryName'  => $entity->getTitle(),
                        'categoryId'    => $entity->getId(),
                    ]
                );
            }

            $viewParameters = [
                'page'   => $session->get('mautic.category.page'),
                'bundle' => $bundle,
            ];

            return $this->postActionRedirect(
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
        } else {
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
    }

    /**
     * Deletes the entity.
     *
     * @return Response
     */
    public function deleteAction(Request $request, $bundle, $objectId)
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
            $model  = $this->getModel('category');
            \assert($model instanceof CategoryModel);
            $entity = $model->getEntity($objectId);

            if (null === $entity) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.category.error.notfound',
                    'msgVars' => ['%id%' => $objectId],
                ];
            } elseif (!$this->security->isGranted($model->getPermissionBase($bundle).':delete')) {
                return $this->accessDenied();
            } elseif ($model->isLocked($entity)) {
                return $this->isLocked($postActionVars, $entity, 'category.category');
            }

            $model->deleteEntity($entity);

            $flashes[] = [
                'type'    => 'notice',
                'msg'     => 'mautic.core.notice.deleted',
                'msgVars' => [
                    '%name%' => $entity->getTitle(),
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
     * @param string $bundle
     *
     * @return Response
     */
    public function batchDeleteAction(Request $request, $bundle)
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
            $model = $this->getModel('category');
            \assert($model instanceof CategoryModel);
            $ids       = json_decode($request->query->get('ids', '{}'));
            $deleteIds = [];

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $model->getEntity($objectId);

                if (null === $entity) {
                    $flashes[] = [
                        'type'    => 'error',
                        'msg'     => 'mautic.category.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ];
                } elseif (!$this->security->isGranted($model->getPermissionBase($bundle).':delete')) {
                    $flashes[] = $this->accessDenied(true);
                } elseif ($model->isLocked($entity)) {
                    $flashes[] = $this->isLocked($postActionVars, $entity, 'category', true);
                } else {
                    $deleteIds[] = $objectId;
                }
            }

            // Delete everything we are able to
            if (!empty($deleteIds)) {
                $entities = $model->deleteEntities($deleteIds);

                $flashes[] = [
                    'type'    => 'notice',
                    'msg'     => 'mautic.category.notice.batch_deleted',
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

    private function getInFormValue(Request $request, string $method): int
    {
        $inForm = $request->get('inForm', 0);
        if (Request::METHOD_POST == $method) {
            $category_form = $request->request->get('category_form');
            $inForm        = $category_form['inForm'] ?? 0;
        }

        return (int) $inForm;
    }
}
