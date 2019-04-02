<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Controller;

use Doctrine\ORM\EntityNotFoundException;
use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Model\ListModel;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ListController extends FormController
{
    use EntityContactsTrait;

    /**
     * Generate's default list view.
     *
     * @param int $page
     *
     * @return JsonResponse | Response
     */
    public function indexAction($page = 1)
    {
        /** @var ListModel $model */
        $model   = $this->getModel('lead.list');
        $session = $this->get('session');

        //set some permissions
        $permissions = $this->get('mautic.security')->isGranted([
            'lead:leads:viewown',
            'lead:leads:viewother',
            'lead:lists:viewother',
            'lead:lists:editother',
            'lead:lists:deleteother',
        ], 'RETURN_ARRAY');

        //Lists can be managed by anyone who has access to leads
        if (!$permissions['lead:leads:viewown'] && !$permissions['lead:leads:viewother']) {
            return $this->accessDenied();
        }

        $this->setListFilters();

        //set limits
        $limit = $session->get('mautic.segment.limit', $this->coreParametersHelper->getParameter('default_pagelimit'));
        $start = ($page === 1) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search = $this->request->get('search', $session->get('mautic.segment.filter', ''));
        $session->set('mautic.segment.filter', $search);

        //do some default filtering
        $orderBy    = $session->get('mautic.segment.orderby', 'l.name');
        $orderByDir = $session->get('mautic.segment.orderbydir', 'ASC');

        $filter = [
            'string' => $search,
        ];

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        if (!$permissions['lead:lists:viewother']) {
            $translator      = $this->get('translator');
            $mine            = $translator->trans('mautic.core.searchcommand.ismine');
            $global          = $translator->trans('mautic.lead.list.searchcommand.isglobal');
            $filter['force'] = "($mine or $global)";
        }

        $items = $model->getEntities(
            [
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir,
            ]);

        $count = count($items);

        if ($count && $count < ($start + 1)) {
            //the number of entities are now less then the current page so redirect to the last page
            if ($count === 1) {
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
                'contentTemplate' => 'MauticLeadBundle:List:index',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_segment_index',
                    'mauticContent' => 'leadlist',
                ],
            ]);
        }

        //set what page currently on so that we can return here after form submission/cancellation
        $session->set('mautic.segment.page', $page);

        $listIds    = array_keys($items->getIterator()->getArrayCopy());
        $leadCounts = (!empty($listIds)) ? $model->getRepository()->getLeadCount($listIds) : [];

        $parameters = [
            'items'       => $items,
            'leadCounts'  => $leadCounts,
            'page'        => $page,
            'limit'       => $limit,
            'permissions' => $permissions,
            'security'    => $this->get('mautic.security'),
            'tmpl'        => $tmpl,
            'currentUser' => $this->user,
            'searchValue' => $search,
        ];

        return $this->delegateView([
            'viewParameters'  => $parameters,
            'contentTemplate' => 'MauticLeadBundle:List:list.html.php',
            'passthroughVars' => [
                'activeLink'    => '#mautic_segment_index',
                'route'         => $this->generateUrl('mautic_segment_index', ['page' => $page]),
                'mauticContent' => 'leadlist',
            ],
        ]);
    }

    /**
     * Generate's new form and processes post data.
     *
     * @return JsonResponse | RedirectResponse | Response
     */
    public function newAction()
    {
        if (!$this->get('mautic.security')->isGranted('lead:leads:viewown')) {
            return $this->accessDenied();
        }

        //retrieve the entity
        $list = new LeadList();
        /** @var ListModel $model */
        $model = $this->getModel('lead.list');
        //set the page we came from
        $page = $this->get('session')->get('mautic.segment.page', 1);
        //set the return URL for post actions
        $returnUrl = $this->generateUrl('mautic_segment_index', ['page' => $page]);
        $action    = $this->generateUrl('mautic_segment_action', ['objectAction' => 'new']);

        //get the user form factory
        $form = $model->createForm($list, $this->get('form.factory'), $action);

        ///Check for a submitted form and process it
        if ($this->request->getMethod() == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    //form is valid so process the data
                    $model->saveEntity($list);

                    $this->addFlash('mautic.core.notice.created', [
                        '%name%'      => $list->getName().' ('.$list->getAlias().')',
                        '%menu_link%' => 'mautic_segment_index',
                        '%url%'       => $this->generateUrl('mautic_segment_action', [
                            'objectAction' => 'edit',
                            'objectId'     => $list->getId(),
                        ]),
                    ]);
                }
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                return $this->postActionRedirect([
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => ['page' => $page],
                    'contentTemplate' => 'MauticLeadBundle:List:index',
                    'passthroughVars' => [
                        'activeLink'    => '#mautic_segment_index',
                        'mauticContent' => 'leadlist',
                    ],
                ]);
            } elseif ($valid && !$cancelled) {
                return $this->editAction($list->getId(), true);
            }
        }

        return $this->delegateView([
            'viewParameters' => [
                'form' => $this->setFormTheme($form, 'MauticLeadBundle:List:form.html.php', 'MauticLeadBundle:FormTheme\Filter'),
            ],
            'contentTemplate' => 'MauticLeadBundle:List:form.html.php',
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
    public function cloneAction($objectId, $ignorePost = false)
    {
        $postActionVars = $this->getPostActionVars();

        try {
            $segment = $this->getSegment($objectId);

            return $this->createSegmentModifyResponse(
                clone $segment,
                $postActionVars,
                $this->generateUrl('mautic_segment_action', ['objectAction' => 'clone', 'objectId' => $objectId]),
                $ignorePost
            );
        } catch (AccessDeniedException $exception) {
            return $this->accessDenied();
        } catch (EntityNotFoundException $exception) {
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
    public function editAction($objectId, $ignorePost = false)
    {
        $postActionVars = $this->getPostActionVars($objectId);

        try {
            $segment = $this->getSegment($objectId);

            return $this->createSegmentModifyResponse(
                $segment,
                $postActionVars,
                $this->generateUrl('mautic_segment_action', ['objectAction' => 'edit', 'objectId' => $objectId]),
                $ignorePost
            );
        } catch (AccessDeniedException $exception) {
            return $this->accessDenied();
        } catch (EntityNotFoundException $exception) {
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
     * @param LeadList $segment
     * @param array    $postActionVars
     * @param string   $action
     * @param bool     $ignorePost
     *
     * @return Response
     */
    private function createSegmentModifyResponse(LeadList $segment, array $postActionVars, $action, $ignorePost)
    {
        /** @var ListModel $segmentModel */
        $segmentModel = $this->getModel('lead.list');

        if ($segmentModel->isLocked($segment)) {
            return $this->isLocked($postActionVars, $segment, 'lead.list');
        }

        /** @var FormInterface $form */
        $form = $segmentModel->createForm($segment, $this->get('form.factory'), $action);

        ///Check for a submitted form and process it
        if (!$ignorePost && $this->request->getMethod() == 'POST') {
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($this->isFormValid($form)) {
                    //form is valid so process the data
                    $segmentModel->saveEntity($segment, $form->get('buttons')->get('save')->isClicked());

                    $this->addFlash('mautic.core.notice.updated', [
                        '%name%'      => $segment->getName().' ('.$segment->getAlias().')',
                        '%menu_link%' => 'mautic_segment_index',
                        '%url%'       => $this->generateUrl('mautic_segment_action', [
                            'objectAction' => 'edit',
                            'objectId'     => $segment->getId(),
                        ]),
                    ]);

                    if ($form->get('buttons')->get('apply')->isClicked()) {
                        $contentTemplate                     = 'MauticLeadBundle:List:form.html.php';
                        $postActionVars['contentTemplate']   = $contentTemplate;
                        $postActionVars['forwardController'] = false;
                        $postActionVars['returnUrl']         = $this->generateUrl('mautic_segment_action', [
                            'objectAction' => 'edit',
                            'objectId'     => $segment->getId(),
                        ]);

                        // Re-create the form once more with the fresh segment and action.
                        // The alias was empty on redirect after cloning.
                        $editAction = $this->generateUrl('mautic_segment_action', ['objectAction' => 'edit', 'objectId' => $segment->getId()]);
                        $form       = $segmentModel->createForm($segment, $this->get('form.factory'), $editAction);

                        $postActionVars['viewParameters'] = [
                            'objectAction' => 'edit',
                            'objectId'     => $segment->getId(),
                            'form'         => $this->setFormTheme($form, $contentTemplate, 'MauticLeadBundle:FormTheme\Filter'),
                        ];

                        return $this->postActionRedirect($postActionVars);
                    } else {
                        return $this->viewAction($segment->getId());
                    }
                }
            } else {
                //unlock the entity
                $segmentModel->unlockEntity($segment);
            }

            if ($cancelled) {
                return $this->postActionRedirect($postActionVars);
            }
        } else {
            //lock the entity
            $segmentModel->lockEntity($segment);
        }

        return $this->delegateView([
            'viewParameters' => [
                'form'          => $this->setFormTheme($form, 'MauticLeadBundle:List:form.html.php', 'MauticLeadBundle:FormTheme\Filter'),
                'currentListId' => $segment->getId(),
            ],
            'contentTemplate' => 'MauticLeadBundle:List:form.html.php',
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
        /** @var LeadList $segment */
        $segment = $this->getModel('lead.list')->getEntity($segmentId);

        // Check if exists
        if (!$segment instanceof LeadList) {
            throw new EntityNotFoundException(sprintf('Segment with id %d not found.', $segmentId));
        }

        if (!$this->get('mautic.security')->hasEntityAccess(
            true, 'lead:lists:editother', $segment->getCreatedBy()
        )) {
            throw new AccessDeniedException(sprintf('User has not access on segment with id %d', $segmentId));
        }

        return $segment;
    }

    /**
     * Get variables for POST action.
     *
     * @param null $objectId
     *
     * @return array
     */
    private function getPostActionVars($objectId = null)
    {
        //set the return URL
        if ($objectId) {
            $returnUrl       = $this->generateUrl('mautic_segment_action', ['objectAction' => 'view', 'objectId'=> $objectId]);
            $viewParameters  = ['objectAction' => 'view', 'objectId'=> $objectId];
            $contentTemplate = 'MauticLeadBundle:List:view';
        } else {
            //set the page we came from
            $page            = $this->get('session')->get('mautic.segment.page', 1);
            $returnUrl       = $this->generateUrl('mautic_segment_index', ['page' => $page]);
            $viewParameters  = ['page' => $page];
            $contentTemplate = 'MauticLeadBundle:List:index';
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
     * @param   $objectId
     *
     * @return JsonResponse | RedirectResponse
     */
    public function deleteAction($objectId)
    {
        /** @var ListModel $model */
        $model     = $this->getModel('lead.list');
        $page      = $this->get('session')->get('mautic.segment.page', 1);
        $returnUrl = $this->generateUrl('mautic_segment_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'MauticLeadBundle:List:index',
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

        if ($this->request->getMethod() == 'POST') {
            /** @var ListModel $model */
            $model = $this->getModel('lead.list');
            $list  = $model->getEntity($objectId);

            if ($list === null) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.lead.list.error.notfound',
                    'msgVars' => ['%id%' => $objectId],
                ];
            } elseif (!$this->get('mautic.security')->hasEntityAccess(
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
     * @return JsonResponse | RedirectResponse
     */
    public function batchDeleteAction()
    {
        $page      = $this->get('session')->get('mautic.segment.page', 1);
        $returnUrl = $this->generateUrl('mautic_segment_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'MauticLeadBundle:List:index',
            'passthroughVars' => [
                'activeLink'    => '#mautic_segment_index',
                'mauticContent' => 'lead',
            ],
        ];

        if ($this->request->getMethod() == 'POST') {
            /** @var ListModel $model */
            $model           = $this->getModel('lead.list');
            $ids             = json_decode($this->request->query->get('ids', '{}'));
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

                if ($entity === null) {
                    $flashes[] = [
                        'type'    => 'error',
                        'msg'     => 'mautic.lead.list.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ];
                } elseif (!$this->get('mautic.security')->hasEntityAccess(
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
        } //else don't do anything

        return $this->postActionRedirect(
            array_merge($postActionVars, [
                'flashes' => $flashes,
            ])
        );
    }

    /**
     * @param $objectId
     *
     * @return JsonResponse | RedirectResponse
     */
    public function removeLeadAction($objectId)
    {
        return $this->changeList($objectId, 'remove');
    }

    /**
     * @param $objectId
     *
     * @return JsonResponse | RedirectResponse
     */
    public function addLeadAction($objectId)
    {
        return $this->changeList($objectId, 'add');
    }

    /**
     * @param $listId
     * @param $action
     *
     * @return array | JsonResponse | RedirectResponse
     */
    protected function changeList($listId, $action)
    {
        $page      = $this->get('session')->get('mautic.lead.page', 1);
        $returnUrl = $this->generateUrl('mautic_contact_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'MauticLeadBundle:Lead:index',
            'passthroughVars' => [
                'activeLink'    => '#mautic_contact_index',
                'mauticContent' => 'lead',
            ],
        ];

        $leadId = $this->request->get('leadId');
        if (!empty($leadId) && $this->request->getMethod() == 'POST') {
            /** @var ListModel $model */
            $model = $this->getModel('lead.list');
            /** @var LeadList $list */
            $list = $model->getEntity($listId);
            /** @var LeadModel $leadModel */
            $leadModel = $this->getModel('lead');
            $lead      = $leadModel->getEntity($leadId);

            if ($lead === null) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.lead.lead.error.notfound',
                    'msgVars' => ['%id%' => $listId],
                ];
            } elseif (!$this->get('mautic.security')->hasEntityAccess(
                'lead:leads:editown', 'lead:leads:editother', $lead->getPermissionUser()
            )) {
                return $this->accessDenied();
            } elseif ($list === null) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.lead.list.error.notfound',
                    'msgVars' => ['%id%' => $list->getId()],
                ];
            } elseif (!$list->isGlobal() && !$this->get('mautic.security')->hasEntityAccess(
                    true, 'lead:lists:viewother', $list->getCreatedBy()
                )) {
                return $this->accessDenied();
            } elseif ($model->isLocked($lead)) {
                return $this->isLocked($postActionVars, $lead, 'lead');
            } else {
                $function = ($action == 'remove') ? 'removeLead' : 'addLead';
                $model->$function($lead, $list, true);

                $identifier = $this->get('translator')->trans($lead->getPrimaryIdentifier());
                $flashes[]  = [
                    'type' => 'notice',
                    'msg'  => ($action == 'remove') ? 'mautic.lead.lead.notice.removedfromlists' :
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
        } //else don't do anything

        return $this->postActionRedirect(
            array_merge($postActionVars, [
                'flashes' => $flashes,
            ])
        );
    }

    /**
     * Loads a specific form into the detailed panel.
     *
     * @param $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function viewAction($objectId)
    {
        /** @var \Mautic\LeadBundle\Model\ListModel $model */
        $model    = $this->getModel('lead.list');
        $security = $this->get('mautic.security');

        /** @var \Mautic\LeadBundle\Entity\LeadList $list */
        $list = $model->getEntity($objectId);
        //set the page we came from
        $page = $this->get('session')->get('mautic.segment.page', 1);

        if ($this->request->getMethod() === 'POST' && $this->request->request->has('includeEvents')) {
            $filters = [
                'includeEvents' => InputHelper::clean($this->request->get('includeEvents', [])),
            ];
            $this->get('session')->set('mautic.segment.filters', $filters);
        } else {
            $filters = [];
        }

        if ($list === null) {
            //set the return URL
            $returnUrl = $this->generateUrl('mautic_segment_index', ['page' => $page]);

            return $this->postActionRedirect([
                'returnUrl'       => $returnUrl,
                'viewParameters'  => ['page' => $page],
                'contentTemplate' => 'MauticLeadBundle:List:index',
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
        } elseif (!$this->get('mautic.security')->hasEntityAccess(
            'lead:leads:viewown',
            'lead:lists:viewother',
            $list->getCreatedBy()
        )
        ) {
            return $this->accessDenied();
        }
        $translator      = $this->get('translator');
        $dateRangeValues = $this->request->get('daterange', []);
        $action          = $this->generateUrl('mautic_segment_action', ['objectAction' => 'view', 'objectId' => $objectId]);
        $dateRangeForm   = $this->get('form.factory')->create('daterange', $dateRangeValues, ['action' => $action]);
        $stats           = $this->getModel('lead.list')->getSegmentContactsLineChartData(
            null,
            new \DateTime($dateRangeForm->get('date_from')->getData()),
            new \DateTime($dateRangeForm->get('date_to')->getData()),
            null,
           ['leadlist_id' => ['value'          => $objectId,
                            'list_column_name' => 't.lead_id', ], 't.leadlist_id' => $objectId]
        );

        return $this->delegateView([
            'returnUrl'      => $this->generateUrl('mautic_segment_action', ['objectAction' => 'view', 'objectId' => $list->getId()]),
            'viewParameters' => [
                'list'        => $list,
                'permissions' => $security->isGranted([
                    'lead:leads:editown',
                    'lead:lists:viewother',
                    'lead:lists:editother',
                    'lead:lists:deleteother',
                ], 'RETURN_ARRAY'),
                'security'      => $security,
                'stats'         => $stats,
                'dateRangeForm' => $dateRangeForm->createView(),
                'events'        => [
                    'filters' => $filters,
                    'types'   => [
                        'manually_added'   => $translator->trans('mautic.segment.contact.manually.added'),
                        'manually_removed' => $translator->trans('mautic.segment.contact.manually.removed'),
                        'filter_added'     => $translator->trans('mautic.segment.contact.filter.added'),
                    ],
                ],
                'contacts' => $this->forward(
                    'MauticLeadBundle:List:contacts',
                    [
                        'objectId'   => $list->getId(),
                        'page'       => $this->get('session')->get('mautic.segment.contact.page', 1),
                        'ignoreAjax' => true,
                        'filters'    => $filters,
                    ]
                )->getContent(),
            ],
            'contentTemplate' => 'MauticLeadBundle:List:details.html.php',
            'passthroughVars' => [
                'activeLink'    => '#mautic_segment_index',
                'mauticContent' => 'list',
            ],
        ]);
    }

    /**
     * @param     $objectId
     * @param int $page
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function contactsAction($objectId, $page = 1)
    {
        $manuallyRemoved = 0;
        $listFilters     = ['manually_removed' => $manuallyRemoved];
        if ($this->request->getMethod() === 'POST' && $this->request->request->has('includeEvents')) {
            $filters = [
                'includeEvents' => InputHelper::clean($this->request->get('includeEvents', [])),
            ];
            $this->get('session')->set('mautic.segment.filters', $filters);
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
            $objectId,
            $page,
            'lead:lists:viewother',
            'segment',
            'lead_lists_leads',
            null,
            'leadlist_id',
            $listFilters
        );
    }
}
