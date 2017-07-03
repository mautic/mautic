<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticMessengerBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Exception as MauticException;
use Joomla\Http\Http;
use Symfony\Component\HttpFoundation\RequestStack;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use MauticPlugin\MauticMessengerBundle\Helper\MessengerHelper;

class MessengerController extends FormController
{

    /**
     * @return array
     */
    protected function getPermissions()
    {
        return (array) $this->get('mautic.security')->isGranted(
            [
                'messenger:messages:viewown',
                'messenger:messages:viewother',
                'messenger:messages:create',
                'messenger:messages:editown',
                'messenger:messages:editother',
                'messenger:messages:deleteown',
                'messenger:messages:deleteother',
                'messenger:messages:publishown',
                'messenger:messages:publishother',
            ],
            'RETURN_ARRAY'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function indexAction($page = 1)
    {
        $model = $this->getModel('messengerMessage');

        $permissions = $this->getPermissions();

        if (!$permissions['messenger:messages:viewown'] && !$permissions['messenger:messages:viewother']) {
            return $this->accessDenied();
        }

        if ($this->request->getMethod() == 'POST') {
            $this->setListFilters();
        }

        //set limits
        $limit = $this->get('session')->get('mautic.messenger.limit', $this->coreParametersHelper->getParameter('default_pagelimit'));
        $start = ($page === 1) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        // fetch
        $search = $this->request->get('search', $this->get('session')->get('mautic.messenger.filter', ''));
        $this->get('session')->set('mautic.messenger.filter', $search);

        $filter = [
            'string' => $search,
            'force'  => [
                ['column' => 'e.variantParent', 'expr' => 'isNull'],
                ['column' => 'e.translationParent', 'expr' => 'isNull'],
            ],
        ];

        $orderBy    = $this->get('session')->get('mautic.messenger.orderby', 'e.name');
        $orderByDir = $this->get('session')->get('mautic.messenger.orderbydir', 'DESC');

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
        $this->get('session')->set('mautic.messenger.page', $page);

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        //retrieve a list of categories
        $categories = $this->getModel('page')->getLookupResults('category', '', 0);

        return $this->delegateView(
            [
                'contentTemplate' => 'MauticMessengerBundle:Messenger:list.html.php',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_messenger_index',
                    'mauticContent' => 'messengerMessage',
                    'route'         => $this->generateUrl('mautic_messenger_index', ['page' => $page]),
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
        if (!$this->accessGranted('messenger:messages:viewown')) {
            return $this->accessDenied();
        }

        if (!$entity instanceof MessengerMessage) {
            $entity = new MessengerMessage();
        }

        /** @var \MauticPlugin\MauticMessengerBundle\Model\MessengerMessageModel $model */
        $model  = $this->getModel('messengerMessage');
        $page   = $this->get('session')->get('mautic.messenger.page', 1);
        $retUrl = $this->generateUrl('mautic_messenger_index', ['page' => $page]);
        $action = $this->generateUrl('mautic_messenger_action', ['objectAction' => 'new']);

        $updateSelect = ($this->request->getMethod() === 'POST')
            ? $this->request->request->get('msg[updateSelect]', false, true)
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
                            '%menu_link%' => 'mautic_messenger_index',
                            '%url%'       => $this->generateUrl(
                                'mautic_messenger_action',
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
                        $retUrl   = $this->generateUrl('mautic_messenger_action', $viewParameters);
                        $template = 'MauticMessengerBundle:Messenger:view';
                    } else {
                        //return edit view so that all the session stuff is loaded
                        return $this->editAction($entity->getId(), true);
                    }
                }
            } else {
                $viewParameters = ['page' => $page];
                $retUrl         = $this->generateUrl('mautic_messenger_index', $viewParameters);
                $template       = 'MauticMessengerBundle:Messenger:index';
            }

            $passthrough = [
                'activeLink'    => '#mautic_messenger_index',
                'mauticContent' => 'messengerMessage',
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
                    'form' => $this->setFormTheme($form, 'MauticMessengerBundle:Messenger:form.html.php'),
                ],
                'contentTemplate' => 'MauticMessengerBundle:Messenger:form.html.php',
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
        /** @var MessengerMessageModel $model */
        $model  = $this->getModel('messengerMessage');
        $entity = $model->getEntity($objectId);
        $page   = $this->get('session')->get('mautic.messenger.page', 1);
        $retUrl = $this->generateUrl('mautic_messenger_index', ['page' => $page]);

        $postActionVars = [
            'returnUrl'       => $retUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'MauticMessengerBundle:Messenger:index',
            'passthroughVars' => [
                'activeLink'    => '#mautic_messenger_index',
                'mauticContent' => 'messengerMessage',
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
                                'msg'     => 'mautic.messenger.error.notfound',
                                'msgVars' => ['%id%' => $objectId],
                            ],
                        ],
                    ]
                )
            );
        } elseif (!$this->get('mautic.security')->hasEntityAccess(true, 'messenger:messages:editother', $entity->getCreatedBy())) {
            return $this->accessDenied();
        } elseif ($model->isLocked($entity)) {
            //deny access if the entity is locked
            return $this->isLocked($postActionVars, $entity, 'messengerMessage');
        }

        $action = $this->generateUrl('mautic_messenger_action', ['objectAction' => 'edit', 'objectId' => $objectId]);

        $updateSelect = ($this->request->getMethod() === 'POST')
            ? $this->request->request->get('msg[updateSelect]', false, true)
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
                            '%menu_link%' => 'mautic_messenger_index',
                            '%url%'       => $this->generateUrl(
                                'mautic_messenger_action',
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
                    'form'          => $this->setFormTheme($form, 'MauticMessengerBundle:Messenger:form.html.php'),
                    'currentListId' => $objectId,
                ],
                'contentTemplate' => 'MauticMessengerBundle:Messenger:form.html.php',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_messenger_index',
                    'route'         => $action,
                    'mauticContent' => 'messengerMessage',
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
        /** @var \MauticPlugin\MauticMessengerBundle\Model\MessengerMessageModel $model */
        $model    = $this->getModel('messengerMessage');
        $security = $this->get('mautic.security');
        $entity   = $model->getEntity($objectId);

        //set the page we came from
        $page = $this->get('session')->get('mautic.messenger.page', 1);

        if ($entity === null) {
            //set the return URL
            $returnUrl = $this->generateUrl('mautic_messenger_index', ['page' => $page]);

            return $this->postActionRedirect(
                [
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => ['page' => $page],
                    'contentTemplate' => 'MauticMessengerBundle:Messenger:index',
                    'passthroughVars' => [
                        'activeLink'    => '#mautic_messenger_index',
                        'mauticContent' => 'messengerMessage',
                    ],
                    'flashes' => [
                        [
                            'type'    => 'error',
                            'msg'     => 'mautic.messenger.error.notfound',
                            'msgVars' => ['%id%' => $objectId],
                        ],
                    ],
                ]
            );
        } elseif (!$security->hasEntityAccess(
            'messenger:messages:viewown',
            'messenger:messages:viewother',
            $entity->getCreatedBy()
        )
        ) {
            return $this->accessDenied();
        }

        /* @var MessengerMessage $parent */
        /* @var MessengerMessage[] $children */
        list($translationParent, $translationChildren) = $entity->getTranslations();

        // Audit Log
        $logs = $this->getModel('core.auditLog')->getLogForObject('messengerMessage', $entity->getId(), $entity->getDateAdded());

        // Init the date range filter form
        $dateRangeValues = $this->request->get('daterange', []);
        $action          = $this->generateUrl('mautic_messenger_action', ['objectAction' => 'view', 'objectId' => $objectId]);
        $dateRangeForm   = $this->get('form.factory')->create('daterange', $dateRangeValues, ['action' => $action]);
        $entityViews     = $model->getHitsLineChartData(
            null,
            new \DateTime($dateRangeForm->get('date_from')->getData()),
            new \DateTime($dateRangeForm->get('date_to')->getData()),
            null,
            ['dynamic_content_id' => $entity->getId(), 'flag' => 'total_and_unique']
        );

        $trackables = $this->getModel('page.trackable')->getTrackableList('messengerMessage', $entity->getId());

        return $this->delegateView(
            [
                'returnUrl'       => $action,
                'contentTemplate' => 'MauticMessengerBundle:Messenger:details.html.php',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_messenger_index',
                    'mauticContent' => 'messengerMessage',
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
        $model  = $this->getModel('messengerMessage');
        $entity = $model->getEntity($objectId);

        if ($entity != null) {
            if (!$this->get('mautic.security')->isGranted('messenger:messages:create')
                || !$this->get('mautic.security')->hasEntityAccess(
                    'messenger:messages:viewown',
                    'messenger:messages:viewother',
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
        $page      = $this->get('session')->get('mautic.messenger.page', 1);
        $returnUrl = $this->generateUrl('mautic_messenger_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'MauticMessengerBundle:Messenger:index',
            'passthroughVars' => [
                'activeLink'    => 'mautic_messenger_index',
                'mauticContent' => 'messengerMessage',
            ],
        ];

        if ($this->request->getMethod() == 'POST') {
            $model  = $this->getModel('messengerMessage');
            $entity = $model->getEntity($objectId);

            if ($entity === null) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.messenger.error.notfound',
                    'msgVars' => ['%id%' => $objectId],
                ];
            } elseif (!$this->get('mautic.security')->hasEntityAccess(
                'messenger:messages:deleteown',
                'messenger:messages:deleteother',
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
        $page      = $this->get('session')->get('mautic.messenger.page', 1);
        $returnUrl = $this->generateUrl('mautic_messenger_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'MauticMessengerBundle:Messenger:index',
            'passthroughVars' => [
                'activeLink'    => '#mautic_messenger_index',
                'mauticContent' => 'messengerMessage',
            ],
        ];

        if ($this->request->getMethod() == 'POST') {
            $model = $this->getModel('messengerMessage');
            $ids   = json_decode($this->request->query->get('ids', '{}'));

            $deleteIds = [];

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $model->getEntity($objectId);

                if ($entity === null) {
                    $flashes[] = [
                        'type'    => 'error',
                        'msg'     => 'mautic.messenger.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ];
                } elseif (!$this->get('mautic.security')->hasEntityAccess(
                    'messenger:messages:viewown',
                    'messenger:messages:viewother',
                    $entity->getCreatedBy()
                )
                ) {
                    $flashes[] = $this->accessDenied(true);
                } elseif ($model->isLocked($entity)) {
                    $flashes[] = $this->isLocked($postActionVars, $entity, 'messengerMessage', true);
                } else {
                    $deleteIds[] = $objectId;
                }
            }

            // Delete everything we are able to
            if (!empty($deleteIds)) {
                $entities = $model->deleteEntities($deleteIds);

                $flashes[] = [
                    'type'    => 'notice',
                    'msg'     => 'mautic.messenger.notice.batch_deleted',
                    'msgVars' => [
                        '%count%' => count($entities),
                    ],
                ];
            }
        } //else don't do anything

        return $this->postActionRedirect(array_merge($postActionVars, ['flashes' => $flashes]));
    }
    
    /**
     * @return Response
     */
    public function callbackAction()
    {
        $verify_token = "bot_app";
        $hub_verify_token = null;
        if (isset($_REQUEST['hub_challenge'])) {
            $challenge = $_REQUEST['hub_challenge'];
            $hub_verify_token = $_REQUEST['hub_verify_token'];
            if ($hub_verify_token === $verify_token) {
                return new Response($challenge);
            }
        }

    }

    public function checkboxAction()
    {
        $content = $this->get('mautic.plugin.helper.messenger')->getTemplateContent();
        return empty($content) ? new Response('', Response::HTTP_NO_CONTENT) : new Response($content);
    }

    public function checkboxJsAction()
    {
        $content = $this->get('mautic.plugin.helper.messenger')->getTemplateContent(
            'MauticMessengerBundle:Plugin:checkbox_plugin_js.html.php'
        );
        return empty($content) ? new Response('', Response::HTTP_NO_CONTENT) : new Response($content);

    }


}
