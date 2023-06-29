<?php

namespace Mautic\CampaignBundle\Membership\Exception;

class RunLimitReachedException extends \Exception
{
    /**
     * @var int
     */
    private $contactsProcessed;

    /**
     * MaxContactsReachedException constructor.
     */
    public function __construct($contactsProcessed)
    {
        $this->contactsProcessed = (int) $contactsProcessed;

        parent::__construct();
    }

    /**
     * @return int
     */
    public function getContactsProcessed()
    {
        return $this->contactsProcessed;
    }
}
