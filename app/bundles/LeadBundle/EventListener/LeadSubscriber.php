<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\EventListener;


use Mautic\ApiBundle\ApiEvents;
use Mautic\ApiBundle\Event\RouteEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\FormBundle\Event\FormBuilderEvent;
use Mautic\FormBundle\FormEvents;
use Mautic\LeadBundle\Event as Events;
use Mautic\LeadBundle\LeadEvents;
use Mautic\UserBundle\Event\UserEvent;
use Mautic\UserBundle\UserEvents;

/**
 * Class LeadSubscriber
 *
 * @package Mautic\LeadBundle\EventListener
 */
class LeadSubscriber extends CommonSubscriber
{

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            CoreEvents::GLOBAL_SEARCH      => array('onGlobalSearch', 0),
            CoreEvents::BUILD_COMMAND_LIST => array('onBuildCommandList', 0),
            LeadEvents::LEAD_POST_SAVE     => array('onLeadPostSave', 0),
            LeadEvents::LEAD_POST_DELETE   => array('onLeadDelete', 0),
            LeadEvents::FIELD_POST_SAVE     => array('onFieldPostSave', 0),
            LeadEvents::FIELD_POST_DELETE   => array('onFieldDelete', 0),
            UserEvents::USER_PRE_DELETE    => array('onUserDelete', 0),
            FormEvents::FORM_ON_BUILD      => array('onFormBuilder', 0)
        );
    }

    /**
     * @param MauticEvents\GlobalSearchEvent $event
     */
    public function onGlobalSearch(MauticEvents\GlobalSearchEvent $event)
    {
        $str = $event->getSearchString();
        if (empty($str)) {
            return;
        }

        $isCommand  = $this->translator->trans('mautic.core.searchcommand.is');
        $anonymous  = $this->translator->trans('mautic.lead.lead.searchcommand.isanonymous');
        $mine       = $this->translator->trans('mautic.core.searchcommand.ismine');
        $filter     = array("string" => $str, "force" => '');

        //only show results that are not anonymous so as to not clutter up things
        if (strpos($str, "$isCommand:$anonymous") === false) {
            $filter['force'] = " !$isCommand:$anonymous";
        }

        $permissions = $this->security->isGranted(
            array('lead:leads:viewown', 'lead:leads:viewother'),
            'RETURN_ARRAY'
        );
        if ($permissions['lead:leads:viewown'] || $permissions['lead:leads:viewother']) {
            //only show own leads if the user does not have permission to view others
            if (!$permissions['lead:leads:viewother']) {
                $filter['force'] .= " $isCommand:$mine";
            }

            $results = $this->factory->getModel('lead.lead')->getEntities(
                array(
                    'limit'          => 5,
                    'filter'         => $filter,
                    'withTotalCount' => true
                ));

            $count = $results['count'];

            if ($count > 0) {
                $leads       = $results['results'];
                $leadResults = array();

                foreach ($leads as $lead) {
                    $leadResults[] = $this->templating->renderResponse(
                        'MauticLeadBundle:Search:lead.html.php',
                        array('lead' => $lead)
                    )->getContent();
                }
                if (count($leads) > 5) {
                    $leadResults[] = $this->templating->renderResponse(
                        'MauticLeadBundle:Search:lead.html.php',
                        array(
                            'showMore'     => true,
                            'searchString' => $str,
                            'remaining'    => (count($leads) - 5)
                        )
                    )->getContent();
                }
                $leadResults['count'] = count($leads);
                $event->addResults('mautic.lead.lead.header.index', $leadResults);
            }
        }
    }

    /**
     * @param MauticEvents\CommandListEvent $event
     */
    public function onBuildCommandList(MauticEvents\CommandListEvent $event)
    {
        if ($this->security->isGranted(array('lead:leads:viewown', 'lead:leads:viewother'), "MATCH_ONE")) {
            $event->addCommands(
                'mautic.lead.lead.header.index',
                $this->factory->getModel('lead.lead')->getCommandList()
            );
        }
    }

    /**
     * Add a lead entry to the audit log
     *
     * @param Events\LeadEvent $event
     */
    public function onLeadPostSave(Events\LeadEvent $event)
    {
        $lead = $event->getLead();
        if ($details = $event->getChanges()) {
            $log = array(
                "bundle"    => "lead",
                "object"    => "lead",
                "objectId"  => $lead->getId(),
                "action"    => ($event->isNew()) ? "create" : "update",
                "details"   => $details,
                "ipAddress" => $this->request->server->get('REMOTE_ADDR')
            );
            $this->factory->getModel('core.auditLog')->writeToLog($log);

            //trigger the score change event
            if (isset($this->changes["score"])) {
                if (!$event->isNew() && $this->dispatcher->hasListeners(LeadEvents::LEAD_SCORE_CHANGE)) {
                    $scoreEvent = new Events\ScoreChangeEvent($lead, $this->changes['score'][0], $this->changes['score'][0]);
                    $this->dispatcher->dispatch(LeadEvents::LEAD_SCORE_CHANGE, $scoreEvent);
                }
            }
        }
    }

    /**
     * Add a lead delete entry to the audit log
     *
     * @param Events\LeadEvent $event
     */
    public function onLeadDelete(Events\LeadEvent $event)
    {
        $lead = $event->getLead();
        $log = array(
            "bundle"     => "lead",
            "object"     => "lead",
            "objectId"   => $lead->deletedId,
            "action"     => "delete",
            "details"    => array('name' => $lead->getPrimaryIdentifier()),
            "ipAddress"  => $this->request->server->get('REMOTE_ADDR')
        );
        $this->factory->getModel('core.auditLog')->writeToLog($log);
    }

    /**
     * Add a field entry to the audit log
     *
     * @param Events\LeadFieldEvent $event
     */
    public function onFieldPostSave(Events\LeadFieldEvent $event)
    {
        $field = $event->getField();
        if ($details = $event->getChanges()) {
            $log = array(
                "bundle"    => "lead",
                "object"    => "field",
                "objectId"  => $field->getId(),
                "action"    => ($event->isNew()) ? "create" : "update",
                "details"   => $details,
                "ipAddress" => $this->request->server->get('REMOTE_ADDR')
            );
            $this->factory->getModel('core.auditLog')->writeToLog($log);
        }
    }

    /**
     * Add a field delete entry to the audit log
     *
     * @param Events\LeadEvent $event
     */
    public function onFieldDelete(Events\LeadFieldEvent $event)
    {
        $field = $event->getField();
        $log = array(
            "bundle"     => "lead",
            "object"     => "field",
            "objectId"   => $field->deletedId,
            "action"     => "delete",
            "details"    => array('name', $field->getLabel()),
            "ipAddress"  => $this->request->server->get('REMOTE_ADDR')
        );
        $this->factory->getModel('core.auditLog')->writeToLog($log);
    }

    /**
     * Disassociate user from leads prior to user delete
     *
     * @param UserEvent $event
     */
    public function onUserDelete(UserEvent $event)
    {
        $this->factory->getModel('lead.lead')->disassociateOwner($event->getUser()->getId());
    }

    /**
     * Add a lead generation action to available form submit actions
     *
     * @param FormBuilderEvent $event
     */
    public function onFormBuilder(FormBuilderEvent $event)
    {
        //add lead generation submit action
        $action = array(
            'group'     => 'mautic.lead.lead.submitaction.group',
            'label'     => 'mautic.lead.lead.submitaction.createlead',
            'descr'     => 'mautic.lead.lead.submitaction.createlead_descr',
            'formType'  => 'lead_submitaction_createlead',
            'callback'  => '\Mautic\LeadBundle\Helper\EventHelper::createLeadOnFormSubmit'
        );

        $event->addSubmitAction('lead.create', $action);

        //add lead generation submit action
        $action = array(
            'group'     => 'mautic.lead.lead.submitaction.group',
            'label'     => 'mautic.lead.lead.submitaction.changescore',
            'descr'     => 'mautic.lead.lead.submitaction.changescore_descr',
            'formType'  => 'lead_submitaction_scorechange',
            'callback'  => '\Mautic\LeadBundle\Helper\EventHelper::changeScoreOnFormSubmit'
        );

        $event->addSubmitAction('lead.scorechange', $action);
    }
}