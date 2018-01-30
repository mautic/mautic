<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Executioner\Dispatcher\Exception;

use Mautic\CampaignBundle\Entity\LeadEventLog;

class LogPassedAndFailedException extends \Exception
{
    /**
     * LogNotProcessedException constructor.
     *
     * @param LeadEventLog $log
     */
    public function __construct(LeadEventLog $log)
    {
        parent::__construct("LeadEventLog ID # {$log->getId()} was passed to both pass() or fail(). Pass or fail the log, not both.", 0, null);
    }
}
