<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\MonitoredEmail\Processor\Unsubscription;

final class UnsubscribedEmail
{
    public function __construct(
        private string $contactEmail,
        private string $unsubscriptionAddress,
    ) {
    }

    /**
     * @return string
     */
    public function getContactEmail()
    {
        return $this->contactEmail;
    }

    /**
     * @return string
     */
    public function getUnsubscriptionAddress()
    {
        return $this->unsubscriptionAddress;
    }
}
