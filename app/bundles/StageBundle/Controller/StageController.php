<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\StageBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractFormController;
use Mautic\CoreBundle\Factory\PageHelperFactoryInterface;
use Mautic\StageBundle\Entity\Stage;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class StageController extends AbstractFormController
{
    /**
     * @param int $page
     *
     * @return JsonResponse|Response
     */
    public function indexAction($page = 1)
    {
        //set some permissions
        $permissions = $this->get('mautic.security')->isGranted(
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

        /** @var PageHelperFactoryInterface $pageHelperFacotry */
        $pageHelperFacotry = $this->get('mautic.page.helper.factory');
        $pageHelper        = $pageHelperFacotry->make('mautic.stage', $page);

        $limit      = $pageHelper->getLimit();
        $start      = $pageHelper->getStart();
        $search     = $this->request->get('search', $this->get('session')->get('mautic.stage.filter', ''));
        $filter     = ['string' => $search, 'force' => []];
        $orderBy    = $this->get('session')->get('mautic.stage.orderby', 's.name');
        $orderByDir = $this->get('session')->get('mautic.stage.orderbydir', 'ASC');
        $stages     = $this->getModel('stage')->getEntities(
            [
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir,
            ]
        );

        $this->get('session')->set('mautic.stage.filter', $search);

        $count = count($stages);
        if ($count && $count < ($start + 1)) {
            $lastPage  = $pageHelper->countPage($count);
            $returnUrl = $this->generateUrl('mautic_stage_index', ['page' => $lastPage]);
            $pageHelper->rememberPage($lastPage);

            return $this->postActionRedirect(
                [
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => ['page' => $lastPage],
                    'contentTemplate' => 'MauticStageBundle:Stage:index',
                    'passthroughVars' => [
                        'activeLink'    => '#mautic_stage_index',
                        'mauticContent' => 'stage',
                    ],
                ]
            );
        }

        $pageHelper->rememberPage($page);

        //get the list of actions
        $actions = $this->getModel('stage')->getStageActions();

        return $this->delegateView(
            [
                'viewParameters' => [
                    'searchValue' => $search,
                    'items'       => $stages,
                    'actions'     => $actions['actions'],
                    'page'        => $page,
                    'limit'       => $limit,
                    'permissions' => $permissions,
                    'tmpl'        => $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index',
                ],
                'contentTemplate' => 'MauticStageBundle:Stage:list.html.php',
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
    public function newAction($entity = null)
    {
        $model = $this->getModel('stage');

        if (!($entity instanceof Stage)) {
            /** @var \Mautic\StageBundle\Entity\Stage $entity */
            $entity = $model->getEntity();
        }

        if (!$this->get('mautic.security')->isGranted('stage:stages:create')) {
            return $this->accessDenied();
        }

        //set the page we came from
        $page       = $this->get('session')->get('mautic.stage.page', 1);
        $method     = $this->request->getMethod();
        $stage      = $this->request->request->get('stage', []);
        $actionType = 'POST' === $method ? ($stage['type'] ?? '') : '';
        $action     = $this->generateUrl('mautic_stage_action', ['objectAction' => 'new']);
        $actions    = $model->getStageActions();
        $form       = $model->createForm(
            $entity,
            $this->get('form.factory'),
            $action,
            [
                'stageActions' => $actions,
                'actionType'   => $actionType,
            ]
        );
        $viewParameters = ['page' => $page];

        ///Check for a submitted form and process it
        if ('POST' === $method) {
            $valid = false;

            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    //form is valid so process the data
                    $model->saveEntity($entity);

                    $this->addFlash(
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

                    if ($form->get('buttons')->get('save')->isClicked()) {
                        $returnUrl = $this->generateUrl('mautic_stage_index', $viewParameters);
                        $template  = 'MauticStageBundle:Stage:index';
                    } else {
                        //return edit view so that all the session stuff is loaded
                        return $this->editAction($entity->getId(), true);
                    }
                }
            } else {
                $returnUrl = $this->generateUrl('mautic_stage_index', $viewParameters);
                $template  = 'MauticStageBundle:Stage:index';
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
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
                    'tmpl'    => $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index',
                    'entity'  => $entity,
                    'form'    => $this->setFormTheme($form, 'MauticStageBundle:Stage:form.html.php', $themes),
                    'actions' => $actions['actions'],
                ],
                'contentTemplate' => 'MauticStageBundle:Stage:form.html.php',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_stage_index',
                    'mauticContent' => 'stage',
                    'route'         => $this->generateUrl(
                        'mautic_stage_action',
                        [
                            'objectAction' => (!empty($valid) ? 'edit' : 'new'), //valid means a new form was applied
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
    public function editAction($objectId, $ignorePost = false)
    {
        $model  = $this->getModel('stage');
        $entity = $model->getEntity($objectId);

        //set the page we came from
        $page = $this->get('session')->get('mautic.stage.page', 1);

        $viewParameters = ['page' => $page];

        //set the return URL
        $returnUrl = $this->generateUrl('mautic_stage_index', ['page' => $page]);

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => $viewParameters,
            'contentTemplate' => 'MauticStageBundle:Stage:index',
            'passthroughVars' => [
                'activeLink'    => '#mautic_stage_index',
                'mauticContent' => 'stage',
            ],
        ];

        //form not found
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
        } elseif (!$this->get('mautic.security')->isGranted('stage:stages:edit')) {
            return $this->accessDenied();
        } elseif ($model->isLocked($entity)) {
            //deny access if the entity is locked
            return $this->isLocked($postActionVars, $entity, 'stage');
        }

        $actionType = 'moved to stage';

        $action  = $this->generateUrl('mautic_stage_action', ['objectAction' => 'edit', 'objectId' => $objectId]);
        $actions = $model->getStageActions();
        $form    = $model->createForm(
            $entity,
            $this->get('form.factory'),
            $action,
            [
                'stageActions' => $actions,
                'actionType'   => $actionType,
            ]
        );

        ///Check for a submitted form and process it
        if (!$ignorePost && 'POST' == $this->request->getMethod()) {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    //form is valid so process the data
                    $model->saveEntity($entity, $form->get('buttons')->get('save')->isClicked());

                    $this->addFlash(
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

                    if ($form->get('buttons')->get('save')->isClicked()) {
                        $returnUrl = $this->generateUrl('mautic_stage_index', $viewParameters);
                        $template  = 'MauticStageBundle:Stage:index';
                    }
                }
            } else {
                //unlock the entity
                $model->unlockEntity($entity);

                $returnUrl = $this->generateUrl('mautic_stage_index', $viewParameters);
                $template  = 'MauticStageBundle:Stage:index';
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
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
            //lock the entity
            $model->lockEntity($entity);
        }

        $themes = ['MauticStageBundle:FormTheme\Action'];
        if (!empty($actions['actions'][$actionType]['formTheme'])) {
            $themes[] = $actions['actions'][$actionType]['formTheme'];
        }

        return $this->delegateView(
            [
                'viewParameters' => [
                    'tmpl'    => $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index',
                    'entity'  => $entity,
                    'form'    => $this->setFormTheme($form, 'MauticStageBundle:Stage:form.html.php', $themes),
                    'actions' => $actions['actions'],
                ],
                'contentTemplate' => 'MauticStageBundle:Stage:form.html.php',
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
    public function cloneAction($objectId)
    {
        $model  = $this->getModel('stage');
        $entity = $model->getEntity($objectId);

        if (null != $entity) {
            if (!$this->get('mautic.security')->isGranted('stage:stages:create')) {
                return $this->accessDenied();
            }

            $entity = clone $entity;
            $entity->setIsPublished(false);
        }

        return $this->newAction($entity);
    }

    /**
     * Deletes the entity.
     *
     * @param int $objectId
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction($objectId)
    {
        $page      = $this->get('session')->get('mautic.stage.page', 1);
        $returnUrl = $this->generateUrl('mautic_stage_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'MauticStageBundle:Stage:index',
            'passthroughVars' => [
                'activeLink'    => '#mautic_stage_index',
                'mauticContent' => 'stage',
            ],
        ];

        if ('POST' == $this->request->getMethod()) {
            $model  = $this->getModel('stage');
            $entity = $model->getEntity($objectId);

            if (null === $entity) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.stage.error.notfound',
                    'msgVars' => ['%id%' => $objectId],
                ];
            } elseif (!$this->get('mautic.security')->isGranted('stage:stages:delete')) {
                return $this->accessDenied();
            } elseif ($model->isLocked($entity)) {
                return $this->isLocked($postActionVars, $entity, 'stage');
            }

            $model->deleteEntity($entity);

            $identifier = $this->get('translator')->trans($entity->getName());
            $flashes[]  = [
                'type'    => 'notice',
                'msg'     => 'mautic.core.notice.deleted',
                'msgVars' => [
                    '%name%' => $identifier,
                    '%id%'   => $objectId,
                ],
            ];
        } //else don't do anything

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
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchDeleteAction()
    {
        $page      = $this->get('session')->get('mautic.stage.page', 1);
        $returnUrl = $this->generateUrl('mautic_stage_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'MauticStageBundle:Stage:index',
            'passthroughVars' => [
                'activeLink'    => '#mautic_stage_index',
                'mauticContent' => 'stage',
            ],
        ];

        if ('POST' == $this->request->getMethod()) {
            $model     = $this->getModel('stage');
            $ids       = json_decode($this->request->query->get('ids', '{}'));
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
                } elseif (!$this->get('mautic.security')->isGranted('stage:stages:delete')) {
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
        } //else don't do anything

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
