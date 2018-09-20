<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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

    /**
     * @return string
     */
    public function getObject()
    {
        return $this->objects[$this->leadField->getObject()];
    }
}
