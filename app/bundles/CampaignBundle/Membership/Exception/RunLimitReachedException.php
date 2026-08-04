<?php

namespace Mautic\CampaignBundle\Membership\Exception;

final class RunLimitReachedException extends \Exception
{
    private readonly int $contactsProcessed;

    public function __construct($contactsProcessed)
    {
        $this->contactsProcessed = (int) $contactsProcessed;

        parent::__construct();
    }

    public function getContactsProcessed(): int
    {
        return $this->contactsProcessed;
    }
}
