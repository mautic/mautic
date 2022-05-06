<?php

namespace Mautic\EmailBundle\MonitoredEmail\Processor;

/**
 * Class AddressList.
 */
class Address
{
    /**
     * @param string $addresses String of email address from an email header
     *
     * @return array
     */
    public static function parseList($addresses)
    {
        $results         = [];
        $parsedAddresses = imap_rfc822_parse_adrlist($addresses, 'default.domain.name');
        foreach ($parsedAddresses as $parsedAddress) {
            if (
                isset($parsedAddress->host)
                &&
                '.SYNTAX-ERROR.' != $parsedAddress->host
                &&
                'default.domain.name' != $parsedAddress->host
            ) {
                $email           = $parsedAddress->mailbox.'@'.$parsedAddress->host;
                $name            = isset($parsedAddress->personal) ? $parsedAddress->personal : null;
                $results[$email] = $name;
            }
        }

        return $results;
    }

    /**
     * @param $address
     *
     * @return string|null
     */
    public static function parseAddressForStatHash($address)
    {
        if (preg_match('#^(.*?)\+(.*?)@(.*?)$#', $address, $parts)) {
            if (strstr($parts[2], '_')) {
                // Has an ID hash so use it to find the lead
                list($ignore, $hashId) = explode('_', $parts[2]);

                return $hashId;
            }
        }

        return null;
    }
}
