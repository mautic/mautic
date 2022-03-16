<?php

namespace Mautic\CampaignBundle\Executioner\Dispatcher\Exception;

use Mautic\CampaignBundle\Entity\LeadEventLog;

class LogNotProcessedException extends \Exception
{
    /**
     * LogNotProcessedException constructor.
     */
    public function __construct(LeadEventLog $log)
    {
        parent::__construct("LeadEventLog ID # {$log->getId()} must be passed to either pass() or fail()", 0, null);
    }
}
