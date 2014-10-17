<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\EventListener;

use Mautic\ApiBundle\Event\RouteEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\FormBundle\Event\FormBuilderEvent;
use Mautic\FormBundle\FormEvents;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Event as Events;
use Mautic\LeadBundle\LeadEvents;
use Mautic\PageBundle\Event\PageHitEvent;
use Mautic\PageBundle\PageEvents;
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
            CoreEvents::GLOBAL_SEARCH        => array('onGlobalSearch', 0),
            CoreEvents::BUILD_COMMAND_LIST   => array('onBuildCommandList', 0),
            LeadEvents::LEAD_POST_SAVE       => array('onLeadPostSave', 0),
            LeadEvents::LEAD_POST_DELETE     => array('onLeadDelete', 0),
            LeadEvents::FIELD_POST_SAVE      => array('onFieldPostSave', 0),
            LeadEvents::FIELD_POST_DELETE    => array('onFieldDelete', 0),
            LeadEvents::NOTE_POST_SAVE      => array('onNotePostSave', 0),
            LeadEvents::NOTE_POST_DELETE    => array('onNoteDelete', 0),
            LeadEvents::TIMELINE_ON_GENERATE => array('onTimelineGenerate', 0),
            UserEvents::USER_PRE_DELETE      => array('onUserDelete', 0)
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
        //Because there is an event within an event, there is a risk that something will trigger a loop which
        //needs to be prevented
        static $preventLoop = array();

        $lead = $event->getLead();
        if ($details = $event->getChanges()) {
            $check = base64_encode($lead->getId() . serialize($details));
            if (!in_array($check, $preventLoop)) {
                $preventLoop[] = $check;

                $log = array(
                    "bundle"    => "lead",
                    "object"    => "lead",
                    "objectId"  => $lead->getId(),
                    "action"    => ($event->isNew()) ? "create" : "update",
                    "details"   => $details,
                    "ipAddress" => $this->request->server->get('REMOTE_ADDR')
                );
                $this->factory->getModel('core.auditLog')->writeToLog($log);

                //trigger the points change event
                if (isset($details["points"])) {
                    if (!$event->isNew() && $this->dispatcher->hasListeners(LeadEvents::LEAD_POINTS_CHANGE)) {
                        $pointsEvent = new Events\PointsChangeEvent($lead, $details['points'][0], $details['points'][1]);
                        $this->dispatcher->dispatch(LeadEvents::LEAD_POINTS_CHANGE, $pointsEvent);
                    }
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
     * Compile events for the lead timeline
     *
     * @param Events\LeadTimelineEvent $event
     */
    public function onTimelineGenerate(Events\LeadTimelineEvent $event)
    {
        $lead = $event->getLead();

        // Add the lead's creation time to the timeline
        $event->addEvent(array(
            'event'     => 'lead.created',
            'timestamp' => $lead->getDateAdded(),
            'contentTemplate' => 'MauticLeadBundle:Timeline:index.html.php'
        ));
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
     * Add a note entry to the audit log
     *
     * @param Events\LeadNoteEvent $event
     */
    public function onNotePostSave(Events\LeadNoteEvent $event)
    {
        $note = $event->getNote();
        if ($details = $event->getChanges()) {
            $log = array(
                "bundle"    => "lead",
                "object"    => "note",
                "objectId"  => $note->getId(),
                "action"    => ($event->isNew()) ? "create" : "update",
                "details"   => $details,
                "ipAddress" => $this->request->server->get('REMOTE_ADDR')
            );
            $this->factory->getModel('core.auditLog')->writeToLog($log);
        }
    }

    /**
     * Add a note delete entry to the audit log
     *
     * @param Events\LeadNoteEvent $event
     */
    public function onNoteDelete(Events\LeadNoteEvent $event)
    {
        $note = $event->getNote();
        $log = array(
            "bundle"     => "lead",
            "object"     => "note",
            "objectId"   => $note->deletedId,
            "action"     => "delete",
            "details"    => array('text', $note->getText()),
            "ipAddress"  => $this->request->server->get('REMOTE_ADDR')
        );
        $this->factory->getModel('core.auditLog')->writeToLog($log);
    }
}
