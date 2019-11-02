<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Executioner\Dispatcher;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Event\DecisionEvent;
use Mautic\CampaignBundle\Event\DecisionResultsEvent;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\DecisionAccessor;
use Mautic\CampaignBundle\Executioner\Result\EvaluatedContacts;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DecisionDispatcher
{
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * DecisionDispatcher constructor.
     *
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param DecisionAccessor $config
     * @param LeadEventLog     $log
     * @param mixed            $passthrough
     *
     * @return DecisionEvent
     */
    public function dispatchRealTimeEvent(DecisionAccessor $config, LeadEventLog $log, $passthrough)
    {
        $event = new DecisionEvent($config, $log, $passthrough);
        $this->dispatcher->dispatch($config->getEventName(), $event);

        return $event;
    }

    /**
     * @param DecisionAccessor $config
     * @param LeadEventLog     $log
     *
     * @return DecisionEvent
     */
    public function dispatchEvaluationEvent(DecisionAccessor $config, LeadEventLog $log)
    {
        $event = new DecisionEvent($config, $log);

        $this->dispatcher->dispatch(CampaignEvents::ON_EVENT_DECISION_EVALUATION, $event);

        return $event;
    }

    /**
     * @param DecisionAccessor  $config
     * @param ArrayCollection   $logs
     * @param EvaluatedContacts $evaluatedContacts
     */
    public function dispatchDecisionResultsEvent(DecisionAccessor $config, ArrayCollection $logs, EvaluatedContacts $evaluatedContacts)
    {
        if (!$logs->count()) {
            return;
        }

        $this->dispatcher->dispatch(
            CampaignEvents::ON_EVENT_DECISION_EVALUATION_RESULTS,
            new DecisionResultsEvent($config, $logs, $evaluatedContacts)
        );
    }
}
