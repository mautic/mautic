<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;
use Mautic\CampaignBundle\Executioner\Result\EvaluatedContacts;
use Symfony\Component\EventDispatcher\Event;

class DecisionResultsEvent extends Event
{
    /**
     * @var AbstractEventAccessor
     */
    private $eventConfig;

    /**
     * @var ArrayCollection|LeadEventLog[]
     */
    private $eventLogs;

    /**
     * @var EvaluatedContacts
     */
    private $evaluatedContacts;

    /**
     * DecisionResultsEvent constructor.
     *
     * @param AbstractEventAccessor $config
     * @param ArrayCollection       $logs
     * @param EvaluatedContacts     $evaluatedContacts
     */
    public function __construct(AbstractEventAccessor $config, ArrayCollection $logs, EvaluatedContacts $evaluatedContacts)
    {
        $this->eventConfig       = $config;
        $this->eventLogs         = $logs;
        $this->evaluatedContacts = $evaluatedContacts;
    }

    /**
     * @return AbstractEventAccessor
     */
    public function getEventConfig()
    {
        return $this->eventConfig;
    }

    /**
     * @return ArrayCollection|LeadEventLog[]
     */
    public function getLogs()
    {
        return $this->eventLogs;
    }

    /**
     * @return EvaluatedContacts
     */
    public function getEvaluatedContacts()
    {
        return $this->evaluatedContacts;
    }
}
