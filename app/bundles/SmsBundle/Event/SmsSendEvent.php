<?php

namespace Mautic\SmsBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\LeadBundle\Entity\Lead;

class SmsSendEvent extends CommonEvent
{
    /**
     * @var int
     */
    protected $smsId;

    /**
     * @param string $content
     */
    public function __construct(
        protected $content,
        protected Lead $lead
    ) {
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     */
    public function setContent($content): void
    {
        $this->content = $content;
    }

    /**
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @param Lead $lead
     */
    public function setLead($lead): void
    {
        $this->lead = $lead;
    }

    /**
     * @return int
     */
    public function getSmsId()
    {
        return $this->smsId;
    }

    /**
     * @param int $smsId
     */
    public function setSmsId($smsId): void
    {
        $this->smsId = $smsId;
    }
}
