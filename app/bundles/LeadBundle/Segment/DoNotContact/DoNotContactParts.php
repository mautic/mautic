<?php

namespace Mautic\LeadBundle\Segment\DoNotContact;

use Mautic\LeadBundle\Entity\DoNotContact;

class DoNotContactParts
{
    private string $channel = 'email';

    private int $type = DoNotContact::UNSUBSCRIBED;
    
    private ?string $commentFilter = null;
    
    private bool $isAllDnc = false;

    public function __construct(?string $field)
    {
        if ($field && str_contains($field, '_all')) {
            $this->isAllDnc = true;
            return;
        }
        
        if ($field && str_contains($field, '_hard_bounce')) {
            $this->type = DoNotContact::BOUNCED;
            $this->commentFilter = 'hard';
            return;
        }
        
        if ($field && str_contains($field, '_soft_bounce')) {
            $this->type = DoNotContact::BOUNCED;
            $this->commentFilter = 'soft';
            return;
        }
        
        if ($field && str_contains($field, '_spam_bounce')) {
            $this->type = DoNotContact::BOUNCED;
            $this->commentFilter = 'spam';
            return;
        }
        
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

    public function getChannel(): string
    {
        return $this->channel;
    }

    public function getParameterType(): int
    {
        return $this->type;
    }
    
    public function getCommentFilter(): ?string
    {
        return $this->commentFilter;
    }
    
    public function isAllDnc(): bool
    {
        return $this->isAllDnc;
    }
}
