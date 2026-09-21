<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Enum\Token;

enum RedirectUrlToken: string
{
    case PageLink     = 'pagelink';
    case FormField    = 'formfield';
    case ContactField = 'contactfield';

    public static function createAllTokensRegex(): string
    {
        $tokensNames = self::joinTokensNames();

        return "/{(?<name>{$tokensNames})=(?<value>[^{}]+)}/";
    }

    private static function joinTokensNames(): string
    {
        $names = [];

        foreach (self::cases() as $token) {
            $names[] = $token->value;
        }

        return \implode('|', $names);
    }
}
