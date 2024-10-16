<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper;

use Mautic\CoreBundle\Helper\Clickthrough\ClickthroughKeyConverter;

trait ClickthroughHelperBCTrait
{
    private static ?ClickthroughHelper $instance = null;

    private static function getInstance(): ClickthroughHelper
    {
        if (null === self::$instance) {
            $shortKeyConvertor = new ClickthroughKeyConverter();
            self::$instance    = new ClickthroughHelper($shortKeyConvertor);
        }

        return self::$instance;
    }

    /**
     * Encode an array of strings to append to a URL.
     *
     * @depreacated use the `encode` method
     * @depreacated
     *
     * @return string
     */
    public static function encodeArrayForUrl(array $array)
    {
        return self::getInstance()->encode($array);
    }

    /**
     * Decode a string appended to URL into an array.
     *
     * @depreacated use the `decode` method
     *
     * @param bool $urlDecode
     *
     * @return array
     */
    public static function decodeArrayFromUrl($string, $urlDecode = true)
    {
        return self::getInstance()->decode($string, $urlDecode);
    }
}
