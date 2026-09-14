<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\MonitoredEmail\Processor\Unsubscription;

final readonly class UnsubscribedEmail
{
    public function __construct(
        private string $contactEmail,
        private string $unsubscriptionAddress,
    ) {
    }

    public function getContactEmail(): string
    {
        return $this->contactEmail;
    }

    public function getUnsubscriptionAddress(): string
    {
        return $this->unsubscriptionAddress;
    }
}
