<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Field\DTO;

use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Exception\InvalidObjectTypeException;

class CustomFieldObject
{
    /**
     * @var array
     */
    private $objects = [
        'lead'    => 'leads',
        'company' => 'companies',
    ];

    /**
     * @var LeadField
     */
    private $leadField;

    /**
     * @throws InvalidObjectTypeException
     */
    public function __construct(LeadField $leadField)
    {
        $leadFieldObject = $leadField->getObject();
        if (!isset($this->objects[$leadFieldObject])) {
            throw new InvalidObjectTypeException($leadFieldObject.' has no associated object.');
        }

        $this->leadField = $leadField;
    }

    public function getObject(): string
    {
        return $this->objects[$this->leadField->getObject()];
    }
}
