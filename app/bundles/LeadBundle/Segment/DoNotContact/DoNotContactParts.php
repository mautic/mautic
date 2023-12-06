<?php

namespace Mautic\LeadBundle\Segment\DoNotContact;

use Mautic\LeadBundle\Entity\DoNotContact;

class DoNotContactParts
{
    private string $channel = 'email';

    private int $type = DoNotContact::UNSUBSCRIBED;

    public function __construct(?string $field)
    {
        if ($field && false !== strpos($field, '_manual')) {
            $this->type = DoNotContact::MANUAL;
        }

        if ($field && false !== strpos($field, '_bounced')) {
            $this->type = DoNotContact::BOUNCED;
        }

        if ($field && false !== strpos($field, '_sms')) {
            $this->channel = 'sms';
        }
    }

    public function getChannel(): string
    {
        return $this->channel;
    }

    public function getParameterType(): int
    {
        return $this->type;
    }
}
