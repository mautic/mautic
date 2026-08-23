<?php

namespace Mautic\LeadBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\LeadBundle\Entity\LeadNote;
use Mautic\LeadBundle\Model\NoteModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Service\Attribute\Required;

final class NoteController extends FormController
{
    use LeadAccessTrait;

    private NoteModel $noteModel;

    #[Required]
    public function autowireNoteController(
        NoteModel $noteModel,
    ): void {
        $this->noteModel = $noteModel;
    }

    /**
     * Generate's default list view.
     */
    #[Route('/s/contacts/notes/{leadId}/{page}', name: 'mautic_contactnote_index', requirements: ['leadId' => '\d+', 'page' => '\d+'], defaults: ['leadId' => 0, 'page' => 0])]
    public function indexAction(Request $request, NoteModel $model, int $leadId = 0, int $page = 1): Response
    {
        if (empty($leadId)) {
            $this->throwAccessDenied();
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

        $viewPermissions = $this->security->isGranted(['lead:notes:viewown', 'lead:notes:viewother'], 'RETURN_ARRAY');
        $canViewOwn      = $viewPermissions['lead:notes:viewown'] ?? false;
        $canViewOther    = $viewPermissions['lead:notes:viewother'] ?? false;
        $canViewNotes    = $canViewOwn || $canViewOther;

        $items           = [];
        if ($canViewNotes) {
            if (!$canViewOther) {
                $force[] = [
                    'column' => 'n.createdBy',
                    'expr'   => 'eq',
                    'value'  => $this->user?->getId(),
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
        }

        $notePermissions = [];
        foreach ($items as &$item) {
            $permissionUser      = $item['createdBy'] ?? null;
            $itemId              = (int) ($item['id'] ?? 0);
            $notePermission      = [
                'edit'   => $this->security->hasEntityAccess('lead:notes:editown', 'lead:notes:editother', $permissionUser),
                'delete' => $this->security->hasEntityAccess('lead:notes:deleteown', 'lead:notes:deleteother', $permissionUser),
            ];
            $item['permissions'] = $notePermission;
            if ($itemId > 0) {
                $notePermissions[$itemId] = $notePermission;
            }
        }
        unset($item);

        return $this->delegateView(
            [
                'viewParameters' => [
                    'notes'           => $items,
                    'lead'            => $lead,
                    'page'            => $page,
                    'limit'           => $limit,
                    'search'          => $search,
                    'noteType'        => $noteType,
                    'noteTypes'       => $noteTypes,
                    'tmpl'            => $tmpl,
                    'permissions'     => [
                        'create' => $this->security->isGranted('lead:notes:create'),
                        'edit'   => $this->security->isGranted(['lead:notes:editown', 'lead:notes:editother'], 'MATCH_ONE'),
                        'delete' => $this->security->isGranted(['lead:notes:deleteown', 'lead:notes:deleteother'], 'MATCH_ONE'),
                    ],
                    'notePermissions' => $notePermissions,
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
     */
    public function newAction(Request $request, $leadId): Response|JsonResponse
    {
        $lead = $this->checkLeadAccess($leadId, 'view');
        if ($lead instanceof Response) {
            return $lead;
        }
        if (!$this->security->isGranted('lead:notes:create')) {
            $this->throwAccessDenied();
        }

        // retrieve the entity
        $note = new LeadNote();
        $note->setLead($lead);
        $action = $this->generateUrl(
            'mautic_contactnote_action',
            [
                'objectAction' => 'new',
                'leadId'       => $leadId,
            ]
        );
        // get the user form factory
        $form       = $this->noteModel->createForm($note, $action);
        $closeModal = false;
        $valid      = false;
        // /Check for a submitted form and process it
        if (Request::METHOD_POST === $request->getMethod()) {
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $closeModal = true;

                    // form is valid so process the data
                    $this->noteModel->saveEntity($note);
                }
            } else {
                $closeModal = true;
            }
        }

        $security    = $this->security;
        $permissions = [
            'edit'   => $security->hasEntityAccess('lead:notes:editown', 'lead:notes:editother', $note->getCreatedBy()),
            'delete' => $security->hasEntityAccess('lead:notes:deleteown', 'lead:notes:deleteother', $note->getCreatedBy()),
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

                $this->addFlashMessage('mautic.lead.note.created');
            }

            $passthroughVars['flashes'] = $this->getFlashContent();

            return new JsonResponse($passthroughVars);
        }

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

    /**
     * Generate's edit form and processes post data.
     */
    public function editAction(Request $request, $leadId, $objectId): Response|JsonResponse
    {
        $lead = $this->checkLeadAccess($leadId, 'view');
        if ($lead instanceof Response) {
            return $lead;
        }
        $note       = $this->noteModel->getEntity($objectId);
        $closeModal = false;
        $valid      = false;

        if (null === $note || !$this->security->hasEntityAccess('lead:notes:editown', 'lead:notes:editother', $note->getCreatedBy())) {
            $this->throwAccessDenied();
        }

        $action = $this->generateUrl(
            'mautic_contactnote_action',
            [
                'objectAction' => 'edit',
                'objectId'     => $objectId,
                'leadId'       => $leadId,
            ]
        );
        $form = $this->noteModel->createForm($note, $action);

        // /Check for a submitted form and process it
        if (Request::METHOD_POST === $request->getMethod()) {
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    // form is valid so process the data
                    $this->noteModel->saveEntity($note);
                    $closeModal = true;
                }
            } else {
                $closeModal = true;
            }
        }

        $security    = $this->security;
        $permissions = [
            'edit'   => $security->hasEntityAccess('lead:notes:editown', 'lead:notes:editother', $note->getCreatedBy()),
            'delete' => $security->hasEntityAccess('lead:notes:deleteown', 'lead:notes:deleteother', $note->getCreatedBy()),
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
        }

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

    /**
     * Deletes the entity.
     */
    public function deleteAction(Request $request, $leadId, $objectId): Response|JsonResponse
    {
        $lead = $this->checkLeadAccess($leadId, 'view');
        if ($lead instanceof Response) {
            return $lead;
        }
        $note = $this->noteModel->getEntity($objectId);

        if (null === $note) {
            return $this->notFound();
        }

        if (
            !$this->security->hasEntityAccess('lead:notes:deleteown', 'lead:notes:deleteother', $note->getCreatedBy())
            || $this->noteModel->isLocked($note)
        ) {
            $this->throwAccessDenied();
        }

        $this->noteModel->deleteEntity($note);

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
     */
    #[Route('/s/contacts/notes/{leadId}/{objectAction}/{objectId}', name: 'mautic_contactnote_action', requirements: ['leadId' => '\d+', 'objectId' => '[a-zA-Z0-9_-]+'], defaults: ['objectId' => 0], priority: -1)]
    public function executeNoteAction(Request $request, $objectAction, $objectId = 0, $leadId = 0): Response
    {
        if (method_exists($this, "{$objectAction}Action")) {
            return $this->{"{$objectAction}Action"}($request, $leadId, $objectId);
        }

        return $this->notFound();
    }
}
