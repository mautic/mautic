<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Field\Event;

use Mautic\LeadBundle\Entity\LeadField;

final class AddColumnBackgroundEvent extends \Symfony\Contracts\EventDispatcher\Event
{
    /**
     * @var LeadField
     */
    private $leadField;

    public function __construct(LeadField $leadField)
    {
        $this->leadField = $leadField;
    }

    public function getLeadField(): LeadField
    {
        return $this->leadField;
    }
}
