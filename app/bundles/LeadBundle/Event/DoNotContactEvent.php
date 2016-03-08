<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\LeadBundle\Entity\Lead;

/**
 * Class ContactEvent
 *
 * @package Mautic\LeadBundle\Event
 */
class DoNotContactEvent extends CommonEvent
{
    /**
     * @var string
     */
    protected $channel;

    /**
     * @var bool
     */
    protected $contactable = true;

    /**
     * @var \Mautic\LeadBundle\Entity\DoNotContact[]
     */
    protected $entries = array();

    /**
     * @var array
     */
    protected $parameters = array();

    /**
     * @param Lead $lead
     * @param string $channel
     * @param \Mautic\LeadBundle\Entity\DoNotContact[] $entries
     * @param array $parameters
     */
    public function __construct(Lead &$lead, $channel, array $entries, $parameters = [])
    {
        $this->lead = $lead;
        $this->channel = $channel;
        $this->entries = $entries;
        $this->parameters = $parameters;
    }

    /**
     * Returns the Lead entity
     *
     * @return Lead
     */
    public function getLead()
    {
        return $this->entity;
    }

    /**
     * Sets the Lead entity
     *
     * @param Lead $lead
     */
    public function setLead(Lead $lead)
    {
        $this->entity = $lead;
    }

    /**
     * @return string
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @param string $channel
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;
    }

    /**
     * @return \Mautic\LeadBundle\Entity\DoNotContact[]
     */
    public function getEntries()
    {
        return (array) $this->entries;
    }

    /**
     * @param array $entries
     */
    public function setEntries($entries)
    {
        $this->entries = $entries;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param array $parameters
     */
    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * @return boolean
     */
    public function isContactable()
    {
        return $this->contactable;
    }

    /**
     * @param boolean $contactable
     */
    public function setContactable($contactable)
    {
        $this->contactable = $contactable;
    }
}