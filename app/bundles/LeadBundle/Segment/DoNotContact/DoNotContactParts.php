<?php

namespace Mautic\LeadBundle\Segment\DoNotContact;

use Mautic\LeadBundle\Entity\DoNotContact;

class DoNotContactParts
{
    /**
     * @var string
     */
    private $channel;

    /**
     * @var string
     */
    private $type;

    /**
     * @param string $field
     */
    public function __construct($field)
    {
        $parts         = explode('_', $field);
        switch (true) {
            case preg_match('/_manually$/', $field):
                $this->type    = DoNotContact::MANUAL;
                $this->channel = count($parts) === 4 ? $parts[2] : 'email';
                break;
            default:
                $this->type    = $parts[1] === 'bounced' ? DoNotContact::BOUNCED : DoNotContact::UNSUBSCRIBED;
                $this->channel = count($parts) === 3 ? $parts[2] : 'email';
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
        switch ($this->type) {
            case 'bounced':
            case DoNotContact::BOUNCED:
                return DoNotContact::BOUNCED;
            case 'manual':
            case DoNotContact::MANUAL:
                return DoNotContact::MANUAL;
            default:
                return DoNotContact::UNSUBSCRIBED;
        }
    }
}
