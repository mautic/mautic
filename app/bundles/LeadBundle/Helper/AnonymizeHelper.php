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
    public static function anonymizeEmail($email, bool $pseudonymized = false, string $newDomain = self::PRE_DEFINED_DOMAIN): ?string
    {
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $emailParts = explode('@', $email);

        $toEncrypt = $emailParts[0].time().random_int(0, 999999);

        if ($pseudonymized) {
            $toEncrypt = $emailParts[0];
            $newDomain = self::PRE_PSEUDONYMIZED_DOMAIN;
        }
        $name       = hash('sha256', $toEncrypt);

        return $name.'@'.$newDomain;
    }

    /**
     * @param string|bool|null $text
     */
    public static function anonymizeText($text, bool $pseudonymize = false): string
    {
        if (empty($text)) {
            $text = '';
        }

        if (!$pseudonymize) {
            $text = $text.time().random_int(0, 999999);
        }

        return hash('sha256', $text);
    }
}
