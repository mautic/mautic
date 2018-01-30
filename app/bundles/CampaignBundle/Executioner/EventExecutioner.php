<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Executioner;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\EventCollector\Accessor\Exception\TypeNotFoundException;
use Mautic\CampaignBundle\EventCollector\EventCollector;
use Mautic\CampaignBundle\Executioner\Event\Action;
use Mautic\CampaignBundle\Executioner\Event\Condition;
use Mautic\CampaignBundle\Executioner\Event\Decision;
use Psr\Log\LoggerInterface;

class EventExecutioner
{
    /**
     * @var Action
     */
    private $actionExecutioner;

    /**
     * @var Condition
     */
    private $conditionExecutioner;

    /**
     * @var Decision
     */
    private $decisionExecutioner;

    /**
     * @var EventCollector
     */
    private $collector;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * EventExecutioner constructor.
     *
     * @param EventCollector  $eventCollector
     * @param Action          $actionExecutioner
     * @param Condition       $conditionExecutioner
     * @param Decision        $decisionExecutioner
     * @param LoggerInterface $logger
     */
    public function __construct(
        EventCollector $eventCollector,
        Action $actionExecutioner,
        Condition $conditionExecutioner,
        Decision $decisionExecutioner,
        LoggerInterface $logger
    ) {
        $this->actionExecutioner    = $actionExecutioner;
        $this->conditionExecutioner = $conditionExecutioner;
        $this->decisionExecutioner  = $decisionExecutioner;
        $this->collector            = $eventCollector;
        $this->logger               = $logger;
    }

    /**
     * @param Event           $event
     * @param ArrayCollection $contacts
     *
     * @throws Dispatcher\Exception\LogNotProcessedException
     * @throws Dispatcher\Exception\LogPassedAndFailedException
     */
    public function executeForContacts(Event $event, ArrayCollection $contacts)
    {
        $this->logger->debug('CAMPAIGN: Executing event ID '.$event->getId());

        if ($contacts->count()) {
            $this->logger->debug('CAMPAIGN: No contacts to process for event ID '.$event->getId());

            return;
        }

        $config = $this->collector->getEventConfig($event);

        switch ($event->getEventType()) {
            case Event::TYPE_ACTION:
                $this->actionExecutioner->executeForContacts($config, $event, $contacts);
                break;
            case Event::TYPE_CONDITION:
                $this->conditionExecutioner->executeForContacts($config, $event, $contacts);
                break;
            case Event::TYPE_DECISION:
                $this->decisionExecutioner->executeForContacts($config, $event, $contacts);
                break;
            default:
                throw new TypeNotFoundException("{$event->getEventType()} is not a valid event type");
        }
    }

    /**
     * @param Event           $event
     * @param ArrayCollection $contacts
     *
     * @throws Dispatcher\Exception\LogNotProcessedException
     * @throws Dispatcher\Exception\LogPassedAndFailedException
     */
    public function executeLogs(Event $event, ArrayCollection $logs)
    {
        $this->logger->debug('CAMPAIGN: Executing event ID '.$event->getId());

        if (!$logs->count()) {
            $this->logger->debug('CAMPAIGN: No logs to process for event ID '.$event->getId());

            return;
        }

        $config = $this->collector->getEventConfig($event);

        switch ($event->getEventType()) {
            case Event::TYPE_ACTION:
                $this->actionExecutioner->executeLogs($config, $event, $logs);
                break;
            case Event::TYPE_CONDITION:
                $this->conditionExecutioner->executeLogs($config, $event, $logs);
                break;
            case Event::TYPE_DECISION:
                $this->decisionExecutioner->executeLogs($config, $event, $logs);
                break;
            default:
                throw new TypeNotFoundException("{$event->getEventType()} is not a valid event type");
        }
    }
}
