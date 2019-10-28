<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Helper;

use Doctrine\ORM\EntityRepository;

/**
 * Helper class custom field operations.
 */
class CustomFieldHelper
{
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_NUMBER  = 'number';
    const TYPE_SELECT  = 'select';

    /**
     * Fixes value type for specific field types.
     *
     * @param string $type
     * @param mixed  $value
     *
     * @return mixed
     */
    public static function fixValueType($type, $value)
    {
        if (!is_null($value)) {
            switch ($type) {
                case self::TYPE_NUMBER:
                    $value = (float) $value;
                    break;
                case self::TYPE_BOOLEAN:
                    $value = (bool) $value;
                    break;
                case self::TYPE_SELECT:
                    $value = (string) $value;
                    break;
            }
        }

        return $value;
    }

    /**
     * Return property label instead of value for select and selectmultiple fields.
     *
     * @param EntityRepository $repository
     * @param string $alias
     * @param mixed  $value
     *
     * @return mixed
     */
    public static function fixSelectFieldValue(EntityRepository $repository, $alias, $value)
    {
        if (!is_null($value)) {
            $customField = $repository->findOneByAlias($alias);
            $customFieldProperties = $customField->getProperties()['list'];
            if ( is_array($value) ) {
                for( $i=0;$i<count($value);$i++ ) {
                    $value[$i] = self::searchFieldValue($customFieldProperties,$alias,$value[$i]);
                }
            }else{
                $value = self::searchFieldValue($customFieldProperties,$alias,$value);
            }
        }
        return $value;
    }

    /**
     * Search for the Lead field value in LeadField properties.
     *
     * @param EntityRepository $fieldProperties
     * @param string $alias
     * @param mixed  $value
     *
     * @return string
     */
    public static function searchFieldValue($fieldProperties, $alias, $value)
    {
        $propertyIndex = array_search($value,array_column($fieldProperties, 'value'));
        if ( $propertyIndex !== false ) {
            $value = $fieldProperties[$propertyIndex]['label'];
        }
        return $value;
    }
}
