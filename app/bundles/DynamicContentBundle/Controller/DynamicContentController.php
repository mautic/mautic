<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DynamicContentBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractFormController;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\DynamicContentBundle\Model\DynamicContentModel;
use Symfony\Component\HttpFoundation\JsonResponse;

class DynamicContentController extends AbstractFormController
{
    /**
     * @return array
     */
    protected function getPermissions()
    {
        return (array) $this->get('mautic.security')->isGranted(
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

    /**
     * {@inheritdoc}
     */
    public function indexAction($page = 1)
    {
        $model = $this->getModel('dynamicContent');

        $permissions = $this->getPermissions();

        if (!$permissions['dynamiccontent:dynamiccontents:viewown'] && !$permissions['dynamiccontent:dynamiccontents:viewother']) {
            return $this->accessDenied();
        }

        if ($this->request->getMethod() == 'POST') {
            $this->setListFilters();
        }

        //set limits
        $limit = $this->get('session')->get('mautic.dynamicContent.limit', $this->coreParametersHelper->getParameter('default_pagelimit'));
        $start = ($page === 1) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        // fetch
        $search = $this->request->get('search', $this->get('session')->get('mautic.dynamicContent.filter', ''));
        $this->get('session')->set('mautic.dynamicContent.filter', $search);

        $filter = [
            'string' => $search,
            'force'  => [
                ['column' => 'e.variantParent', 'expr' => 'isNull'],
                ['column' => 'e.translationParent', 'expr' => 'isNull'],
            ],
        ];

        $orderBy    = $this->get('session')->get('mautic.dynamicContent.orderby', 'e.name');
        $orderByDir = $this->get('session')->get('mautic.dynamicContent.orderbydir', 'DESC');

        $entities = $model->getEntities(
            [
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir,
            ]
        );

        //set what page currently on so that we can return here after form submission/cancellation
        $this->get('session')->set('mautic.dynamicContent.page', $page);

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        //retrieve a list of categories
        $categories = $this->getModel('page')->getLookupResults('category', '', 0);

        return $this->delegateView(
            [
                'contentTemplate' => 'MauticDynamicContentBundle:DynamicContent:list.html.php',
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

    /**
     * {@inheritdoc}
     */
    public function newAction($entity = null)
    {
        if (!$this->accessGranted('dynamiccontent:dynamiccontents:viewown')) {
            return $this->accessDenied();
        }

        if (!$entity instanceof DynamicContent) {
            $entity = new DynamicContent();
        }

        /** @var \Mautic\DynamicContentBundle\Model\DynamicContentModel $model */
        $model  = $this->getModel('dynamicContent');
        $page   = $this->get('session')->get('mautic.dynamicContent.page', 1);
        $retUrl = $this->generateUrl('mautic_dynamicContent_index', ['page' => $page]);
        $action = $this->generateUrl('mautic_dynamicContent_action', ['objectAction' => 'new']);

        $updateSelect = ($this->request->getMethod() === 'POST')
            ? $this->request->request->get('dwc[updateSelect]', false, true)
            : $this->request->get('updateSelect', false);

        $form = $model->createForm($entity, $this->get('form.factory'), $action, ['update_select' => $updateSelect]);

        if ($this->request->getMethod() === 'POST') {
            $valid = false;

            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $model->saveEntity($entity);

                    $this->addFlash(
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

                    if ($form->get('buttons')->get('save')->isClicked()) {
                        $viewParameters = [
                            'objectAction' => 'view',
                            'objectId'     => $entity->getId(),
                        ];
                        $retUrl   = $this->generateUrl('mautic_dynamicContent_action', $viewParameters);
                        $template = 'MauticDynamicContentBundle:DynamicContent:view';
                    } else {
                        //return edit view so that all the session stuff is loaded
                        return $this->editAction($entity->getId(), true);
                    }
                }
            } else {
                $viewParameters = ['page' => $page];
                $retUrl         = $this->generateUrl('mautic_dynamicContent_index', $viewParameters);
                $template       = 'MauticDynamicContentBundle:DynamicContent:index';
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

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                return $this->postActionRedirect(
                    [
                        'returnUrl'       => $retUrl,
                        'viewParameters'  => $viewParameters,
                        'contentTemplate' => $template,
                        'passthroughVars' => $passthrough,
                    ]
                );
            } elseif ($valid && !$cancelled) {
                return $this->editAction($entity->getId(), true);
            }
        }

        $passthrough['route'] = $action;

        return $this->delegateView(
            [
                'viewParameters' => [
                    'form' => $this->setFormTheme($form, 'MauticDynamicContentBundle:DynamicContent:form.html.php'),
                ],
                'contentTemplate' => 'MauticDynamicContentBundle:DynamicContent:form.html.php',
                'passthroughVars' => $passthrough,
            ]
        );
    }

    /**
     * Generate's edit form and processes post data.
     *
     * @param            $objectId
     * @param bool|false $ignorePost
     *
     * @return array | JsonResponse | RedirectResponse | Response
     */
    public function editAction($objectId, $ignorePost = false)
    {
        /** @var DynamicContentModel $model */
        $model  = $this->getModel('dynamicContent');
        $entity = $model->getEntity($objectId);
        $page   = $this->get('session')->get('mautic.dynamicContent.page', 1);
        $retUrl = $this->generateUrl('mautic_dynamicContent_index', ['page' => $page]);

        $postActionVars = [
            'returnUrl'       => $retUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'MauticDynamicContentBundle:DynamicContent:index',
            'passthroughVars' => [
                'activeLink'    => '#mautic_dynamicContent_index',
                'mauticContent' => 'dynamicContent',
            ],
        ];

        if ($entity === null) {
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
        } elseif (!$this->get('mautic.security')->hasEntityAccess(true, 'dynamiccontent:dynamiccontents:editother', $entity->getCreatedBy())) {
            return $this->accessDenied();
        } elseif ($model->isLocked($entity)) {
            //deny access if the entity is locked
            return $this->isLocked($postActionVars, $entity, 'dynamicContent');
        }

        $action = $this->generateUrl('mautic_dynamicContent_action', ['objectAction' => 'edit', 'objectId' => $objectId]);

        $updateSelect = ($this->request->getMethod() === 'POST')
            ? $this->request->request->get('dwc[updateSelect]', false, true)
            : $this->request->get('updateSelect', false);

        $form = $model->createForm($entity, $this->get('form.factory'), $action, ['update_select' => $updateSelect]);

        ///Check for a submitted form and process it
        if (!$ignorePost && $this->request->getMethod() == 'POST') {
            $valid = false;

            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    //form is valid so process the data
                    $model->saveEntity($entity, $form->get('buttons')->get('save')->isClicked());

                    $this->addFlash(
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
                //unlock the entity
                $model->unlockEntity($entity);
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                return $this->viewAction($entity->getId());
            }
        } else {
            //lock the entity
            $model->lockEntity($entity);
        }

        return $this->delegateView(
            [
                'viewParameters' => [
                    'form'          => $this->setFormTheme($form, 'MauticDynamicContentBundle:DynamicContent:form.html.php'),
                    'currentListId' => $objectId,
                ],
                'contentTemplate' => 'MauticDynamicContentBundle:DynamicContent:form.html.php',
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
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function viewAction($objectId)
    {
        /** @var \Mautic\DynamicContentBundle\Model\DynamicContentModel $model */
        $model    = $this->getModel('dynamicContent');
        $security = $this->get('mautic.security');
        $entity   = $model->getEntity($objectId);

        //set the page we came from
        $page = $this->get('session')->get('mautic.dynamicContent.page', 1);

        if ($entity === null) {
            //set the return URL
            $returnUrl = $this->generateUrl('mautic_dynamicContent_index', ['page' => $page]);

            return $this->postActionRedirect(
                [
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => ['page' => $page],
                    'contentTemplate' => 'MauticDynamicContentBundle:DynamicContent:index',
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
        list($translationParent, $translationChildren) = $entity->getTranslations();

        // Audit Log
        $logs = $this->getModel('core.auditLog')->getLogForObject('dynamicContent', $entity->getId(), $entity->getDateAdded());

        // Init the date range filter form
        $dateRangeValues = $this->request->get('daterange', []);
        $action          = $this->generateUrl('mautic_dynamicContent_action', ['objectAction' => 'view', 'objectId' => $objectId]);
        $dateRangeForm   = $this->get('form.factory')->create('daterange', $dateRangeValues, ['action' => $action]);
        $entityViews     = $model->getHitsLineChartData(
            null,
            new \DateTime($dateRangeForm->get('date_from')->getData()),
            new \DateTime($dateRangeForm->get('date_to')->getData()),
            null,
            ['dynamic_content_id' => $entity->getId(), 'flag' => 'total_and_unique']
        );

        $trackables = $this->getModel('page.trackable')->getTrackableList('dynamicContent', $entity->getId());

        return $this->delegateView(
            [
                'returnUrl'       => $action,
                'contentTemplate' => 'MauticDynamicContentBundle:DynamicContent:details.html.php',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_dynamicContent_index',
                    'mauticContent' => 'dynamicContent',
                ],
                'viewParameters' => [
                    'entity'       => $entity,
                    'permissions'  => $this->getPermissions(),
                    'logs'         => $logs,
                    'isEmbedded'   => $this->request->get('isEmbedded') ? $this->request->get('isEmbedded') : false,
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
     * @param $objectId
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function cloneAction($objectId)
    {
        $model  = $this->getModel('dynamicContent');
        $entity = $model->getEntity($objectId);

        if ($entity != null) {
            if (!$this->get('mautic.security')->isGranted('dynamiccontent:dynamiccontents:create')
                || !$this->get('mautic.security')->hasEntityAccess(
                    'dynamiccontent:dynamiccontents:viewown',
                    'dynamiccontent:dynamiccontents:viewother',
                    $entity->getCreatedBy()
                )
            ) {
                return $this->accessDenied();
            }

            $entity = clone $entity;
        }

        return $this->newAction($entity);
    }

    /**
     * Deletes the entity.
     *
     * @param   $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction($objectId)
    {
        $page      = $this->get('session')->get('mautic.dynamicContent.page', 1);
        $returnUrl = $this->generateUrl('mautic_dynamicContent_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'MauticDynamicContentBundle:DynamicContent:index',
            'passthroughVars' => [
                'activeLink'    => 'mautic_dynamicContent_index',
                'mauticContent' => 'dynamicContent',
            ],
        ];

        if ($this->request->getMethod() == 'POST') {
            $model  = $this->getModel('dynamicContent');
            $entity = $model->getEntity($objectId);

            if ($entity === null) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.dynamicContent.error.notfound',
                    'msgVars' => ['%id%' => $objectId],
                ];
            } elseif (!$this->get('mautic.security')->hasEntityAccess(
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
        } //else don't do anything

        return $this->postActionRedirect(array_merge($postActionVars, ['flashes' => $flashes]));
    }

    /**
     * Deletes a group of entities.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchDeleteAction()
    {
        $page      = $this->get('session')->get('mautic.dynamicContent.page', 1);
        $returnUrl = $this->generateUrl('mautic_dynamicContent_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'MauticDynamicContentBundle:DynamicContent:index',
            'passthroughVars' => [
                'activeLink'    => '#mautic_dynamicContent_index',
                'mauticContent' => 'dynamicContent',
            ],
        ];

        if ($this->request->getMethod() == 'POST') {
            $model = $this->getModel('dynamicContent');
            $ids   = json_decode($this->request->query->get('ids', '{}'));

            $deleteIds = [];

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $model->getEntity($objectId);

                if ($entity === null) {
                    $flashes[] = [
                        'type'    => 'error',
                        'msg'     => 'mautic.dynamicContent.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ];
                } elseif (!$this->get('mautic.security')->hasEntityAccess(
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
        } //else don't do anything

        return $this->postActionRedirect(array_merge($postActionVars, ['flashes' => $flashes]));
    }
}
