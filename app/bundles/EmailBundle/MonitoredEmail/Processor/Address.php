<?php

namespace Mautic\EmailBundle\MonitoredEmail\Processor;

class Address
{
    /**
     * @param string $addresses String of email address from an email header
     */
    public static function parseList($addresses): array
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
                $name            = $parsedAddress->personal ?? null;
                $results[$email] = $name;
            }
        }

        return $results;
    }

    public static function parseAddressForStatHash($address): ?string
    {
        if (preg_match('#^(.*?)\+(.*?)@(.*?)$#', $address, $parts)) {
            if (strstr($parts[2], '_')) {
                // Has an ID hash so use it to find the lead
                [$ignore, $hashId] = explode('_', $parts[2]);

                return $hashId;
            }
        }

        return null;
    }
}
