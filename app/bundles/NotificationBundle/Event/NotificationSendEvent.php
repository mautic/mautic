<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\NotificationBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\LeadBundle\Entity\Lead;

/**
 * Class NotificationSendEvent.
 */
class NotificationSendEvent extends CommonEvent
{
    /**
     * @var string
     */
    protected $message;

    /**
     * @var
     */
    protected $heading;

    /**
     * @var Lead
     */
    protected $lead;

    /**
     * @param string $message
     * @param Lead   $lead
     */
    public function __construct($message, $heading, Lead $lead)
    {
        $this->message = $message;
        $this->heading = $heading;
        $this->lead    = $lead;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * @return mixed
     */
    public function getHeading()
    {
        return $this->heading;
    }

    /**
     * @param mixed $heading
     *
     * @return NotificationSendEvent
     */
    public function setHeading($heading)
    {
        $this->heading = $heading;
    }

    /**
     * @return array
     */
    public function getLead()
    {
        return $this->lead;
    }
}
