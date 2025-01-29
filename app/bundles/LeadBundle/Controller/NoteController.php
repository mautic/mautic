<?php

namespace Mautic\LeadBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\LeadBundle\Entity\LeadNote;
use Mautic\LeadBundle\Model\NoteModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class NoteController extends FormController
{
    use LeadAccessTrait;

    /**
     * Generate's default list view.
     *
     * @return JsonResponse|Response
     */
    public function indexAction(Request $request, $leadId = 0, $page = 1)
    {
        if (empty($leadId)) {
            return $this->accessDenied();
        }

        $lead = $this->checkLeadAccess($leadId, 'view');
        if ($lead instanceof Response) {
            return $lead;
        }

        $this->setListFilters();

        $session = $request->getSession();

        // set limits
        $limit = $session->get(
            'mautic.lead.'.$lead->getId().'.note.limit',
            $this->coreParametersHelper->get('default_pagelimit')
        );
        $start = (1 === $page) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search = $request->get('search', $session->get('mautic.lead.'.$lead->getId().'.note.filter', ''));
        $session->set('mautic.lead.'.$lead->getId().'.note.filter', $search);

        // do some default filtering
        $orderBy    = $session->get('mautic.lead.'.$lead->getId().'.note.orderby', 'n.dateTime');
        $orderByDir = $session->get('mautic.lead.'.$lead->getId().'.note.orderbydir', 'DESC');

        $model = $this->getModel('lead.note');
        $force = [
            [
                'column' => 'n.lead',
                'expr'   => 'eq',
                'value'  => $lead,
            ],
        ];

        $tmpl     = $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index';
        $noteType = InputHelper::clean($request->request->all()['noteTypes'] ?? []);
        if (empty($noteType) && 'index' === $tmpl) {
            $noteType = $session->get('mautic.lead.'.$lead->getId().'.notetype.filter', []);
        }
        $session->set('mautic.lead.'.$lead->getId().'.notetype.filter', $noteType);

        $noteTypes = [
            'general' => 'mautic.lead.note.type.general',
            'email'   => 'mautic.lead.note.type.email',
            'call'    => 'mautic.lead.note.type.call',
            'meeting' => 'mautic.lead.note.type.meeting',
        ];

        if (!empty($noteType)) {
            $force[] = [
                'column' => 'n.type',
                'expr'   => 'in',
                'value'  => $noteType,
            ];
        }

        $items = $model->getEntities(
            [
                'filter' => [
                    'force'  => $force,
                    'string' => $search,
                ],
                'start'          => $start,
                'limit'          => $limit,
                'orderBy'        => $orderBy,
                'orderByDir'     => $orderByDir,
                'hydration_mode' => 'HYDRATE_ARRAY',
            ]
        );

        $security = $this->security;

        return $this->delegateView(
            [
                'viewParameters' => [
                    'notes'       => $items,
                    'lead'        => $lead,
                    'page'        => $page,
                    'limit'       => $limit,
                    'search'      => $search,
                    'noteType'    => $noteType,
                    'noteTypes'   => $noteTypes,
                    'tmpl'        => $tmpl,
                    'permissions' => [
                        'edit'   => $security->hasEntityAccess('lead:leads:editown', 'lead:leads:editother', $lead->getPermissionUser()),
                        'delete' => $security->hasEntityAccess('lead:leads:deleteown', 'lead:leads:deleteown', $lead->getPermissionUser()),
                    ],
                ],
                'passthroughVars' => [
                    'route'         => false,
                    'mauticContent' => 'leadNote',
                    'noteCount'     => count($items),
                ],
                'contentTemplate' => '@MauticLead/Note/list.html.twig',
            ]
        );
    }

    /**
     * Generate's new note and processes post data.
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function newAction(Request $request, $leadId)
    {
        $lead = $this->checkLeadAccess($leadId, 'view');
        if ($lead instanceof Response) {
            return $lead;
        }

        // retrieve the entity
        $note = new LeadNote();
        $note->setLead($lead);

        $model = $this->getModel('lead.note');
        \assert($model instanceof NoteModel);
        $action = $this->generateUrl(
            'mautic_contactnote_action',
            [
                'objectAction' => 'new',
                'leadId'       => $leadId,
            ]
        );
        // get the user form factory
        $form       = $model->createForm($note, $this->formFactory, $action);
        $closeModal = false;
        $valid      = false;
        // /Check for a submitted form and process it
        if (Request::METHOD_POST === $request->getMethod()) {
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $closeModal = true;

                    // form is valid so process the data
                    $model->saveEntity($note);
                }
            } else {
                $closeModal = true;
            }
        }

        $security    = $this->security;
        $permissions = [
            'edit'   => $security->hasEntityAccess('lead:leads:editown', 'lead:leads:editother', $lead->getPermissionUser()),
            'delete' => $security->hasEntityAccess('lead:leads:deleteown', 'lead:leads:deleteown', $lead->getPermissionUser()),
        ];

        if ($closeModal) {
            // just close the modal
            $passthroughVars = [
                'closeModal'    => 1,
                'mauticContent' => 'leadNote',
            ];

            if ($valid && !$cancelled) {
                $passthroughVars['upNoteCount'] = 1;
                $passthroughVars['noteHtml']    = $this->renderView(
                    '@MauticLead/Note/note.html.twig',
                    [
                        'note'        => $note,
                        'lead'        => $lead,
                        'permissions' => $permissions,
                    ]
                );
                $passthroughVars['noteId'] = $note->getId();
            }

            return new JsonResponse($passthroughVars);
        } else {
            return $this->delegateView(
                [
                    'viewParameters' => [
                        'form'        => $form->createView(),
                        'lead'        => $lead,
                        'permissions' => $permissions,
                    ],
                    'contentTemplate' => '@MauticLead/Note/form.html.twig',
                ]
            );
        }
    }

    /**
     * Generate's edit form and processes post data.
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function editAction(Request $request, $leadId, $objectId)
    {
        $lead = $this->checkLeadAccess($leadId, 'view');
        if ($lead instanceof Response) {
            return $lead;
        }

        $model = $this->getModel('lead.note');
        \assert($model instanceof NoteModel);
        $note       = $model->getEntity($objectId);
        $closeModal = false;
        $valid      = false;

        if (null === $note || !$this->security->hasEntityAccess('lead:leads:editown', 'lead:leads:editother', $lead->getPermissionUser())) {
            return $this->accessDenied();
        }

        $action = $this->generateUrl(
            'mautic_contactnote_action',
            [
                'objectAction' => 'edit',
                'objectId'     => $objectId,
                'leadId'       => $leadId,
            ]
        );
        $form = $model->createForm($note, $this->formFactory, $action);

        // /Check for a submitted form and process it
        if (Request::METHOD_POST === $request->getMethod()) {
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    // form is valid so process the data
                    $model->saveEntity($note);
                    $closeModal = true;
                }
            } else {
                $closeModal = true;
            }
        }

        $security    = $this->security;
        $permissions = [
            'edit'   => $security->hasEntityAccess('lead:leads:editown', 'lead:leads:editother', $lead->getPermissionUser()),
            'delete' => $security->hasEntityAccess('lead:leads:deleteown', 'lead:leads:deleteown', $lead->getPermissionUser()),
        ];

        if ($closeModal) {
            // just close the modal
            $passthroughVars['closeModal'] = 1;

            if ($valid && !$cancelled) {
                $passthroughVars['noteHtml'] = $this->renderView(
                    '@MauticLead/Note/note.html.twig',
                    [
                        'note'        => $note,
                        'lead'        => $lead,
                        'permissions' => $permissions,
                    ]
                );
                $passthroughVars['noteId'] = $note->getId();
            }

            $passthroughVars['mauticContent'] = 'leadNote';

            return new JsonResponse($passthroughVars);
        } else {
            return $this->delegateView(
                [
                    'viewParameters' => [
                        'form'        => $form->createView(),
                        'lead'        => $lead,
                        'permissions' => $permissions,
                    ],
                    'contentTemplate' => '@MauticLead/Note/form.html.twig',
                ]
            );
        }
    }

    /**
     * Deletes the entity.
     *
     * @return Response
     */
    public function deleteAction(Request $request, $leadId, $objectId)
    {
        $lead = $this->checkLeadAccess($leadId, 'view');
        if ($lead instanceof Response) {
            return $lead;
        }
        $model = $this->getModel('lead.note');
        \assert($model instanceof NoteModel);
        $note = $model->getEntity($objectId);

        if (null === $note) {
            return $this->notFound();
        }

        if (
            !$this->security->hasEntityAccess('lead:leads:editown', 'lead:leads:editother', $lead->getPermissionUser())
            || $model->isLocked($note)
        ) {
            return $this->accessDenied();
        }

        $model->deleteEntity($note);

        return new JsonResponse(
            [
                'deleteId'      => $objectId,
                'mauticContent' => 'leadNote',
                'downNoteCount' => 1,
            ]
        );
    }

    /**
     * Executes an action defined in route.
     *
     * @param int $objectId
     * @param int $leadId
     *
     * @return Response
     */
    public function executeNoteAction(Request $request, $objectAction, $objectId = 0, $leadId = 0)
    {
        if (method_exists($this, "{$objectAction}Action")) {
            return $this->{"{$objectAction}Action"}($request, $leadId, $objectId);
        } else {
            return $this->accessDenied();
        }
    }
}
