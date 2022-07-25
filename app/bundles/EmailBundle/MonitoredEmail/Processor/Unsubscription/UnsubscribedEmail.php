<?php

namespace Mautic\EmailBundle\MonitoredEmail\Processor\Unsubscription;

class UnsubscribedEmail
{
    /**
     * @var string
     */
    private $contactEmail;

    /**
     * @var string
     */
    private $unsubscriptionAddress;

    /**
     * @param string $contactEmail
     * @param string $unsubscriptionAddress
     */
    public function __construct($contactEmail, $unsubscriptionAddress)
    {
        $this->contactEmail          = $contactEmail;
        $this->unsubscriptionAddress = $unsubscriptionAddress;
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
