<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Controller;

use Doctrine\ORM\EntityNotFoundException;
use Mautic\CoreBundle\Controller\CategoryListFiltersTrait;
use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Controller\QuickFilterSearchTrait;
use Mautic\CoreBundle\Exception\DeleteEntitiesDependencyException;
use Mautic\CoreBundle\Exception\DeleteEntityDependencyException;
use Mautic\CoreBundle\Factory\PageHelperFactoryInterface;
use Mautic\CoreBundle\Form\Type\DateRangeType;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\LeadBundle\Security\Permissions\LeadPermissions;
use Mautic\LeadBundle\Segment\Stat\SegmentCampaignShare;
use Mautic\LeadBundle\Segment\Stat\SegmentDependencies;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Service\Attribute\Required;

final class ListController extends FormController
{
    use CategoryListFiltersTrait;
    use EntityContactsTrait;
    use QuickFilterSearchTrait;

    private LeadListRepository $leadListRepository;

    private ListModel $listModel;

    private LeadModel $leadModel;

    #[Required]
    public function autowireListController(
        LeadModel $leadModel,
        ListModel $listModel,
        LeadListRepository $leadListRepository,
    ): void {
        $this->leadModel = $leadModel;
        $this->listModel = $listModel;
        $this->leadListRepository = $leadListRepository;
    }

    public const ROUTE_SEGMENT_CONTACTS = 'mautic_segment_contacts';

    public const SEGMENT_CONTACT_FIELDS = ['id', 'company', 'city', 'state', 'country'];

    private array $listFilters = [];

