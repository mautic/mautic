<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\LeadBundle\Entity\LeadNote;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class NoteController extends FormController
{

    /**
     * Generate's default list view
     *
     * @param $leadId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction ($leadId = 0, $page = 1)
    {
        if (empty($leadId)) {
            return $this->accessDenied();
        }

        $lead = $this->checkLeadAccess($leadId, 'view');
        if ($lead instanceof Response) {
            return $lead;
        }

        $session = $this->factory->getSession();

        //set limits
        $limit = $session->get('mautic.lead.'.$lead->getId().'.note.limit', $this->factory->getParameter('default_pagelimit'));
        $start = ($page === 1) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search = $this->request->get('search', $session->get('mautic.lead.'.$lead->getId().'.note.filter', ''));
        $session->set('mautic.lead.'.$lead->getId().'.note.filter', $search);

        //do some default filtering
        $orderBy    = $this->factory->getSession()->get('mautic.lead.'.$lead->getId().'.note.orderby', 'n.dateTime');
        $orderByDir = $this->factory->getSession()->get('mautic.lead.'.$lead->getId().'.note.orderbydir', 'DESC');

        $model = $this->getModel('lead.note');

        $force = array(
            array(
                'column' => 'n.lead',
                'expr'   => 'eq',
                'value'  => $lead
            )
        );

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        $session = $this->factory->getSession();

        $noteType = InputHelper::clean($this->request->request->get('noteTypes', array(), true));
        if (empty($noteType) && $tmpl == 'index') {
            $noteType = $session->get('mautic.lead.'.$lead->getId().'.notetype.filter', array());
        }
        $session->set('mautic.lead.'.$lead->getId().'.notetype.filter', $noteType);

        $noteTypes = array(
            'general' => 'mautic.lead.note.type.general',
            'email'   => 'mautic.lead.note.type.email',
            'call'    => 'mautic.lead.note.type.call',
            'meeting' => 'mautic.lead.note.type.meeting',
        );

        if (!empty($noteType)) {
            $force[] = array(
                'column' => 'n.type',
                'expr'   => 'in',
                'value'  => $noteType
            );
        }

        $items = $model->getEntities(array(
            'filter'         => array(
                'force' => $force,
                'string' => $search
            ),
            'start'          => $start,
            'limit'          => $limit,
            'orderBy'        => $orderBy,
            'orderByDir'     => $orderByDir,
            'hydration_mode' => 'HYDRATE_ARRAY'
        ));

        $security = $this->factory->getSecurity();

        return $this->delegateView(array(
            'viewParameters'  => array(
                'notes'       => $items,
                'lead'        => $lead,
                'page'        => $page,
                'limit'       => $limit,
                'search'      => $search,
                'noteType'    => $noteType,
                'noteTypes'   => $noteTypes,
                'tmpl'        => $tmpl,
                'permissions' => array(
                    'edit'   => $security->hasEntityAccess('lead:leads:editown', 'lead:leads:editother', $lead->getOwner()),
                    'delete' => $security->hasEntityAccess('lead:leads:deleteown', 'lead:leads:deleteown', $lead->getOwner()),
                )
            ),
            'passthroughVars' => array(
                'route'         => false,
                'mauticContent' => 'leadNote',
                'noteCount'     => count($items)
            ),
            'contentTemplate' => 'MauticLeadBundle:Note:list.html.php'
        ));
    }

    /**
     * Generate's new note and processes post data
     *
     * @param $leadId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction ($leadId)
    {
        $lead = $this->checkLeadAccess($leadId, 'view');
        if ($lead instanceof Response) {
            return $lead;
        }

        //retrieve the entity
        $note = new LeadNote();
        $note->setLead($lead);

        $model  = $this->getModel('lead.note');
        $action = $this->generateUrl('mautic_contactnote_action', array(
                'objectAction' => 'new',
                'leadId'       => $leadId)
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

        $security    = $this->factory->getSecurity();
        $permissions = array(
            'edit'   => $security->hasEntityAccess('lead:leads:editown', 'lead:leads:editother', $lead->getOwner()),
            'delete' => $security->hasEntityAccess('lead:leads:deleteown', 'lead:leads:deleteown', $lead->getOwner()),
        );

        if ($closeModal) {
            //just close the modal
            $passthroughVars = array(
                'closeModal'    => 1,
                'mauticContent' => 'leadNote'
            );

            if ($valid && !$cancelled) {
                $passthroughVars['upNoteCount'] = 1;
                $passthroughVars['noteHtml'] = $this->renderView('MauticLeadBundle:Note:note.html.php', array(
                    'note'        => $note,
                    'lead'        => $lead,
                    'permissions' => $permissions,
                ));
                $passthroughVars['noteId']   = $note->getId();
            }


            $response = new JsonResponse($passthroughVars);

            return $response;
        } else {

            return $this->delegateView(array(
                'viewParameters'  => array(
                    'form'        => $form->createView(),
                    'lead'        => $lead,
                    'permissions' => $permissions
                ),
                'contentTemplate' => 'MauticLeadBundle:Note:form.html.php'
            ));
        }
    }

    /**
     * Generate's edit form and processes post data
     *
     * @param $leadId
     * @param $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function editAction ($leadId, $objectId)
    {
        $lead = $this->checkLeadAccess($leadId, 'view');
        if ($lead instanceof Response) {
            return $lead;
        }

        $model      = $this->getModel('lead.note');
        $note       = $model->getEntity($objectId);
        $closeModal = false;
        $valid      = false;

        if ($note === null || !$this->factory->getSecurity()->hasEntityAccess('lead:leads:editown', 'lead:leads:editother', $lead->getOwner())) {
            return $this->accessDenied();
        }

        $action = $this->generateUrl('mautic_contactnote_action', array(
            'objectAction' => 'edit',
            'objectId'     => $objectId,
            'leadId'       => $leadId
        ));
        $form   = $model->createForm($note, $this->get('form.factory'), $action);

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

        $security    = $this->factory->getSecurity();
        $permissions = array(
            'edit'   => $security->hasEntityAccess('lead:leads:editown', 'lead:leads:editother', $lead->getOwner()),
            'delete' => $security->hasEntityAccess('lead:leads:deleteown', 'lead:leads:deleteown', $lead->getOwner()),
        );

        if ($closeModal) {
            //just close the modal
            $passthroughVars['closeModal'] = 1;

            if ($valid && !$cancelled) {
                $passthroughVars['noteHtml'] = $this->renderView('MauticLeadBundle:Note:note.html.php', array(
                    'note'        => $note,
                    'lead'        => $lead,
                    'permissions' => $permissions
                ));
                $passthroughVars['noteId']   = $note->getId();
            }

            $passthroughVars['mauticContent'] = 'leadNote';

            $response = new JsonResponse($passthroughVars);

            return $response;
        } else {

            return $this->delegateView(array(
                'viewParameters'  => array(
                    'form'        => $form->createView(),
                    'lead'        => $lead,
                    'permissions' => $permissions
                ),
                'contentTemplate' => 'MauticLeadBundle:Note:form.html.php'
            ));
        }
    }

    /**
     * Determines if the user has access to the lead the note is for
     *
     * @param $leadId
     * @param $action
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function checkLeadAccess ($leadId, $action)
    {
        //make sure the user has view access to this lead
        $leadModel = $this->getModel('lead');
        $lead      = $leadModel->getEntity($leadId);

        if ($lead === null) {
            //set the return URL
            $page      = $this->factory->getSession()->get('mautic.lead.page', 1);
            $returnUrl = $this->generateUrl('mautic_contact_index', array('page' => $page));

            return $this->postActionRedirect(array(
                'returnUrl'       => $returnUrl,
                'viewParameters'  => array('page' => $page),
                'contentTemplate' => 'MauticLeadBundle:Lead:index',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_contact_index',
                    'mauticContent' => 'leadNote'
                ),
                'flashes'         => array(
                    array(
                        'type'    => 'error',
                        'msg'     => 'mautic.lead.lead.error.notfound',
                        'msgVars' => array('%id%' => $leadId)
                    )
                )
            ));
        } elseif (!$this->factory->getSecurity()->hasEntityAccess(
            'lead:leads:' . $action . 'own', 'lead:leads:' . $action . 'other', $lead->getOwner()
        )
        ) {
            return $this->accessDenied();
        } else {
            return $lead;
        }
    }


    /**
     * Deletes the entity
     *
     * @param         $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction ($leadId, $objectId)
    {

        $lead = $this->checkLeadAccess($leadId, 'view');
        if ($lead instanceof Response) {
            return $lead;
        }

        $model = $this->getModel('lead.note');
        $note  = $model->getEntity($objectId);

        if (
            $note === null ||
            !$this->factory->getSecurity()->hasEntityAccess('lead:leads:editown', 'lead:leads:editother', $lead->getOwner()) ||
            $model->isLocked($note) ||
            $this->request->getMethod() != 'POST'
        ) {
            return $this->accessDenied();
        }

        $model->deleteEntity($note);

        $response = new JsonResponse(array(
            'deleteId'      => $objectId,
            'mauticContent' => 'leadNote',
            'downNoteCount' => 1
        ));

        return $response;
    }

    /**
     * Executes an action defined in route
     *
     * @param     $objectAction
     * @param int $objectId
     *
     * @return Response
     */
    public function executeNoteAction ($objectAction, $objectId = 0, $leadId = 0)
    {
        if (method_exists($this, "{$objectAction}Action")) {
            return $this->{"{$objectAction}Action"}($leadId, $objectId);
        } else {
            return $this->accessDenied();
        }
    }
}