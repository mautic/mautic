<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

class Serializer
{
    /**
     * Unserializing a string can be a security vulnerability as it can contain classes that can execute a PHP code.
     * PHP >=7 has the `['allowed_classes' => false]` option to disable classes altogether or whitelist those needed.
     * PHP <7 do not accept the second parameter, throw warning and return false so we have to handle it diffenetly.
     * This helper method is secure for PHP >= 7 by default and handle all PHP versions.
     *
     * PHP does not recommend untrusted user input even with ['allowed_classes' => false]
     *
     * @param string $serializedString
     * @param array  $options
     *
     * @return mixed
     */
    public static function decode($serializedString, array $options = ['allowed_classes' => false])
    {
        if (stripos($serializedString, 'o:') !== false) {
            throw new \InvalidArgumentException(sprintf('The string %s contains an object.', $serializedString));
        }

        if (version_compare(phpversion(), '7.0.0', '<')) {
            return unserialize($serializedString);
        }

        return unserialize($serializedString, $options);
    }
}
