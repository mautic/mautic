<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Helper;

class AnonymizeHelper
{
    public const PRE_DEFINED_DOMAIN       = 'ano.nym';
    public const PRE_PSEUDONYMIZED_DOMAIN = 'pseudo.nym';

    /**
     * @param string|bool|null $email
     */
    public static function anonymizeEmail(
        $email,
        bool $pseudonymized = false,
        int $limit = 0,
        string $newDomain = self::PRE_DEFINED_DOMAIN,
    ): string {
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return '';
        }

        $emailParts = explode('@', $email);

        $toEncrypt = $emailParts[0].time().random_int(0, 999999);

        if ($pseudonymized) {
            $toEncrypt = $emailParts[0];
            $newDomain = self::PRE_PSEUDONYMIZED_DOMAIN;
        }

        $name       = hash('sha256', $toEncrypt);
        $email      = $name.'@'.$newDomain;

        if (0 === $limit || $limit >= strlen($email)) {
            return $email;
        }

        // if the limit is less than total characters keep going
        // Extract the domain from the email
        $atPosition = strrpos($email, '@'); // Find the position of '@'

        if (false === $atPosition) {
            // If the email does not have a domain, return the email as-is
            return $email;
        }

        $domain       = substr($email, $atPosition); // Extract the domain (e.g., @gmail.com or @uol.com)
        $domainLength = strlen($domain);

        // Calculate the allowed length for the local part
        $localPartLength = $limit - $domainLength;

        // If the local part length is less than 1, it's not possible to truncate
        if ($localPartLength < 1) {
            return $email;
        }

        // Extract and truncate the local part
        $localPart = substr($email, 0, $localPartLength);

        // Combine the truncated local part with the domain
        return $localPart.$domain;
    }

    /**
     * @param string|bool|null $text
     */
    public static function anonymizeText($text, bool $pseudonymize = false, int $limit = 0): string
    {
        if (empty($text)) {
            $text = '';
        }

        if (!$pseudonymize) {
            $text = $text.time().random_int(0, 999999);
        }

        $hash = hash('sha256', $text);
        if (0 === $limit || $limit >= strlen($hash)) {
            return $hash;
        }

        return substr($hash, 0, $limit);
    }
}
