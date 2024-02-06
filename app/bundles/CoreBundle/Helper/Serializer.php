<?php

namespace Mautic\CoreBundle\Helper;

class Serializer
{
    /**
     * Unserializing a string can be a security vulnerability as it can contain classes that can execute a PHP code.
     * PHP >=7 has the `['allowed_classes' => false]` option to disable classes altogether or whitelist those needed.
     * PHP <7 do not accept the second parameter, throw warning and return false so we have to handle it differently.
     * This helper method is secure for PHP >= 7 by default and handle all PHP versions.
     *
     * PHP does not recommend untrusted user input even with ['allowed_classes' => false]
     *
     * @param string $serializedString
     *
     * @return mixed
     */
    public static function decode($serializedString, array $options = ['allowed_classes' => false])
    {
        if (1 === preg_match('/(^|;|{|})O:\+?[0-9]+:"/', $serializedString)) {
            throw new \InvalidArgumentException(sprintf('The string %s contains an object.', $serializedString));
        }

        if (version_compare(phpversion(), '7.0.0', '<')) {
            return unserialize($serializedString);
        }

        return unserialize($serializedString, $options);
    }
}
