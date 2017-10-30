<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CategoryBundle\Controller;

use Mautic\CategoryBundle\CategoryEvents;
use Mautic\CategoryBundle\Event\CategoryTypesEvent;
use Mautic\CategoryBundle\Model\CategoryModel;
use Mautic\CoreBundle\Controller\FormController;
use Symfony\Component\HttpFoundation\JsonResponse;

class CategoryController extends FormController
{
    /**
     * @param        $bundle
     * @param        $objectAction
     * @param int    $objectId
     * @param string $objectModel
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function executeCategoryAction($bundle, $objectAction, $objectId = 0, $objectModel = '')
    {
        if (method_exists($this, "{$objectAction}Action")) {
            return $this->{"{$objectAction}Action"}($bundle, $objectId, $objectModel);
        } else {
            return $this->accessDenied();
        }
    }

    /**
     * @param     $bundle
     * @param int $page
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($bundle, $page = 1)
    {
        $session = $this->get('session');

        $search = $this->request->get('search', $session->get('mautic.category.filter', ''));
        $bundle = $this->request->get('bundle', $session->get('mautic.category.type', ''));

        if ($bundle) {
            $session->set('mautic.category.type', $bundle);
        }

        // hack to make pagination work for default list view
        if ($bundle == 'all') {
            $bundle = 'category';
        }

        $session->set('mautic.category.filter', $search);

        //set some permissions
        $permissionBase = $this->getModel('category')->getPermissionBase($bundle);
        $permissions    = $this->get('mautic.security')->isGranted(
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

        if ($this->request->getMethod() == 'POST') {
            $this->setListFilters();
        }

        $viewParams = [
            'page'   => $page,
            'bundle' => $bundle,
        ];

        //set limits
        $limit = $session->get('mautic.category.limit', $this->coreParametersHelper->getParameter('default_pagelimit'));
        $start = ($page === 1) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $filter = ['string' => $search];

        if ($bundle != 'category') {
            $filter['force'] = [
                [
                    'column' => 'c.bundle',
                    'expr'   => 'eq',
                    'value'  => $bundle,
                ],
            ];
        }

        $orderBy    = $this->get('session')->get('mautic.category.orderby', 'c.title');
        $orderByDir = $this->get('session')->get('mautic.category.orderbydir', 'DESC');

        $entities = $this->getModel('category')->getEntities(
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
            //the number of entities are now less then the current page so redirect to the last page
            if ($count === 1) {
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
                    'contentTemplate' => 'MauticCategoryBundle:Category:index',
                    'passthroughVars' => [
                        'activeLink'    => '#mautic_'.$bundle.'category_index',
                        'mauticContent' => 'category',
                    ],
                ]
            );
        }

        $categoryTypes = ['category' => $this->get('translator')->trans('mautic.core.select')];

        $dispatcher = $this->dispatcher;
        if ($dispatcher->hasListeners(CategoryEvents::CATEGORY_ON_BUNDLE_LIST_BUILD)) {
            $event = new CategoryTypesEvent();
            $dispatcher->dispatch(CategoryEvents::CATEGORY_ON_BUNDLE_LIST_BUILD, $event);
            $categoryTypes = array_merge($categoryTypes, $event->getCategoryTypes());
        }

        //set what page currently on so that we can return here after form submission/cancellation
        $session->set('mautic.category.page', $page);

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

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
                'contentTemplate' => 'MauticCategoryBundle:Category:list.html.php',
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
    public function newAction($bundle)
    {
        $session    = $this->get('session');
        $model      = $this->getModel('category');
        $entity     = $model->getEntity();
        $success    = $closeModal    = 0;
        $cancelled  = $valid  = false;
        $method     = $this->request->getMethod();
        $inForm     = ($method == 'POST') ? $this->request->request->get('category_form[inForm]', 0, true) : $this->request->get('inForm', 0);
        $showSelect = $this->request->get('show_bundle_select', false);

        //not found
        if (!$this->get('mautic.security')->isGranted($model->getPermissionBase($bundle).':create')) {
            return $this->modalAccessDenied();
        }
        //Create the form
        $action = $this->generateUrl('mautic_category_action', [
            'objectAction' => 'new',
            'bundle'       => $bundle,
        ]);
        $form = $model->createForm($entity, $this->get('form.factory'), $action, ['bundle' => $bundle, 'show_bundle_select' => $showSelect]);
        $form['inForm']->setData($inForm);
        ///Check for a submitted form and process it
        if ($method == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $success = 1;

                    //form is valid so process the data
                    $model->saveEntity($entity, $form->get('buttons')->get('save')->isClicked());

                    $this->addFlash('mautic.category.notice.created', [
                        '%name%' => $entity->getName(),
                    ]);
                }
            } else {
                $success = 1;
            }
        }

        $closeModal = ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked()));

        if ($closeModal) {
            if ($inForm) {
                return new JsonResponse([
                    'mauticContent' => 'category',
                    'closeModal'    => 1,
                    'inForm'        => 1,
                    'categoryName'  => $entity->getName(),
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
                'contentTemplate' => 'MauticCategoryBundle:Category:index',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_'.$bundle.'category_index',
                    'mauticContent' => 'category',
                    'closeModal'    => 1,
                ],
            ]);
        } elseif (!empty($valid)) {
            //return edit view to prevent duplicates
            return $this->editAction($bundle, $entity->getId(), true);
        } else {
            return $this->ajaxAction([
                'contentTemplate' => 'MauticCategoryBundle:Category:form.html.php',
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
            ]);
        }
    }

    /**
     * Generates edit form and processes post data.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction($bundle, $objectId, $ignorePost = false)
    {
        $session = $this->get('session');
        /** @var CategoryModel $model */
        $model     = $this->getModel('category');
        $entity    = $model->getEntity($objectId);
        $success   = $closeModal   = 0;
        $cancelled = $valid = false;
        $method    = $this->request->getMethod();
        $inForm    = ($method == 'POST') ? $this->request->request->get('category_form[inForm]', 0, true) : $this->request->get('inForm', 0);
        //not found
        if ($entity === null) {
            $closeModal = true;
        } elseif (!$this->get('mautic.security')->isGranted($model->getPermissionBase($bundle).':view')) {
            return $this->modalAccessDenied();
        } elseif ($model->isLocked($entity)) {
            return $this->modalAccessDenied();
        }

