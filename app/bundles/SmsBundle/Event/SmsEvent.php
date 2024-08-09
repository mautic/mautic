<?php

namespace Mautic\SmsBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\SmsBundle\Entity\Sms;

class SmsEvent extends CommonEvent
{
    /**
     * @param bool $isNew
     */
    public function __construct(Sms $sms, $isNew = false)
    {
        $this->entity = $sms;
        $this->isNew  = $isNew;
    }

    /**
     * Returns the Sms entity.
     *
     * @return Sms
     */
    public function getSms()
    {
        return $this->entity;
    }

    /**
     * Sets the Sms entity.
     */
    public function setSms(Sms $sms): void
    {
        $this->entity = $sms;
    }
}
