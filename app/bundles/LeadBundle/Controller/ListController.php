<?php

namespace Mautic\LeadBundle\Controller;

use Doctrine\ORM\EntityNotFoundException;
use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Factory\PageHelperFactoryInterface;
use Mautic\CoreBundle\Form\Type\DateRangeType;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\LeadBundle\Segment\Stat\SegmentCampaignShare;
use Mautic\LeadBundle\Segment\Stat\SegmentDependencies;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

class ListController extends FormController
{
    use EntityContactsTrait;

    public const ROUTE_SEGMENT_CONTACTS = 'mautic_segment_contacts';

    public const SEGMENT_CONTACT_FIELDS = ['id', 'company', 'city', 'state', 'country'];

    /**
     * @var array
     */
    protected $listFilters = [];

    /**
     * Generate's default list view.
     *
     * @param int $page
     *
     * @return JsonResponse|Response
     *
     * @throws \Exception
     */
    public function indexAction(Request $request, $page = 1)
    {
        /** @var ListModel $model */
        $model   = $this->getModel('lead.list');
        $session = $request->getSession();

        // set some permissions
        $permissions = $this->security->isGranted([
            'lead:leads:viewown',
            'lead:leads:viewother',
            'lead:lists:viewother',
            'lead:lists:editother',
            'lead:lists:deleteother',
        ], 'RETURN_ARRAY');

        // Lists can be managed by anyone who has access to leads
        if (!$permissions['lead:leads:viewown'] && !$permissions['lead:leads:viewother']) {
            return $this->accessDenied();
        }

        $this->setListFilters();

        // set limits
        $limit = $session->get('mautic.lead.list.limit', $this->coreParametersHelper->get('default_pagelimit'));
        $start = (1 === $page) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search = $request->get('search', $session->get('mautic.segment.filter', ''));
        $session->set('mautic.segment.filter', $search);

        // do some default filtering
        $orderBy    = $session->get('mautic.lead.list.orderby', 'l.dateModified');
        $orderByDir = $session->get('mautic.lead.list.orderbydir', $this->getDefaultOrderDirection());

        $filter = [
            'string' => $search,
        ];

        $tmpl = $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index';

        if (!$permissions['lead:lists:viewother']) {
            $translator      = $this->translator;
            $mine            = $translator->trans('mautic.core.searchcommand.ismine');
            $global          = $translator->trans('mautic.lead.list.searchcommand.isglobal');
            $filter['force'] = "($mine or $global)";
        }

        [$count, $items] = $this->getIndexItems($start, $limit, $filter, $orderBy, $orderByDir);

        if ($count && $count < ($start + 1)) {
            // the number of entities are now less then the current page so redirect to the last page
            if (1 === $count) {
                $lastPage = 1;
            } else {
                $lastPage = (ceil($count / $limit)) ?: 1;
            }
            $session->set('mautic.segment.page', $lastPage);
            $returnUrl = $this->generateUrl('mautic_segment_index', ['page' => $lastPage]);

            return $this->postActionRedirect([
                'returnUrl'      => $returnUrl,
                'viewParameters' => [
                    'page' => $lastPage,
                    'tmpl' => $tmpl,
                ],
                'contentTemplate' => 'Mautic\LeadBundle\Controller\ListController::indexAction',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_segment_index',
                    'mauticContent' => 'leadlist',
                ],
            ]);
        }

        // set what page currently on so that we can return here after form submission/cancellation
        $session->set('mautic.segment.page', $page);

        $listIds    = array_keys($items->getIterator()->getArrayCopy());
        $leadCounts = (!empty($listIds)) ? $model->getSegmentContactCountFromCache($listIds) : [];

        $parameters = [
            'items'                          => $items,
            'leadCounts'                     => $leadCounts,
            'page'                           => $page,
            'limit'                          => $limit,
            'permissions'                    => $permissions,
            'security'                       => $this->security,
            'tmpl'                           => $tmpl,
            'currentUser'                    => $this->user,
            'searchValue'                    => $search,
            'segmentRebuildWarningThreshold' => $this->coreParametersHelper->get('segment_rebuild_time_warning'),
            'segmentBuildWarningThreshold'   => $this->coreParametersHelper->get('segment_build_time_warning'),
        ];

