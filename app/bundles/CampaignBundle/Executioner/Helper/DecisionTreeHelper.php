<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Executioner\Helper;

use Mautic\CampaignBundle\EventCollector\EventCollector;
use Mautic\CampaignBundle\Executioner\EventExecutioner;
use Mautic\CampaignBundle\Executioner\Logger\EventLogger;
use Psr\Log\LoggerInterface;

class DecisionTreeHelper
{
    /**
     * @var EventCollector
     */
    private $eventCollector;

    /**
     * @var EventLogger
     */
    private $eventLogger;

    /**
     * @var EventExecutioner
     */
    private $eventExecutioner;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * DecisionTreeHelper constructor.
     *
     * @param EventCollector   $eventCollector
     * @param EventLogger      $eventLogger
     * @param EventExecutioner $eventExecutioner
     * @param LoggerInterface  $logger
     */
    public function __construct(EventCollector $eventCollector, EventLogger $eventLogger, EventExecutioner $eventExecutioner, LoggerInterface $logger)
    {
        $this->eventCollector   = $eventCollector;
        $this->eventLogger      = $eventLogger;
        $this->eventExecutioner = $eventExecutioner;
        $this->logger           = $logger;
    }
}
