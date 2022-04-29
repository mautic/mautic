<?php

namespace Mautic\LeadBundle\Segment\DoNotContact;

use Mautic\LeadBundle\Entity\DoNotContact;

class DoNotContactParts
{
    private string $channel = 'email';

    private int $type = DoNotContact::UNSUBSCRIBED;

    /**
     * @param string $field
     */
    public function __construct($field)
    {
        if (false !== mb_strpos($field, '_manual')) {
            $this->type = DoNotContact::MANUAL;
        }

        if (false !== mb_strpos($field, '_bounced')) {
            $this->type = DoNotContact::BOUNCED;
        }

        if (false !== mb_strpos($field, '_sms')) {
            $this->channel = 'sms';
        }
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
    public function getParameterType()
    {
        return $this->type;
    }
}
