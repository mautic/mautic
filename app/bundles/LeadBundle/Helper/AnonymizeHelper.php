<?php

namespace Mautic\LeadBundle\Helper;

class AnonymizeHelper
{
    public const PRE_DEFINED_DOMAIN = 'ano.nym';

    public static function email(string $email, string $newDomain = self::PRE_DEFINED_DOMAIN): string|false
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        $emailParts = explode('@', $email);
        $name       = hash('sha256', $emailParts[0]);

        return $name.'@'.$newDomain;
    }

    public static function text(string $text): string
    {
        return hash('sha256', $text);
    }
}
