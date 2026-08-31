<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\LeadBundle\Entity\Lead;

class SmsSendEvent extends CommonEvent
{
    /**
     * @var int
     */
    protected $smsId;

    public function __construct(
        protected string $content,
        protected Lead $lead,
    ) {
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getLead(): Lead
    {
        return $this->lead;
    }

    public function setLead(Lead $lead): void
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
