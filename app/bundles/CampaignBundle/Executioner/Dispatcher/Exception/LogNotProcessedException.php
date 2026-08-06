<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Executioner\Dispatcher\Exception;

use Mautic\CampaignBundle\Entity\LeadEventLog;

final class LogNotProcessedException extends \Exception
{
    public function __construct(LeadEventLog $log)
    {
        parent::__construct("LeadEventLog ID # {$log->getId()} must be passed to either pass() or fail()");
    }
}
