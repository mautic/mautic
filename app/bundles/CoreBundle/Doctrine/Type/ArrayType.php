<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Mautic\CoreBundle\Helper\UTF8Helper;

/**
 * Type that maps a PHP/JSON array to a clob SQL type.
 *
 * @since 2.0
 */
class ArrayType extends \Doctrine\DBAL\Types\JsonArrayType
{
    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

        $value = (is_resource($value)) ? stream_get_contents($value) : $value;

        if (substr($value, 0, 1) === '{') {
            return parent::convertToPHPValue($value, $platform);
        }

        $val = unserialize($value);
        if ($val === false && $value != 'b:0;') {
            throw ConversionException::conversionFailed($value, $this->getName());
        }

        return $val;
    }
}
