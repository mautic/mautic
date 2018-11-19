<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Field;

use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\IdentifierFieldEntityInterface;
use Mautic\LeadBundle\Entity\Lead;

class IdentifierFields
{
    /**
     * @var FieldsWithUniqueIdentifier
     */
    private $fieldsWithUniqueIdentifier;

    /**
     * @var FieldList
     */
    private $fieldList;

    /**
     * @var string
     */
    private $object;

    /**
     * IdentifierFields constructor.
     *
     * @param FieldsWithUniqueIdentifier $fieldsWithUniqueIdentifier
     * @param FieldList                  $fieldList
     */
    public function __construct(FieldsWithUniqueIdentifier $fieldsWithUniqueIdentifier, FieldList $fieldList)
    {
        $this->fieldsWithUniqueIdentifier = $fieldsWithUniqueIdentifier;
        $this->fieldList                  = $fieldList;
    }

    /**
     * @param $object
     * @param $entityClass
     *
     * @return array
     */
    public function getFieldList($object, $entityClass = null)
    {
        $this->object = $object;

        return array_merge(
            $this->getDefaultFields($entityClass),
            $this->getUniqueIdentifierFields(),
            $this->getSocialFields()
        );
    }

    /**
     * @param $entityClass
     *
     * @return array
     */
    public function getDefaultFields($entityClass)
    {
        if (null === $entityClass) {
            switch ($this->object) {
                case 'lead':
                    $entityClass = Lead::class;
                    break;
                case 'company':
                    $entityClass = Company::class;
                    break;
                default:
                    return [];
            }
        }

        if (is_subclass_of($entityClass, IdentifierFieldEntityInterface::class)) {
            return $entityClass::getDefaultIdentifierFields();
        }

        // The class wasn't recognized or doesn't implement the interface
        return [];
    }

    /**
     * @return array
     */
    private function getUniqueIdentifierFields()
    {
        $fields = $this->fieldsWithUniqueIdentifier->getFieldsWithUniqueIdentifier(
            [
                'object' => $this->object,
            ]
        );

        return array_keys($fields);
    }

    /**
     * @return array
     */
    private function getSocialFields()
    {
        $fields = $this->fieldList->getFieldList(
            true,
            false,
            [
                'isPublished' => true,
                'object'      => $this->object,
            ]
        );

        if (!isset($fields['Social'])) {
            return [];
        }

        return array_keys($fields['Social']);
    }
}
