<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\LeadBundle\Entity\LeadNote;
use Symfony\Component\Form\FormError;
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
    public function indexAction($leadId = 0)
    {
        if (empty($leadId)) {
            return $this->accessDenied();
        }

        $lead = $this->checkLeadAccess($leadId, 'view');
        if ($lead instanceof Response) {
            return $lead;
        }

        $items = $this->factory->getModel('lead.note')->getEntities(array(
            'filter' => array(
                'force' => array(
                    array(
                        'column' => 'n.lead',
                        'expr'   => 'eq',
                        'value'  => $lead
                    )
                )
            )
        ));

        return $this->delegateView(array(
            'viewParameters'  => array(
                'items'      => $items,
                'lead'       => $lead
            ),
            'contentTemplate' => 'MauticLeadBundle:Note:index.html.php'
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
        $note       = new LeadNote();
        $note->setLead($lead);

        $model      = $this->factory->getModel('lead.note');
        //set the return URL for post actions
        $returnUrl  = $this->generateUrl('mautic_leadnote_index');
        $action     = $this->generateUrl('mautic_leadnote_action', array('objectAction' => 'new'));
        //get the user form factory
        $form       = $model->createForm($note, $this->get('form.factory'), $action);

        ///Check for a submitted form and process it
        if ($this->request->getMethod() == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    //form is valid so process the data
                    $model->saveEntity($note);
                }
            }
        }

        $formView = $this->setFormTheme($form, 'MauticLeadBundle:Note:form.html.php', 'MauticLeadBundle:FormNote');

        return $this->delegateView(array(
            'viewParameters'  => array(
                'form' => $formView,
                'lead' => $lead
            ),
            'contentTemplate' => 'MauticLeadBundle:Note:form.html.php'
        ));
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

        $model  = $this->factory->getModel('lead.note');
        $note   = $model->getEntity($objectId);

        if ($model->isLocked($note)) {
            //deny access if the entity is locked
            return $this->isLocked(array(), $note, 'lead.note');
        }

        $action = $this->generateUrl('mautic_leadnote_action', array('objectAction' => 'edit', 'objectId' => $objectId));
        $form   = $model->createForm($note, $this->get('form.factory'), $action);

        ///Check for a submitted form and process it
        if ($this->request->getMethod() == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    //form is valid so process the data
                    $model->saveEntity($note);
                }
            } else {
                //unlock the entity
                $model->unlockEntity($note);
            }

            if ($cancelled || $valid) {

            }
        } else {
            //lock the entity
            $model->lockEntity($note);
        }

        return $this->delegateView(array(
            'viewParameters'  => array(
                'form'    => $form->createView()
            ),
            'contentTemplate' => 'MauticLeadBundle:Note:form.html.php'
        ));
    }

    /**
     * Determines if the user has access to the lead the note is for
     *
     * @param $leadId
     * @param $action
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function checkLeadAccess($leadId, $action)
    {
        //make sure the user has view access to this lead
        $leadModel = $this->factory->getModel('lead');
        $lead      = $leadModel->getEntity($leadId);

        if ($lead === null) {
            //set the return URL
            $page       = $this->factory->getSession()->get('mautic.lead.page', 1);
            $returnUrl  = $this->generateUrl('mautic_lead_index', array('page' => $page));

            return $this->postActionRedirect(array(
                'returnUrl'       => $returnUrl,
                'viewParameters'  => array('page' => $page),
                'contentTemplate' => 'MauticLeadBundle:Lead:index',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_lead_index',
                    'mauticContent' => 'leadNote'
                ),
                'flashes'         =>array(
                    array(
                        'type' => 'error',
                        'msg'  => 'mautic.lead.lead.error.notfound',
                        'msgVars' => array('%id%' => $leadId)
                    )
                )
            ));
        } elseif (!$this->factory->getSecurity()->hasEntityAccess(
            'lead:leads:'.$action.'own', 'lead:leads:'.$action.'other', $lead->getOwner()
        )) {
            return $this->accessDenied();
        } else {
            return $lead;
        }
    }
}