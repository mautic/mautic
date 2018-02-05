<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;
use Mautic\LeadBundle\Entity\Lead;

abstract class AbstractLogCollectionEvent extends \Symfony\Component\EventDispatcher\Event
{
    /**
     * @var AbstractEventAccessor
     */
    protected $config;

    /**
     * @var Event
     */
    protected $event;

    /**
     * @var ArrayCollection
     */
    protected $logs;

    /**
     * @var array
     */
    private $contacts = [];

    /**
     * @var array
     */
    private $logContactXref = [];

    /**
     * PendingEvent constructor.
     *
     * @param AbstractEventAccessor $config
     * @param Event                 $event
     * @param ArrayCollection       $logs
     */
    public function __construct(AbstractEventAccessor $config, Event $event, ArrayCollection $logs)
    {
        $this->config = $config;
        $this->event  = $event;
        $this->logs   = $logs;
        $this->extractContacts();
    }

    /**
     * @return AbstractEventAccessor
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return Event
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * Return an array of Lead entities keyed by LeadEventLog ID.
     *
     * @return Lead[]
     */
    public function getContacts()
    {
        return $this->contacts;
    }

    /**
     * Get the IDs of all contacts affected by this event.
     *
     * @return array
     */
    public function getContactIds()
    {
        return array_keys($this->logContactXref);
    }

    /**
     * @param int $id
     *
     * @return mixed|null
     */
    public function findLogByContactId($id)
    {
        return $this->logs->get($this->logContactXref[$id]);
    }

    private function extractContacts()
    {
        /** @var LeadEventLog $log */
        foreach ($this->logs as $log) {
            $contact                                 = $log->getLead();
            $this->contacts[$log->getId()]           = $contact;
            $this->logContactXref[$contact->getId()] = $log->getId();
        }
    }
}
