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
use Mautic\CampaignBundle\Executioner\Exception\NoContactsFoundException;
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
     * @var ArrayCollection|Lead[]
     */
    private $contacts;

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
        $this->config   = $config;
        $this->event    = $event;
        $this->logs     = $logs;
        $this->contacts = new ArrayCollection();

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
     * @return Lead[]|ArrayCollection
     */
    public function getContacts()
    {
        return $this->contacts;
    }

    /**
     * @return ArrayCollection
     */
    public function getContactsKeyedById()
    {
        $contacts = new ArrayCollection();

        /** @var Lead $contact */
        foreach ($this->contacts as $contact) {
            $contacts->set($contact->getId(), $contact);
        }

        return $contacts;
    }

    /**
     * Get the IDs of all contacts affected by this event.
     *
     * @return array
     */
    public function getContactIds()
    {
        $contactIds = array_keys($this->logContactXref);

        return array_combine($contactIds, $contactIds);
    }

    /**
     * @param int $id
     *
     * @return LeadEventLog
     *
     * @throws NoContactsFoundException
     */
    public function findLogByContactId($id)
    {
        if (!isset($this->logContactXref[$id])) {
            throw new NoContactsFoundException("$id not found");
        }

        if (!$this->logs->offsetExists($this->logContactXref[$id])) {
            throw new NoContactsFoundException("$id was found in the xref table but no log was found");
        }

        return $this->logs->get($this->logContactXref[$id]);
    }

    private function extractContacts()
    {
        /** @var LeadEventLog $log */
        foreach ($this->logs as $log) {
            $contact                                 = $log->getLead();
            $this->logContactXref[$contact->getId()] = $log->getId();

            $this->contacts->set($log->getId(), $contact);
        }
    }
}