        //Create the form
        $action = $this->generateUrl(
            'mautic_category_action',
            [
                'objectAction' => 'edit',
                'objectId'     => $objectId,
                'bundle'       => $bundle,
            ]
        );
        $form = $model->createForm($entity, $this->get('form.factory'), $action, ['bundle' => $bundle]);
        $form['inForm']->setData($inForm);

        ///Check for a submitted form and process it
        if (!$ignorePost && $method == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $success = 1;

                    //form is valid so process the data
                    $model->saveEntity($entity, $form->get('buttons')->get('save')->isClicked());

                    $this->addFlash(
                        'mautic.category.notice.updated',
                        [
                            '%name%' => $entity->getTitle(),
                        ]
                    );

                    if ($form->get('buttons')->get('apply')->isClicked()) {
                        // Rebuild the form with new action so that apply doesn't keep creating a clone
                        $action = $this->generateUrl(
                            'mautic_category_action',
                            [
                                'objectAction' => 'edit',
                                'objectId'     => $entity->getId(),
                                'bundle'       => $bundle,
                            ]
                        );
                        $form = $model->createForm($entity, $this->get('form.factory'), $action, ['bundle' => $bundle]);
                    }
                }
            } else {
                $success = 1;

                //unlock the entity
                $model->unlockEntity($entity);
            }
        } else {
            //lock the entity
            $model->lockEntity($entity);
        }

        $closeModal = ($closeModal || $cancelled || ($valid && $form->get('buttons')->get('save')->isClicked()));

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
                    'contentTemplate' => 'MauticCategoryBundle:Category:index',
                    'passthroughVars' => [
                        'activeLink'    => '#mautic_'.$bundle.'category_index',
                        'mauticContent' => 'category',
                        'closeModal'    => 1,
                    ],
                ]
            );
        } else {
            return $this->ajaxAction(
                [
                    'contentTemplate' => 'MauticCategoryBundle:Category:form.html.php',
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
     * @param   $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction($bundle, $objectId)
    {
        $session    = $this->get('session');
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
            'contentTemplate' => 'MauticCategoryBundle:Category:index',
            'passthroughVars' => [
                'activeLink'    => 'mautic_'.$bundle.'category_index',
                'mauticContent' => 'category',
            ],
        ];

        if ($this->request->getMethod() == 'POST') {
            $model  = $this->getModel('category');
            $entity = $model->getEntity($objectId);

            if ($entity === null) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.category.error.notfound',
                    'msgVars' => ['%id%' => $objectId],
                ];
            } elseif (!$this->get('mautic.security')->isGranted($model->getPermissionBase($bundle).':delete')) {
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
        } //else don't do anything

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
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchDeleteAction($bundle)
    {
        $session    = $this->get('session');
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
            'contentTemplate' => 'MauticCategoryBundle:Category:index',
            'passthroughVars' => [
                'activeLink'    => 'mautic_'.$bundle.'category_index',
                'mauticContent' => 'category',
            ],
        ];

        if ($this->request->getMethod() == 'POST') {
            $model     = $this->getModel('category');
            $ids       = json_decode($this->request->query->get('ids', '{}'));
            $deleteIds = [];

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $model->getEntity($objectId);

                if ($entity === null) {
                    $flashes[] = [
                        'type'    => 'error',
                        'msg'     => 'mautic.category.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ];
                } elseif (!$this->get('mautic.security')->isGranted($model->getPermissionBase($bundle).':delete')) {
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
        } //else don't do anything

        return $this->postActionRedirect(
            array_merge($postActionVars, [
                'flashes' => $flashes,
            ])
        );
    }
}
