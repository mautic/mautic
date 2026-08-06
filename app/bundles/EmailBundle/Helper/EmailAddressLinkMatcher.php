<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Helper;

final class EmailAddressLinkMatcher
{
    public function __construct(private readonly MailHashHelper $mailHash)
    {
    }

    public function matchesLink(string $value, string $secretHash, ?string $statEmailAddress = null): bool
    {
        $matchesSecretHash = $this->mailHash->getEmailHash($value) === $secretHash;
        $normalizedValue   = strtolower($value);
        if (!$matchesSecretHash && $normalizedValue !== $value) {
            $matchesSecretHash = $this->mailHash->getEmailHash($normalizedValue) === $secretHash;
        }

        $matchesStatEmail = true;
        if (null !== $statEmailAddress) {
            $matchesStatEmail  = strtolower($value) === strtolower($statEmailAddress);
            $matchesSecretHash = $matchesSecretHash
                || $this->mailHash->getEmailHash($statEmailAddress) === $secretHash;
        }

        return $matchesSecretHash && $matchesStatEmail;
    }
}
