<?php

namespace Mautic\EmailBundle\MonitoredEmail\Processor\Unsubscription;

class UnsubscribedEmail
{
    /**
     * @param string $contactEmail
     * @param string $unsubscriptionAddress
     */
    public function __construct(
        private $contactEmail,
        private $unsubscriptionAddress
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
