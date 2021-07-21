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
use Mautic\LeadBundle\Entity\LeadEventLog;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Response;

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
     * @var Response|null
     */
    private $response;

    /**
     * @var ?LeadEventLog
     */
    private $eventLog;

    /**
     * ReplyEvent constructor.
     *
     * @param string $message
     */
    public function __construct(Lead $contact, $message)
    {
        $this->contact = $contact;
        $this->message = $message;
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

    public function setResponse(Response $response)
    {
        $this->response = $response;
    }

    /**
     * @return Response|null
     */
    public function getResponse()
    {
        return $this->response;
    }

    public function getEventLog(): ?LeadEventLog
    {
        return $this->eventLog;
    }

    public function setEventLog(LeadEventLog $eventLog): void
    {
        $this->eventLog = $eventLog;
    }
}