    /**
     * Generate's default list view.
     *
     * @param int $page
     *
     * @throws \Exception
     */
    public function indexAction(Request $request, $page = 1): Response
    {
        $session = $request->getSession();

        // set some permissions
        $permissionsToCheck = [
            LeadPermissions::LISTS_VIEW_OWN,
            LeadPermissions::LISTS_VIEW_OTHER,
            LeadPermissions::LISTS_EDIT_OWN,
            LeadPermissions::LISTS_EDIT_OTHER,
            LeadPermissions::LISTS_CREATE,
            LeadPermissions::LISTS_DELETE_OWN,
            LeadPermissions::LISTS_DELETE_OTHER,
            LeadPermissions::LISTS_FULL,
        ];

        $permissions = $this->security->isGranted($permissionsToCheck, 'RETURN_ARRAY');

        // If no permission set to the current user.
        if (!in_array(1, $permissions)) {
            $this->throwAccessDenied();
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

        $tmpl       = $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index';
        $tableAlias = $this->leadListRepository->getTableAlias();

        if (!$permissions[LeadPermissions::LISTS_VIEW_OTHER]) {
            $filter['where'][] = [
                'expr' => 'orX',
                'val'  => [
                    ['column' => $tableAlias.'.createdBy', 'expr' => 'eq', 'value' => $this->user->getId()],
                    ['column' => $tableAlias.'.isGlobal', 'expr' => 'eq', 'value' => 1],
                ],
            ];
        }

        $filter['force'][]   = ['column' => $tableAlias.'.deleted', 'expr' => 'isNull'];
        [$count, $items]     = $this->getIndexItems($start, $limit, $filter, $orderBy, $orderByDir);

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
        $leadCounts = (!empty($listIds)) ? $this->listModel->getSegmentContactCountFromCache($listIds) : [];

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
     */
    public function newAction(Request $request, SegmentDependencies $segmentDependencies, SegmentCampaignShare $segmentCampaignShare, ListModel $listModel, AuditLogModel $auditLogModel): Response
    {
        if (!$this->security->isGranted(LeadPermissions::LISTS_CREATE)) {
            $this->throwAccessDenied();
        }

        // retrieve the entity
        $list = new LeadList();

        return $this->createSegmentNewResponse(
            $request,
            $list,
            $segmentDependencies,
            $segmentCampaignShare,
            $listModel,
            $auditLogModel,
            [],
            $this->generateUrl('mautic_segment_action', ['objectAction' => 'new']),
            false
        );
    }

    /**
     * Generate's clone form and processes post data.
     *
     * @param int  $objectId
     * @param bool $ignorePost
     */
    public function cloneAction(Request $request, SegmentDependencies $segmentDependencies, SegmentCampaignShare $segmentCampaignShare, ListModel $listModel, AuditLogModel $auditLogModel, $objectId, $ignorePost = false): Response
    {
        if (!$this->security->isGranted(LeadPermissions::LISTS_CREATE)) {
            $this->throwAccessDenied();
        }
        $postActionVars = $this->getPostActionVars($request, $objectId);

        try {
            $segment = $this->getSegment((int) $objectId, LeadPermissions::LISTS_VIEW_OWN, LeadPermissions::LISTS_VIEW_OTHER);

            return $this->createSegmentNewResponse(
                $request,
                clone $segment,
                $segmentDependencies,
                $segmentCampaignShare,
                $listModel,
                $auditLogModel,
                $postActionVars,
                $this->generateUrl('mautic_segment_action', ['objectAction' => 'clone', 'objectId' => $objectId]),
                (bool) $ignorePost
            );
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
     */
    public function editAction(Request $request, SegmentDependencies $segmentDependencies, SegmentCampaignShare $segmentCampaignShare, ListModel $listModel, AuditLogModel $auditLogModel, $objectId, $ignorePost = false, bool $isNew = false): Response
    {
        $postActionVars = $this->getPostActionVars($request, $objectId);

        try {
            $segment = $this->getSegment((int) $objectId, LeadPermissions::LISTS_EDIT_OWN, LeadPermissions::LISTS_EDIT_OTHER);

            if ($isNew) {
                $segment->setNew();
            }

            return $this->createSegmentModifyResponse(
                $request,
                $segment,
                $segmentDependencies,
                $segmentCampaignShare,
                $listModel,
                $auditLogModel,
                $postActionVars,
                $this->generateUrl('mautic_segment_action', ['objectAction' => 'edit', 'objectId' => $objectId]),
                $ignorePost
            );
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
     * Return segment if exists and user has access.
     *
     * @throws EntityNotFoundException
     * @throws AccessDeniedException
     */
    private function getSegment(int $segmentId, string $ownPermission, string $otherPermission): LeadList
    {
        $segment = $this->listModel->getEntity($segmentId);

        // Check if exists
        if (!$segment instanceof LeadList) {
            throw new EntityNotFoundException(sprintf('Segment with id %d not found.', $segmentId));
        }

        if (!$this->security->hasEntityAccess(
            $ownPermission, $otherPermission, $segment->getCreatedBy()
        )) {
            throw new AccessDeniedException(sprintf('User has not access on segment with id %d', $segmentId));
        }

        return $segment;
    }

    /**
     * Create new response for segments - new/clone.
     *
     * @param array<string, string> $postActionVars
     */
    private function createSegmentNewResponse(Request $request, LeadList $segment, SegmentDependencies $segmentDependencies, SegmentCampaignShare $segmentCampaignShare, ListModel $segmentModel, AuditLogModel $auditLogModel, array $postActionVars, string $action, bool $ignorePost): Response
    {
        // set the page we came from
        $page = $request->getSession()->get('mautic.segment.page', 1);
        // set the return URL for post actions
        $returnUrl = $this->generateUrl('mautic_segment_index', ['page' => $page]);

        // get the user form factory
        $form = $segmentModel->createForm($segment, $this->formFactory, $action);

        // Check for a submitted form and process it
        if (!$ignorePost && Request::METHOD_POST === $request->getMethod()) {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    // form is valid so process the data
                    $segmentModel->saveEntity($segment);

                    $this->addFlashMessage('mautic.core.notice.created', [
                        '%name%'      => $segment->getName().' ('.$segment->getAlias().')',
                        '%menu_link%' => 'mautic_segment_index',
                        '%url%'       => $this->generateUrl('mautic_segment_action', [
                            'objectAction' => 'edit',
                            'objectId'     => $segment->getId(),
                        ]),
                    ]);
                }
            }

            if ($cancelled || ($valid && $this->getFormButton($form, ['buttons', 'save'])->isClicked())) {
                return $this->postActionRedirect(array_merge($postActionVars, [
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => ['page' => $page],
                    'contentTemplate' => 'Mautic\LeadBundle\Controller\ListController::indexAction',
                    'passthroughVars' => [
                        'activeLink'    => '#mautic_segment_index',
                        'mauticContent' => 'leadlist',
                    ],
                ]));
            }
            if ($valid) {
                return $this->editAction($request, $segmentDependencies, $segmentCampaignShare, $segmentModel, $auditLogModel, $segment->getId(), true);
            }
        }

        return $this->delegateView([
            'viewParameters' => [
                'form' => $form->createView(),
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
     * Create modifying response for segments - edit.
     *
     * @param bool $ignorePost
     *
     * @return Response
     */
    private function createSegmentModifyResponse(Request $request, LeadList $segment, SegmentDependencies $segmentDependencies, SegmentCampaignShare $segmentCampaignShare, ListModel $segmentModel, AuditLogModel $auditLogModel, array $postActionVars, string $action, $ignorePost)
    {
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

                    if ($this->isButtonClicked($form, 'apply')) {
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
                    }

                    return $this->viewAction($request, $segmentDependencies, $segmentCampaignShare, $segmentModel, $auditLogModel, $segment->getId());
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
        $page      = $request->getSession()->get('mautic.segment.page', 1);
        $returnUrl = $this->generateUrl('mautic_segment_index', ['page' => $page]);

        $flashes = [];

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
            $list  = $this->listModel->getEntity($objectId);

            if (null === $list) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.lead.list.error.notfound',
                    'msgVars' => ['%id%' => $objectId],
                ];
            } elseif (!$this->security->hasEntityAccess(
                LeadPermissions::LISTS_DELETE_OWN, LeadPermissions::LISTS_DELETE_OTHER, $list->getCreatedBy()
            )
            ) {
                $this->throwAccessDenied();
            } elseif ($this->listModel->isLocked($list)) {
                return $this->isLocked($postActionVars, $list, 'lead.list');
            } else {
                try {
                    $this->listModel->deleteEntity($list);
                    $flashes[] = [
                        'type'    => 'notice',
                        'msg'     => 'mautic.core.notice.deleted',
                        'msgVars' => [
                            '%name%' => $list->getName(),
                            '%id%'   => $objectId,
                        ],
                    ];
                } catch (DeleteEntityDependencyException $deletedException) {
                    foreach ($deletedException->getErrors() as $error) {
                        $flashes[] = [
                            'type' => 'error',
                            'msg'  => $error,
                        ];
                    }
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
    public function batchDeleteAction(Request $request, ListModel $model): Response
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
            $ids       = json_decode($request->query->get('ids', '{}'));
            $deleteIds = [];

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $model->getEntity($objectId);

                if (null === $entity) {
                    $flashes[] = [
                        'type'    => 'error',
                        'msg'     => 'mautic.lead.list.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ];
                } elseif (!$this->security->hasEntityAccess(
                    LeadPermissions::LISTS_DELETE_OWN, LeadPermissions::LISTS_DELETE_OTHER, $entity->getCreatedBy()
                )) {
                    $flashes[] = $this->getAccessDeniedFlash();
                } elseif ($model->isLocked($entity)) {
                    $flashes[] = $this->isLocked($postActionVars, $entity, 'lead.list', true);
                } else {
                    $deleteIds[] = $objectId;
                }
            }

            if ([] !== $deleteIds) {
                try {
                    $deletedEntities = $model->deleteEntities($deleteIds);
                } catch (DeleteEntitiesDependencyException $e) {
                    $deletedEntities = $e->getDeletedEntities();

                    if ($e->getUnableToDeleteEntities()) {
                        $flashes[] = [
                            'type'    => 'error',
                            'msg'     => 'mautic.lead.list.error.cannot.delete.batch',
                            'msgVars' => [
                                '%segments%' => implode(', ', array_map(fn (LeadList $entity) => $entity->getName(), $e->getUnableToDeleteEntities())),
                            ],
                        ];
                    }
                }

                if ([] !== $deletedEntities) {
                    $flashes[] = [
                        'type'    => 'notice',
                        'msg'     => 'mautic.lead.list.notice.batch_deleted',
                        'msgVars' => [
                            '%count%' => count($deletedEntities),
                        ],
                    ];
                }
            }
        }

        return $this->postActionRedirect(
            array_merge($postActionVars, [
                'flashes' => $flashes,
            ])
        );
    }

    public function removeLeadAction(Request $request, $objectId): Response
    {
        return $this->changeList($request, $objectId, 'remove');
    }

    public function addLeadAction(Request $request, $objectId): Response
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
            /** @var LeadList $list */
            $list = $this->listModel->getEntity($listId);
            $lead      = $this->leadModel->getEntity($leadId);

            if (null === $lead) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.lead.lead.error.notfound',
                    'msgVars' => ['%id%' => $leadId],
                ];
            } elseif (!$this->security->hasEntityAccess(
                LeadPermissions::LISTS_EDIT_OWN, LeadPermissions::LISTS_EDIT_OTHER, $lead->getPermissionUser()
            )) {
                $this->throwAccessDenied();
            } elseif (null === $list) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.lead.list.error.notfound',
                    'msgVars' => ['%id%' => $listId],
                ];
            } elseif (!$list->isGlobal() && !$this->security->hasEntityAccess(
                LeadPermissions::LISTS_VIEW_OWN, LeadPermissions::LISTS_VIEW_OTHER, $list->getCreatedBy()
            )) {
                $this->throwAccessDenied();
            } elseif ($this->listModel->isLocked($lead)) {
                return $this->isLocked($postActionVars, $lead, 'lead');
            } else {
                $function = ('remove' == $action) ? 'removeLead' : 'addLead';
                $this->listModel->{$function}($lead, $list, true);

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
     */
    public function viewAction(Request $request, SegmentDependencies $segmentDependencies, SegmentCampaignShare $segmentCampaignShare, ListModel $listModel, AuditLogModel $auditLogModel, $objectId): Response
    {
        /** @var LeadList $list */
        $list = $listModel->getEntity($objectId);
        // set the page we came from
        $page = $request->getSession()->get('mautic.segment.page', 1);

        if ('POST' === $request->getMethod() && $request->request->has('includeEvents')) {
            $filters = [
                'includeEvents' => InputHelper::clean($request->request->all()['includeEvents'] ?? []),
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
                        'msg'     => 'mautic.lead.list.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ],
                ],
            ]);
        }
        if (!$this->security->hasEntityAccess(
            LeadPermissions::LISTS_VIEW_OWN,
            LeadPermissions::LISTS_VIEW_OTHER,
            $list->getCreatedBy()
        )
        ) {
            $this->throwAccessDenied();
        }

        $dateRangeValues              = $request->query->all()['daterange'] ?? $request->request->all()['daterange'] ?? [];
        $action                       = $this->generateUrl('mautic_segment_action', ['objectAction' => 'view', 'objectId' => $objectId]);
        $dateRangeForm                = $this->formFactory->create(DateRangeType::class, $dateRangeValues, ['action' => $action]);
        $segmentContactsLineChartData = $listModel->getSegmentContactsLineChartData(
            null,
            new \DateTime($dateRangeForm->get('date_from')->getData()),
            new \DateTime($dateRangeForm->get('date_to')->getData()),
            null,
            [
                'leadlist_id' => [
                    'value'            => $objectId,
                    'list_column_name' => 't.lead_id',
                ],
                't.leadlist_id' => $objectId,
            ]
        );

        $permissions = [LeadPermissions::LISTS_CREATE, LeadPermissions::LISTS_VIEW_OWN, LeadPermissions::LISTS_VIEW_OTHER, LeadPermissions::LISTS_EDIT_OWN, LeadPermissions::LISTS_EDIT_OTHER, LeadPermissions::LISTS_DELETE_OWN, LeadPermissions::LISTS_DELETE_OTHER];

        // Audit Log
        $logs = $auditLogModel->getLogForObject('segment', $list->getId(), $list->getDateAdded());

        return $this->delegateView([
            'returnUrl'      => $this->generateUrl('mautic_segment_action', ['objectAction' => 'view', 'objectId' => $list->getId()]),
            'viewParameters' => [
                'logs'               => $logs,
                'usageStats'         => $segmentDependencies->getChannelsIds($list->getId()),
                'campaignStats'      => $segmentCampaignShare->getCampaignList($list->getId()),
                'stats'              => $segmentContactsLineChartData,
                'list'               => $list,
                'segmentCount'       => $this->leadListRepository->getLeadCount($list->getId()),
                'activeSegmentCount' => $listModel->getActiveSegmentContactCount($list->getId()),
                'permissions'        => $this->security->isGranted($permissions, 'RETURN_ARRAY'),
                'security'           => $this->security,
                'dateRangeForm'      => $dateRangeForm->createView(),
                'events'             => [
                    'filters' => $filters,
                    'types'   => [
                        'manually_added'   => $this->translator->trans('mautic.segment.contact.manually.added'),
                        'manually_removed' => $this->translator->trans('mautic.segment.contact.manually.removed'),
                        'filter_added'     => $this->translator->trans('mautic.segment.contact.filter.added'),
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
        return $this->listModel->getPermissionBase();
    }

    protected function getListModel(): ListModel
    {
        return $this->listModel;
    }

    protected function getModelName(): string
    {
        return 'lead.list';
    }

    protected function getIndexItems($start, $limit, $filter, $orderBy, $orderByDir, array $args = []): array
    {
        $request          = $this->getCurrentRequest();
        $filterForceCount = count($filter['force'] ?? []);
        $categoryFilters  = $this->applyCategoryListFilter(
            $request,
            'mautic.lead.list.list_filters',
            ['segment', 'lead.list'],
            'cat.id',
            $filter,
            'mautic.lead.list.source.segment.category',
            'mautic.lead.list.filter.placeholder',
        );
        $filter['string'] = $this->stripQuickFilterTokensFromSearch(
            (string) ($filter['string'] ?? ''),
            $categoryFilters['searchTerms'],
        );
        $joinCategories = count($filter['force'] ?? []) > $filterForceCount;

        // Store for customizeViewArguments
        $this->listFilters = $categoryFilters['filters'];

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
     * @return JsonResponse|RedirectResponse|Response
     */
    public function contactsAction(Request $request, PageHelperFactoryInterface $pageHelperFactory, $objectId, $page = 1)
    {
        $session = $request->getSession();
        $session->set('mautic.segment.contact.page', $page);

        $manuallyRemoved = 0;
        $listFilters     = ['manually_removed' => $manuallyRemoved];
        if ('POST' === $request->getMethod() && $request->request->has('includeEvents')) {
            $filters = [
                'includeEvents' => InputHelper::clean($request->query->all()['includeEvents'] ?? $request->request->all()['includeEvents'] ?? []),
            ];
            $request->getSession()->set('mautic.segment.filters', $filters);
        } else {
            $filters = [];
        }

        if (!empty($filters)) {
            if (in_array('manually_added', $filters['includeEvents'])) {
                $listFilters = array_merge($listFilters, ['manually_added' => 1]);
            }
            if (in_array('manually_removed', $filters['includeEvents'])) {
                $listFilters = array_merge($listFilters, ['manually_removed' => 1]);
            }
            if (in_array('filter_added', $filters['includeEvents'])) {
                $listFilters = array_merge($listFilters, ['manually_added' => 0]);
            }
        }

        return $this->generateContactsGrid(
            $request,
            $pageHelperFactory,
            $objectId,
            $page,
            LeadPermissions::LISTS_VIEW,
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