        return $this->delegateView(
            $this->getViewArguments([
                'viewParameters'  => $parameters,
                'contentTemplate' => '@MauticLead/List/list.html.twig',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_segment_index',
                    'route'         => $this->generateUrl('mautic_segment_index', ['page' => $page]),
                    'mauticContent' => 'leadlist',
                ],
            ],
                'index'
            )
        );
    }

    /**
     * Generate's new form and processes post data.
     *
     * @return JsonResponse|RedirectResponse|Response
     */
    public function newAction(Request $request, SegmentDependencies $segmentDependencies, SegmentCampaignShare $segmentCampaignShare)
    {
        if (!$this->security->isGranted('lead:leads:viewown')) {
            return $this->accessDenied();
        }

        // retrieve the entity
        $list = new LeadList();
        /** @var ListModel $model */
        $model = $this->getModel('lead.list');
        // set the page we came from
        $page = $request->getSession()->get('mautic.segment.page', 1);
        // set the return URL for post actions
        $returnUrl = $this->generateUrl('mautic_segment_index', ['page' => $page]);
        $action    = $this->generateUrl('mautic_segment_action', ['objectAction' => 'new']);

        // get the user form factory
        $form = $model->createForm($list, $this->formFactory, $action);

        // /Check for a submitted form and process it
        if ('POST' === $request->getMethod()) {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    // form is valid so process the data
                    $list->setDateModified(new \DateTime());
                    $model->saveEntity($list);

                    $this->addFlashMessage('mautic.core.notice.created', [
                        '%name%'      => $list->getName().' ('.$list->getAlias().')',
                        '%menu_link%' => 'mautic_segment_index',
                        '%url%'       => $this->generateUrl('mautic_segment_action', [
                            'objectAction' => 'edit',
                            'objectId'     => $list->getId(),
                        ]),
                    ]);
                }
            }

            if ($cancelled || ($valid && $this->getFormButton($form, ['buttons', 'save'])->isClicked())) {
                return $this->postActionRedirect([
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => ['page' => $page],
                    'contentTemplate' => 'Mautic\LeadBundle\Controller\ListController::indexAction',
                    'passthroughVars' => [
                        'activeLink'    => '#mautic_segment_index',
                        'mauticContent' => 'leadlist',
                    ],
                ]);
            } elseif ($valid && !$cancelled) {
                return $this->editAction($request, $segmentDependencies, $segmentCampaignShare, $list->getId(), true);
            }
        }

        return $this->delegateView([
            'viewParameters' => [
                'form' => $form->createView(),
            ],
            'contentTemplate' => '@MauticLead/List/form.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_segment_index',
                'route'         => $this->generateUrl('mautic_segment_action', ['objectAction' => 'new']),
                'mauticContent' => 'leadlist',
            ],
        ]);
    }

    /**
     * Generate's clone form and processes post data.
     *
     * @param int  $objectId
     * @param bool $ignorePost
     *
     * @return Response
     */
    public function cloneAction(Request $request, SegmentDependencies $segmentDependencies, SegmentCampaignShare $segmentCampaignShare, $objectId, $ignorePost = false)
    {
        $postActionVars = $this->getPostActionVars($request, $objectId);

        try {
            $segment = $this->getSegment($objectId);

            return $this->createSegmentModifyResponse(
                $request,
                clone $segment,
                $segmentDependencies,
                $segmentCampaignShare,
                $postActionVars,
                $this->generateUrl('mautic_segment_action', ['objectAction' => 'clone', 'objectId' => $objectId]),
                $ignorePost
            );
        } catch (AccessDeniedException) {
            return $this->accessDenied();
        } catch (EntityNotFoundException) {
            return $this->postActionRedirect(
                array_merge($postActionVars, [
                    'flashes' => [
                        [
                            'type'    => 'error',
                            'msg'     => 'mautic.lead.list.error.notfound',
                            'msgVars' => ['%id%' => $objectId],
                        ],
                    ],
                ])
            );
        }
    }

    /**
     * Generate's edit form and processes post data.
     *
     * @param int  $objectId
     * @param bool $ignorePost
     *
     * @return Response
     */
    public function editAction(Request $request, SegmentDependencies $segmentDependencies, SegmentCampaignShare $segmentCampaignShare, $objectId, $ignorePost = false, bool $isNew = false)
    {
        $postActionVars = $this->getPostActionVars($request, $objectId);

        try {
            $segment = $this->getSegment($objectId);

            if ($isNew) {
                $segment->setNew();
            }

            return $this->createSegmentModifyResponse(
                $request,
                $segment,
                $segmentDependencies,
                $segmentCampaignShare,
                $postActionVars,
                $this->generateUrl('mautic_segment_action', ['objectAction' => 'edit', 'objectId' => $objectId]),
                $ignorePost
            );
        } catch (AccessDeniedException) {
            return $this->accessDenied();
        } catch (EntityNotFoundException) {
            return $this->postActionRedirect(
                array_merge($postActionVars, [
                    'flashes' => [
                        [
                            'type'    => 'error',
                            'msg'     => 'mautic.lead.list.error.notfound',
                            'msgVars' => ['%id%' => $objectId],
                        ],
                    ],
                ])
            );
        }
    }

    /**
     * Create modifying response for segments - edit/clone.
     *
     * @param string $action
     * @param bool   $ignorePost
     *
     * @return Response
     */
    private function createSegmentModifyResponse(Request $request, LeadList $segment, SegmentDependencies $segmentDependencies, SegmentCampaignShare $segmentCampaignShare, array $postActionVars, $action, $ignorePost)
    {
        /** @var ListModel $segmentModel */
        $segmentModel = $this->getModel('lead.list');

        if ($segmentModel->isLocked($segment)) {
            return $this->isLocked($postActionVars, $segment, 'lead.list');
        }

        $form = $segmentModel->createForm($segment, $this->formFactory, $action);

        // /Check for a submitted form and process it
        if (!$ignorePost && 'POST' === $request->getMethod()) {
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($this->isFormValid($form)) {
                    // form is valid so process the data
                    $segmentModel->saveEntity($segment, $this->getFormButton($form, ['buttons', 'save'])->isClicked());

                    $this->addFlashMessage('mautic.core.notice.updated', [
                        '%name%'      => $segment->getName().' ('.$segment->getAlias().')',
                        '%menu_link%' => 'mautic_segment_index',
                        '%url%'       => $this->generateUrl('mautic_segment_action', [
                            'objectAction' => 'edit',
                            'objectId'     => $segment->getId(),
                        ]),
                    ]);

                    if ($form->get('buttons')->get('apply')->isClicked()) {
                        $contentTemplate                     = '@MauticLead/List/form.html.twig';
                        $postActionVars['contentTemplate']   = $contentTemplate;
                        $postActionVars['forwardController'] = false;
                        $postActionVars['returnUrl']         = $this->generateUrl('mautic_segment_action', [
                            'objectAction' => 'edit',
                            'objectId'     => $segment->getId(),
                        ]);

                        $form = $segmentModel->createForm($segment, $this->formFactory, $postActionVars['returnUrl']);

                        $postActionVars['viewParameters'] = [
                            'objectAction' => 'edit',
                            'objectId'     => $segment->getId(),
                            'form'         => $form->createView(),
                        ];

                        return $this->postActionRedirect($postActionVars);
                    } else {
                        return $this->viewAction($request, $segmentDependencies, $segmentCampaignShare, $segment->getId());
                    }
                }
            } else {
                // unlock the entity
                $segmentModel->unlockEntity($segment);
            }

            if ($cancelled) {
                return $this->postActionRedirect($postActionVars);
            }
        } else {
            // lock the entity
            $segmentModel->lockEntity($segment);
        }

        return $this->delegateView([
            'viewParameters' => [
                'form'          => $form->createView(),
                'currentListId' => $segment->getId(),
            ],
            'contentTemplate' => '@MauticLead/List/form.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_segment_index',
                'route'         => $action,
                'mauticContent' => 'leadlist',
            ],
        ]);
    }

    /**
     * Return segment if exists and user has access.
     *
     * @param int $segmentId
     *
     * @return LeadList
     *
     * @throws EntityNotFoundException
     * @throws AccessDeniedException
     */
    private function getSegment($segmentId)
    {
        /** @var LeadList|null $segment */
        $segment = $this->getModel('lead.list')->getEntity($segmentId);

        // Check if exists
        if (!$segment) {
            throw new EntityNotFoundException(sprintf('Segment with id %d not found.', $segmentId));
        }

        if (!$this->security->hasEntityAccess(
            true, 'lead:lists:editother', $segment->getCreatedBy()
        )) {
            throw new AccessDeniedException(sprintf('User has not access on segment with id %d', $segmentId));
        }

        return $segment;
    }

    /**
     * Get variables for POST action.
     *
     * @param int|null $objectId
     */
    private function getPostActionVars(Request $request, $objectId = null): array
    {
        // set the return URL
        if ($objectId) {
            $returnUrl       = $this->generateUrl('mautic_segment_action', ['objectAction' => 'view', 'objectId'=> $objectId]);
            $viewParameters  = ['objectAction' => 'view', 'objectId'=> $objectId];
            $contentTemplate = 'Mautic\LeadBundle\Controller\ListController::viewAction';
        } else {
            // set the page we came from
            $page            = $request->getSession()->get('mautic.segment.page', 1);
            $returnUrl       = $this->generateUrl('mautic_segment_index', ['page' => $page]);
            $viewParameters  = ['page' => $page];
            $contentTemplate = 'Mautic\LeadBundle\Controller\ListController::indexAction';
        }

        return [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => $viewParameters,
            'contentTemplate' => $contentTemplate,
            'passthroughVars' => [
                'activeLink'    => '#mautic_segment_index',
                'mauticContent' => 'leadlist',
            ],
        ];
    }

    /**
     * Delete a list.
     *
     * @return Response
     */
    public function deleteAction(Request $request, $objectId)
    {
        /** @var ListModel $model */
        $model     = $this->getModel('lead.list');
        $page      = $request->getSession()->get('mautic.segment.page', 1);
        $returnUrl = $this->generateUrl('mautic_segment_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\LeadBundle\Controller\ListController::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_segment_index',
                'mauticContent' => 'lead',
            ],
        ];

        $dependents = $model->getSegmentsWithDependenciesOnSegment($objectId);

        if (!empty($dependents)) {
            $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.lead.list.error.cannot.delete',
                    'msgVars' => ['%segments%' => implode(', ', $dependents)],
                ];

            return $this->postActionRedirect(
                array_merge($postActionVars, [
                    'flashes' => $flashes,
                ])
            );
        }

        if ('POST' === $request->getMethod()) {
            /** @var ListModel $model */
            $model = $this->getModel('lead.list');
            $list  = $model->getEntity($objectId);

            if (null === $list) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.lead.list.error.notfound',
                    'msgVars' => ['%id%' => $objectId],
                ];
            } elseif (!$this->security->hasEntityAccess(
                true, 'lead:lists:deleteother', $list->getCreatedBy()
            )
            ) {
                return $this->accessDenied();
            } elseif ($model->isLocked($list)) {
                return $this->isLocked($postActionVars, $list, 'lead.list');
            }

            $model->deleteEntity($list);

            $flashes[] = [
                'type'    => 'notice',
                'msg'     => 'mautic.core.notice.deleted',
                'msgVars' => [
                    '%name%' => $list->getName(),
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
     * @return Response
     */
    public function batchDeleteAction(Request $request)
    {
        $page      = $request->getSession()->get('mautic.segment.page', 1);
        $returnUrl = $this->generateUrl('mautic_segment_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\LeadBundle\Controller\ListController::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_segment_index',
                'mauticContent' => 'lead',
            ],
        ];

        if ('POST' === $request->getMethod()) {
            /** @var ListModel $model */
            $model           = $this->getModel('lead.list');
            $ids             = json_decode($request->query->get('ids', '{}'));
            $canNotBeDeleted = $model->canNotBeDeleted($ids);

            if (!empty($canNotBeDeleted)) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.lead.list.error.cannot.delete.batch',
                    'msgVars' => ['%segments%' => implode(', ', $canNotBeDeleted)],
                ];
            }

            $toBeDeleted = array_diff($ids, array_keys($canNotBeDeleted));
            $deleteIds   = [];

            // Loop over the IDs to perform access checks pre-delete
            foreach ($toBeDeleted as $objectId) {
                $entity = $model->getEntity($objectId);

                if (null === $entity) {
                    $flashes[] = [
                        'type'    => 'error',
                        'msg'     => 'mautic.lead.list.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ];
                } elseif (!$this->security->hasEntityAccess(
                    true, 'lead:lists:deleteother', $entity->getCreatedBy()
                )) {
                    $flashes[] = $this->accessDenied(true);
                } elseif ($model->isLocked($entity)) {
                    $flashes[] = $this->isLocked($postActionVars, $entity, 'lead.list', true);
                } else {
                    $deleteIds[] = $objectId;
                }
            }

            // Delete everything we are able to
            if (!empty($deleteIds)) {
                $entities = $model->deleteEntities($deleteIds);

                $flashes[] = [
                    'type'    => 'notice',
                    'msg'     => 'mautic.lead.list.notice.batch_deleted',
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

    /**
     * @return Response
     */
    public function removeLeadAction(Request $request, $objectId)
    {
        return $this->changeList($request, $objectId, 'remove');
    }

    /**
     * @return Response
     */
    public function addLeadAction(Request $request, $objectId)
    {
        return $this->changeList($request, $objectId, 'add');
    }

    /**
     * @return Response
     */
    protected function changeList(Request $request, $listId, $action)
    {
        $page      = $request->getSession()->get('mautic.lead.page', 1);
        $returnUrl = $this->generateUrl('mautic_contact_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\LeadBundle\Controller\LeadController::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_contact_index',
                'mauticContent' => 'lead',
            ],
        ];

        $leadId = $request->get('leadId');
        if (!empty($leadId) && 'POST' === $request->getMethod()) {
            /** @var ListModel $model */
            $model = $this->getModel('lead.list');
            /** @var LeadList $list */
            $list = $model->getEntity($listId);
            /** @var LeadModel $leadModel */
            $leadModel = $this->getModel('lead');
            $lead      = $leadModel->getEntity($leadId);

            if (null === $lead) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.lead.lead.error.notfound',
                    'msgVars' => ['%id%' => $listId],
                ];
            } elseif (!$this->security->hasEntityAccess(
                'lead:leads:editown', 'lead:leads:editother', $lead->getPermissionUser()
            )) {
                return $this->accessDenied();
            } elseif (null === $list) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.lead.list.error.notfound',
                    'msgVars' => ['%id%' => $list->getId()],
                ];
            } elseif (!$list->isGlobal() && !$this->security->hasEntityAccess(
                true, 'lead:lists:viewother', $list->getCreatedBy()
            )) {
                return $this->accessDenied();
            } elseif ($model->isLocked($lead)) {
                return $this->isLocked($postActionVars, $lead, 'lead');
            } else {
                $function = ('remove' == $action) ? 'removeLead' : 'addLead';
                $model->$function($lead, $list, true);

                $identifier = $this->translator->trans($lead->getPrimaryIdentifier());
                $flashes[]  = [
                    'type' => 'notice',
                    'msg'  => ('remove' == $action) ? 'mautic.lead.lead.notice.removedfromlists' :
                        'mautic.lead.lead.notice.addedtolists',
                    'msgVars' => [
                        '%name%' => $identifier,
                        '%id%'   => $leadId,
                        '%list%' => $list->getName(),
                        '%url%'  => $this->generateUrl('mautic_contact_action', [
                            'objectAction' => 'edit',
                            'objectId'     => $leadId,
                        ]),
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

    /**
     * Loads a specific form into the detailed panel.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function viewAction(Request $request, SegmentDependencies $segmentDependencies, SegmentCampaignShare $segmentCampaignShare, $objectId)
    {
        /** @var \Mautic\LeadBundle\Model\ListModel $model */
        $model    = $this->getModel('lead.list');
        $security = $this->security;

        /** @var LeadList $list */
        $list = $model->getEntity($objectId);
        // set the page we came from
        $page = $request->getSession()->get('mautic.segment.page', 1);

        if ('POST' === $request->getMethod() && $request->request->has('includeEvents')) {
            $filters = [
                'includeEvents' => InputHelper::clean($request->get('includeEvents', [])),
            ];
            $request->getSession()->set('mautic.segment.filters', $filters);
        } else {
            $filters = [];
        }

        if (null === $list) {
            // set the return URL
            $returnUrl = $this->generateUrl('mautic_segment_index', ['page' => $page]);

            return $this->postActionRedirect([
                'returnUrl'       => $returnUrl,
                'viewParameters'  => ['page' => $page],
                'contentTemplate' => 'Mautic\LeadBundle\Controller\ListController::indexAction',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_segment_index',
                    'mauticContent' => 'list',
                ],
                'flashes' => [
                    [
                        'type'    => 'error',
                        'msg'     => 'mautic.list.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ],
                ],
            ]);
        } elseif (!$this->security->hasEntityAccess(
            'lead:leads:viewown',
            'lead:lists:viewother',
            $list->getCreatedBy()
        )
        ) {
            return $this->accessDenied();
        }
        /** @var TranslatorInterface $translator */
        $translator = $this->translator;
        /** @var ListModel $listModel */
        $listModel                    = $this->getModel('lead.list');
        $dateRangeValues              = $request->get('daterange', []);
        $action                       = $this->generateUrl('mautic_segment_action', ['objectAction' => 'view', 'objectId' => $objectId]);
        $dateRangeForm                = $this->formFactory->create(DateRangeType::class, $dateRangeValues, ['action' => $action]);
        $segmentContactsLineChartData = $listModel->getSegmentContactsLineChartData(
            null,
            new \DateTime($dateRangeForm->get('date_from')->getData()),
            new \DateTime($dateRangeForm->get('date_to')->getData()),
            null,
            [
                'leadlist_id'   => [
                    'value'            => $objectId,
                    'list_column_name' => 't.lead_id',
                ],
                't.leadlist_id' => $objectId,
            ]
        );

        return $this->delegateView([
            'returnUrl'      => $this->generateUrl('mautic_segment_action', ['objectAction' => 'view', 'objectId' => $list->getId()]),
            'viewParameters' => [
                'usageStats'     => $segmentDependencies->getChannelsIds($list->getId()),
                'campaignStats'  => $segmentCampaignShare->getCampaignList($list->getId()),
                'stats'          => $segmentContactsLineChartData,
                'list'           => $list,
                'segmentCount'   => $listModel->getRepository()->getLeadCount($list->getId()),
                'permissions'    => $security->isGranted([
                    'lead:leads:editown',
                    'lead:lists:viewother',
                    'lead:lists:editother',
                    'lead:lists:deleteother',
                ], 'RETURN_ARRAY'),
                'security'      => $security,
                'dateRangeForm' => $dateRangeForm->createView(),
                'events'        => [
                    'filters' => $filters,
                    'types'   => [
                        'manually_added'   => $translator->trans('mautic.segment.contact.manually.added'),
                        'manually_removed' => $translator->trans('mautic.segment.contact.manually.removed'),
                        'filter_added'     => $translator->trans('mautic.segment.contact.filter.added'),
                    ],
                ],
            ],
            'contentTemplate' => '@MauticLead/List/details.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_segment_index',
                'mauticContent' => 'list',
            ],
        ]);
    }

    /**
     * Get the permission base from the model.
     */
    protected function getPermissionBase(): string
    {
        return $this->getModel('lead.list')->getPermissionBase();
    }

    /**
     * Get List Model.
     */
    protected function getListModel(): ListModel
    {
        /** @var ListModel $model */
        $model = $this->getModel('lead.list');

        return $model;
    }

    protected function getModelName(): string
    {
        return 'lead.list';
    }

    protected function getIndexItems($start, $limit, $filter, $orderBy, $orderByDir, array $args = []): array
    {
        $request = $this->getCurrentRequest();
        \assert(null !== $request);
        $session        = $request->getSession();
        $currentFilters = $session->get('mautic.lead.list.list_filters', []);
        $updatedFilters = $request->get('filters', false);

        $sourceLists = $this->getListModel()->getSourceLists();
        $listFilters = [
            'filters' => [
                'placeholder' => $this->translator->trans('mautic.lead.list.filter.placeholder'),
                'multiple'    => true,
                'groups'      => [
                    'mautic.lead.list.source.segment.category' => [
                        'options' => $sourceLists['categories'],
                        'prefix'  => 'category',
                    ],
                ],
            ],
        ];

        if ($updatedFilters) {
            // Filters have been updated

            // Parse the selected values
            $newFilters     = [];
            $updatedFilters = json_decode($updatedFilters, true);

            if ($updatedFilters) {
                foreach ($updatedFilters as $updatedFilter) {
                    [$clmn, $fltr] = explode(':', $updatedFilter);

                    $newFilters[$clmn][] = $fltr;
                }

                $currentFilters = $newFilters;
            } else {
                $currentFilters = [];
            }
        }
        $session->set('mautic.lead.list.list_filters', $currentFilters);

        $joinCategories = false;
        if (!empty($currentFilters)) {
            $catIds = [];
            foreach ($currentFilters as $type => $typeFilters) {
                $listFilters['filters']['groups']['mautic.lead.list.source.segment.'.$type]['values'] = $typeFilters;

                foreach ($typeFilters as $fltr) {
                    if ('category' == $type) {
                        $catIds[] = (int) $fltr;
                    } // else for other group filters
                }
            }

            if (!empty($catIds)) {
                $joinCategories    = true;
                $filter['force'][] = ['column' => 'cat.id', 'expr' => 'in', 'value' => $catIds];
            }
        }

        // Store for customizeViewArguments
        $this->listFilters = $listFilters;

        $request = $this->getCurrentRequest();
        \assert(null !== $request);

        return parent::getIndexItems(
            $start,
            $limit,
            $filter,
            $orderBy,
            $orderByDir,
            [
                'joinCategories' => $joinCategories,
            ]
        );
    }

    public function getViewArguments(array $args, $action): array
    {
        switch ($action) {
            case 'index':
                $args['viewParameters']['filters'] = $this->listFilters;
                break;
        }

        return $args;
    }

    /**
     * @param int $objectId
     * @param int $page
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function contactsAction(Request $request, PageHelperFactoryInterface $pageHelperFactory, $objectId, $page = 1)
    {
        $session = $request->getSession();
        \assert($session instanceof SessionInterface);
        $session->set('mautic.segment.contact.page', $page);

        $manuallyRemoved = 0;
        $listFilters     = ['manually_removed' => $manuallyRemoved];
        if ('POST' === $request->getMethod() && $request->request->has('includeEvents')) {
            $filters = [
                'includeEvents' => InputHelper::clean($request->get('includeEvents', [])),
            ];
            $request->getSession()->set('mautic.segment.filters', $filters);
        } else {
            $filters = [];
        }

        if (!empty($filters)) {
            if (isset($filters['includeEvents']) && in_array('manually_added', $filters['includeEvents'])) {
                $listFilters = array_merge($listFilters, ['manually_added' => 1]);
            }
            if (isset($filters['includeEvents']) && in_array('manually_removed', $filters['includeEvents'])) {
                $listFilters = array_merge($listFilters, ['manually_removed' => 1]);
            }
            if (isset($filters['includeEvents']) && in_array('filter_added', $filters['includeEvents'])) {
                $listFilters = array_merge($listFilters, ['manually_added' => 0]);
            }
        }

        return $this->generateContactsGrid(
            $request,
            $pageHelperFactory,
            $objectId,
            $page,
            ['lead:leads:viewother', 'lead:leads:viewown'],
            'segment',
            'lead_lists_leads',
            null,
            'leadlist_id',
            $listFilters
        );
    }

    protected function getDefaultOrderDirection(): string
    {
        return 'DESC';
    }
}
