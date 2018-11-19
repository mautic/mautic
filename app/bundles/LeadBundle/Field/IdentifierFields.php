<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Field;

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
     * Fields that are used to identify as not anonymous or unique identifiers (company).
     *
     * @var array
     */
    private $defaultFields = [
        'lead' => [
            'firstname',
            'lastname',
            'company',
            'email',
        ],
        'company' => [
            'companyname',
            'companyemail',
            'companywebsite',
            'city',
            'state',
            'country',
        ],
    ];

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
     *
     * @return array
     */
    public function getFieldList($object)
    {
        $this->object = $object;

        return array_merge(
            isset($this->defaultFields[$object]) ? $this->defaultFields[$object] : [],
            $this->getUniqueIdentifierFields(),
            $this->getSocialFields()
        );
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
