<?php

namespace Mautic\LeadBundle\Segment\DoNotContact;

use Mautic\LeadBundle\Entity\DoNotContact;

class DoNotContactParts
{
    private string $channel = 'email';

    private int $type = DoNotContact::UNSUBSCRIBED;

    public function __construct(?string $field)
    {
        if ($field && str_contains($field, '_manual')) {
            $this->type = DoNotContact::MANUAL;
        }

        if ($field && str_contains($field, '_bounced')) {
            $this->type = DoNotContact::BOUNCED;
        }

        if ($field && str_contains($field, '_sms')) {
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
