<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\EventListener;

use Mautic\ApiBundle\Event\RouteEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Event as MauticEvents;
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
            LeadEvents::LEAD_POST_SAVE       => array('onLeadPostSave', 0),
            LeadEvents::LEAD_POST_DELETE     => array('onLeadDelete', 0),
            LeadEvents::LEAD_POST_MERGE      => array('onLeadMerge', 0),
            LeadEvents::FIELD_POST_SAVE      => array('onFieldPostSave', 0),
            LeadEvents::FIELD_POST_DELETE    => array('onFieldDelete', 0),
            LeadEvents::NOTE_POST_SAVE       => array('onNotePostSave', 0),
            LeadEvents::NOTE_POST_DELETE     => array('onNoteDelete', 0),
            LeadEvents::TIMELINE_ON_GENERATE => array('onTimelineGenerate', 0),
            UserEvents::USER_PRE_DELETE      => array('onUserDelete', 0)
        );
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
                    "ipAddress" => $this->factory->getIpAddressFromRequest()
                );
                $this->factory->getModel('core.auditLog')->writeToLog($log);

                if (isset($details['dateIdentified'])) {
                    //log the day lead was identified
                    $log = array(
                        "bundle"    => "lead",
                        "object"    => "lead",
                        "objectId"  => $lead->getId(),
                        "action"    => "identified",
                        "details"   => array(),
                        "ipAddress" => $this->factory->getIpAddressFromRequest()
                    );
                    $this->factory->getModel('core.auditLog')->writeToLog($log);

                    //trigger lead identified event
                    if (!$lead->imported && $this->dispatcher->hasListeners(LeadEvents::LEAD_IDENTIFIED)) {
                        $this->dispatcher->dispatch(LeadEvents::LEAD_IDENTIFIED, $event);
                    }
                }

                //add if an ip was added
                if (isset($details['ipAddresses'])) {
                    $log = array(
                        "bundle"    => "lead",
                        "object"    => "lead",
                        "objectId"  => $lead->getId(),
                        "action"    => "ipadded",
                        "details"   => $details['ipAddresses'][1],
                        "ipAddress" => $this->request->server->get('REMOTE_ADDR')
                    );
                    $this->factory->getModel('core.auditLog')->writeToLog($log);
                }

                //trigger the points change event
                if (!$lead->imported && isset($details["points"]) && (int) $details["points"][1] > 0) {
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
            "ipAddress"  => $this->factory->getIpAddressFromRequest()
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
                "ipAddress" => $this->factory->getIpAddressFromRequest()
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
            "ipAddress"  => $this->factory->getIpAddressFromRequest()
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
        $eventTypes = array(
            'lead.create'     => 'mautic.lead.event.create',
            'lead.identified' => 'mautic.lead.event.identified',
            'lead.ipadded'    => 'mautic.lead.event.ipadded'
        );

        foreach ($eventTypes as $type => $label) {
            $event->addEventType($type, $this->translator->trans($label));
        }
    }

    /**
     * Disassociate user from leads prior to user delete
     *
     * @param UserEvent $event
     */
    public function onUserDelete(UserEvent $event)
    {
        //not needed as set onDelete="SET NULL" on the entity association
        //$this->factory->getModel('lead.lead')->disassociateOwner($event->getUser()->getId());
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
                "ipAddress" => $this->factory->getIpAddressFromRequest()
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
            "ipAddress"  => $this->factory->getIpAddressFromRequest()
        );
        $this->factory->getModel('core.auditLog')->writeToLog($log);
    }

    /**
     * @param LeadChangeEvent $event
     */
    public function onLeadMerge(Events\LeadMergeEvent $event)
    {
        $this->factory->getEntityManager()->getRepository('MauticLeadBundle:PointsChangeLog')->updateLead(
            $event->getLoser()->getId(),
            $event->getVictor()->getId()
        );

        $this->factory->getEntityManager()->getRepository('MauticLeadBundle:ListLead')->updateLead(
            $event->getLoser()->getId(),
            $event->getVictor()->getId()
        );

        $this->factory->getEntityManager()->getRepository('MauticLeadBundle:LeadNote')->updateLead(
            $event->getLoser()->getId(),
            $event->getVictor()->getId()
        );

        $log = array(
            "bundle"     => "lead",
            "object"     => "lead",
            "objectId"   => $event->getLoser()->getId(),
            "action"     => "merge",
            "details"    => array('merged_into' => $event->getVictor()->getId()),
            "ipAddress"  => $this->factory->getIpAddressFromRequest()
        );
        $this->factory->getModel('core.auditLog')->writeToLog($log);
    }
}
