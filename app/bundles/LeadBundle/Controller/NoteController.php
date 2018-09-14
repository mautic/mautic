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

use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\LeadBundle\Entity\LeadNote;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class NoteController extends FormController
{
    use LeadAccessTrait;

    /**
     * Generate's default list view.
     *
     * @param $leadId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($leadId = 0, $page = 1)
    {
        if (empty($leadId)) {
            return $this->accessDenied();
        }

        $lead = $this->checkLeadAccess($leadId, 'view');
        if ($lead instanceof Response) {
            return $lead;
        }

        $this->setListFilters();

        $session = $this->get('session');

        //set limits
        $limit = $session->get(
            'mautic.lead.'.$lead->getId().'.note.limit',
            $this->get('mautic.helper.core_parameters')->getParameter('default_pagelimit')
        );
        $start = ($page === 1) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search = $this->request->get('search', $session->get('mautic.lead.'.$lead->getId().'.note.filter', ''));
        $session->set('mautic.lead.'.$lead->getId().'.note.filter', $search);

        //do some default filtering
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

        $tmpl     = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';
        $noteType = InputHelper::clean($this->request->request->get('noteTypes', [], true));
        if (empty($noteType) && $tmpl == 'index') {
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

        $security = $this->get('mautic.security');

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
                'contentTemplate' => 'MauticLeadBundle:Note:list.html.php',
            ]
        );
    }

    /**
     * Generate's new note and processes post data.
     *
     * @param $leadId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction($leadId)
    {
        $lead = $this->checkLeadAccess($leadId, 'view');
        if ($lead instanceof Response) {
            return $lead;
        }

        //retrieve the entity
        $note = new LeadNote();
        $note->setLead($lead);

        $model  = $this->getModel('lead.note');
        $action = $this->generateUrl(
            'mautic_contactnote_action',
            [
                'objectAction' => 'new',
                'leadId'       => $leadId,
            ]
        );
        //get the user form factory
        $form       = $model->createForm($note, $this->get('form.factory'), $action);
        $closeModal = false;
        $valid      = false;
        ///Check for a submitted form and process it
        if ($this->request->getMethod() == 'POST') {
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $closeModal = true;

                    //form is valid so process the data
                    $model->saveEntity($note);
                }
            } else {
                $closeModal = true;
            }
        }

        $security    = $this->get('mautic.security');
        $permissions = [
            'edit'   => $security->hasEntityAccess('lead:leads:editown', 'lead:leads:editother', $lead->getPermissionUser()),
            'delete' => $security->hasEntityAccess('lead:leads:deleteown', 'lead:leads:deleteown', $lead->getPermissionUser()),
        ];

        if ($closeModal) {
            //just close the modal
            $passthroughVars = [
                'closeModal'    => 1,
                'mauticContent' => 'leadNote',
            ];

            if ($valid && !$cancelled) {
                $passthroughVars['upNoteCount'] = 1;
                $passthroughVars['noteHtml']    = $this->renderView(
                    'MauticLeadBundle:Note:note.html.php',
                    [
                        'note'        => $note,
                        'lead'        => $lead,
                        'permissions' => $permissions,
                    ]
                );
                $passthroughVars['noteId'] = $note->getId();
            }

            $response = new JsonResponse($passthroughVars);

            return $response;
        } else {
            return $this->delegateView(
                [
                    'viewParameters' => [
                        'form'        => $form->createView(),
                        'lead'        => $lead,
                        'permissions' => $permissions,
                    ],
                    'contentTemplate' => 'MauticLeadBundle:Note:form.html.php',
                ]
            );
        }
    }

    /**
     * Generate's edit form and processes post data.
     *
     * @param $leadId
     * @param $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function editAction($leadId, $objectId)
    {
        $lead = $this->checkLeadAccess($leadId, 'view');
        if ($lead instanceof Response) {
            return $lead;
        }

        $model      = $this->getModel('lead.note');
        $note       = $model->getEntity($objectId);
        $closeModal = false;
        $valid      = false;

        if ($note === null || !$this->get('mautic.security')->hasEntityAccess('lead:leads:editown', 'lead:leads:editother', $lead->getPermissionUser())) {
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
        $form = $model->createForm($note, $this->get('form.factory'), $action);

        ///Check for a submitted form and process it
        if ($this->request->getMethod() == 'POST') {
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    //form is valid so process the data
                    $model->saveEntity($note);
                    $closeModal = true;
                }
            } else {
                $closeModal = true;
            }
        }

        $security    = $this->get('mautic.security');
        $permissions = [
            'edit'   => $security->hasEntityAccess('lead:leads:editown', 'lead:leads:editother', $lead->getPermissionUser()),
            'delete' => $security->hasEntityAccess('lead:leads:deleteown', 'lead:leads:deleteown', $lead->getPermissionUser()),
        ];

        if ($closeModal) {
            //just close the modal
            $passthroughVars['closeModal'] = 1;

            if ($valid && !$cancelled) {
                $passthroughVars['noteHtml'] = $this->renderView(
                    'MauticLeadBundle:Note:note.html.php',
                    [
                        'note'        => $note,
                        'lead'        => $lead,
                        'permissions' => $permissions,
                    ]
                );
                $passthroughVars['noteId'] = $note->getId();
            }

            $passthroughVars['mauticContent'] = 'leadNote';

            $response = new JsonResponse($passthroughVars);

            return $response;
        } else {
            return $this->delegateView(
                [
                    'viewParameters' => [
                        'form'        => $form->createView(),
                        'lead'        => $lead,
                        'permissions' => $permissions,
                    ],
                    'contentTemplate' => 'MauticLeadBundle:Note:form.html.php',
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
    public function deleteAction($leadId, $objectId)
    {
        $lead = $this->checkLeadAccess($leadId, 'view');
        if ($lead instanceof Response) {
            return $lead;
        }

        $model = $this->getModel('lead.note');
        $note  = $model->getEntity($objectId);

        if ($note === null) {
            return $this->notFound();
        }

        if (
            !$this->get('mautic.security')->hasEntityAccess('lead:leads:editown', 'lead:leads:editother', $lead->getPermissionUser())
            || $model->isLocked($note)
        ) {
            return $this->accessDenied();
        }

        $model->deleteEntity($note);

        $response = new JsonResponse(
            [
                'deleteId'      => $objectId,
                'mauticContent' => 'leadNote',
                'downNoteCount' => 1,
            ]
        );

        return $response;
    }

    /**
     * Executes an action defined in route.
     *
     * @param     $objectAction
     * @param int $objectId
     * @param int $leadId
     *
     * @return Response
     */
    public function executeNoteAction($objectAction, $objectId = 0, $leadId = 0)
    {
        if (method_exists($this, "{$objectAction}Action")) {
            return $this->{"{$objectAction}Action"}($leadId, $objectId);
        } else {
            return $this->accessDenied();
        }
    }
}
