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
     * @throws Dispatcher\LogNotProcessedException
     * @throws Dispatcher\LogPassedAndFailedException
     */
    public function execute(Event $event, ArrayCollection $contacts)
    {
        $this->logger->debug('CAMPAIGN: Executing event ID '.$event->getId());

        $config = $this->collector->getEventConfig($event);

        switch ($event->getEventType()) {
            case Event::TYPE_ACTION:
                $this->actionExecutioner->execute($config, $event, $contacts);
                break;
            case Event::TYPE_CONDITION:
                $this->conditionExecutioner->execute($config, $event, $contacts);
                break;
            case Event::TYPE_DECISION:
                $this->decisionExecutioner->execute($config, $event, $contacts);
                break;
            default:
                throw new TypeNotFoundException("{$event->getEventType()} is not a valid event type");
        }
    }
}
