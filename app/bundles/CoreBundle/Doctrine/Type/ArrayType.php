<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Mautic\CoreBundle\Helper\UTF8Helper;

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

        if(strpos($serialized, chr(0)) !== false) {
            throw new \Doctrine\DBAL\Types\ConversionException("Serialized array includes null-byte. This cannot be saved as a text. Please check if you not provided object with protected or private members.");
        }

        return $serialized;
    }

    /**
    * @author	Chris Smith <code+php@chris.cs278.org>, Frank BÃ¼ltge <frank@bueltge.de>
    * @copyright	Copyright (c) 2009 Chris Smith (http://www.cs278.org/), 2011 Frank BÃ¼ltge (http://bueltge.de)
    * @license	http://sam.zoy.org/wtfpl/ WTFPL
    * @param	string	$value	Value to test for serialized form
    * @param	mixed	$result	Result of unserialize() of the $value
    * @return	boolean			True if $value is serialized data, otherwise FALSE
    */
    function is_serialized( $value, &$result = null ) {
        // Bit of a give away this one
        if ( ! is_string( $value ) ) {
            return FALSE;
        }

        // Serialized FALSE, return TRUE. unserialize() returns FALSE on an
        // invalid string or it could return FALSE if the string is serialized
        // FALSE, eliminate that possibility.
        if ( 'b:0;' === $value ) {
            $result = FALSE;
            return TRUE;
        }

        $length	= strlen($value);
        $end	= '';

        if ( isset( $value[0] ) ) {
            switch ($value[0]) {
                case 's':
                    if ( '"' !== $value[$length - 2] )
                        return FALSE;

                case 'b':
                case 'i':
                case 'd':
                    // This looks odd but it is quicker than isset()ing
                    $end .= ';';
                case 'a':
                case 'O':
                    $end .= '}';

                    if ( ':' !== $value[1] )
                        return FALSE;

                    switch ( $value[2] ) {
                        case 0:
                        case 1:
                        case 2:
                        case 3:
                        case 4:
                        case 5:
                        case 6:
                        case 7:
                        case 8:
                        case 9:
                            break;

                        default:
                            return FALSE;
                    }
                case 'N':
                    $end .= ';';

                    if ( $value[$length - 1] !== $end[0] )
                        return FALSE;
                    break;

                default:
                    return FALSE;
            }
        }

        if ( ( $result = @unserialize($value) ) === FALSE ) {
            $result = null;
            return FALSE;
        }

        return TRUE;
    }
}
