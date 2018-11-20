<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Event;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\SmsBundle\Entity\Stat;
use Symfony\Component\EventDispatcher\Event;

class ReplyEvent extends Event
{
    /**
     * @var Lead
     */
    private $contact;

    /**
     * @var string
     */
    private $message;

    /**
     * This may not be known depending on if the SMS transport service supports identifying the message the contact replied to.
     *
     * @var Stat|null
     */
    private $stat;

    /**
     * ReplyEvent constructor.
     *
     * @param Lead      $contact
     * @param           $message
     * @param Stat|null $stat
     */
    public function __construct(Lead $contact, $message, Stat $stat = null)
    {
        $this->contact = $contact;
        $this->message = $message;
        $this->stat    = $stat;
    }

    /**
     * @return Lead
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return Stat|null
     */
    public function getStat()
    {
        return $this->stat;
    }
}
