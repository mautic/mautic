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
use Symfony\Component\Debug\Exception\ContextErrorException;

/**
 * Type that maps a PHP array to a clob SQL type.
 *
 * @since 2.0
 */
class ArrayType extends \Doctrine\DBAL\Types\ArrayType
{
    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (!is_array($value)) {
            return (null === $value) ? 'N;' : 'a:0:{}';
        }

        // MySQL will crap out on corrupt UTF8 leading to broken serialized strings
        array_walk(
            $value,
            function (&$entry) {
                $entry = UTF8Helper::toUTF8($entry);
            }
        );

        $serialized = serialize($value);

        if (strpos($serialized, chr(0)) !== false) {
            throw new \Doctrine\DBAL\Types\ConversionException(
                'Serialized array includes null-byte. This cannot be saved as a text. Please check if you not provided object with protected or private members.'
            );
        }

        return $serialized;
    }

    /**
     * @param mixed            $value
     * @param AbstractPlatform $platform
     *
     * @return array
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        try {
            return parent::convertToPHPValue($value, $platform);
        } catch (ConversionException $exception) {
            return [];
        } catch (ContextErrorException $exeption) {
            return [];
        }
    }
}
