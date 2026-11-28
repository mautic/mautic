<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;

final readonly class MailHashHelper
{
    public function __construct(
        private CoreParametersHelper $coreParametersHelper,
    ) {
    }

    public function getEmailHash(string $email): string
    {
        $secret = $this->coreParametersHelper->get('secret_key');

        return self::getEmailHashForSecret($email, $secret);
    }

    /**
     * Signs the link to the copy of an email that its recipients pass on. It is made from the email alone, so it
     * is the same for every recipient and says nothing about who passed it on.
     */
    public function getForwardHash(int $emailId): string
    {
        return hash_hmac('sha256', 'email-forward:'.$emailId, (string) $this->coreParametersHelper->get('secret_key'));
    }

    public static function getEmailHashForSecret(string $email, string $secret): string
    {
        return hash_hmac('sha256', $email, $secret);
    }
}
