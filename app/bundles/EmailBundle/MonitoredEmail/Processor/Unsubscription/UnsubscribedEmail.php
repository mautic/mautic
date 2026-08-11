<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\MonitoredEmail\Processor\Unsubscription;

final class UnsubscribedEmail
{
    /**
     * @param string $contactEmail
     * @param string $unsubscriptionAddress
     */
    public function __construct(
        private $contactEmail,
        private $unsubscriptionAddress,
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
