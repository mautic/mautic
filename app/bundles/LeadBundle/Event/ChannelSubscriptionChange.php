<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Event;

use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\EventDispatcher\Event;

class ChannelSubscriptionChange extends Event
{
    /**
     * @var Lead
     */
    private $lead;

    /**
     * @var string
     */
    private $channel;

    /**
     * @var string
     */
    private $oldStatus;

    /**
     * @var string
     */
    private $newStatus;

    /**
     * ContactStatusChange constructor.
     *
     * @param Lead $lead
     * @param      $channel
     * @param      $oldStatus
     * @param      $newStatus
     */
    public function __construct(Lead $lead, $channel, $oldStatus, $newStatus)
    {
        $this->lead      = $lead;
        $this->channel   = $channel;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }

    /**
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @return string
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @return int
     */
    public function getOldStatus()
    {
        return $this->oldStatus;
    }

    /**
     * @return string
     */
    public function getOldStatusVerb()
    {
        return $this->getDncReasonVerb($this->oldStatus);
    }

    /**
     * @return int
     */
    public function getNewStatus()
    {
        return $this->newStatus;
    }

    /**
     * @return string
     */
    public function getNewStatusVerb()
    {
        return $this->getDncReasonVerb($this->newStatus);
    }

    /**
     * @param $reason
     *
     * @return string
     */
    private function getDncReasonVerb($reason)
    {
        // use true matching or else 'foobar' == DoNotContact::IS_CONTACTABLE
        switch (true) {
            case DoNotContact::IS_CONTACTABLE === $reason:
                return 'contactable';
            case DoNotContact::BOUNCED === $reason:
                return 'bounced';
            case DoNotContact::MANUAL === $reason:
                return 'manual';
            default:
                return 'unsubscribed';
        }
    }
}
