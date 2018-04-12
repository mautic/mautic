<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Executioner\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\ActionAccessor;
use Mautic\CampaignBundle\Executioner\Dispatcher\EventDispatcher;
use Mautic\CampaignBundle\Executioner\Exception\CannotProcessEventException;

class Action implements EventInterface
{
    const TYPE = 'action';

    /**
     * @var EventDispatcher
     */
    private $dispatcher;

    /**
     * Action constructor.
     *
     * @param EventDispatcher $dispatcher
     */
    public function __construct(EventDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param ActionAccessor  $config
     * @param ArrayCollection $logs
     *
     * @return mixed|void
     *
     * @throws CannotProcessEventException
     * @throws \Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogNotProcessedException
     * @throws \Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogPassedAndFailedException
     * @throws \ReflectionException
     */
    public function executeLogs(AbstractEventAccessor $config, ArrayCollection $logs)
    {
        /** @var LeadEventLog $firstLog */
        if (!$firstLog = $logs->first()) {
            return;
        }

        $event = $firstLog->getEvent();

        if (Event::TYPE_ACTION !== $event->getEventType()) {
            throw new CannotProcessEventException('Cannot process event ID '.$event->getId().' as an action.');
        }

        // Execute to process the batch of contacts
        $this->dispatcher->dispatchActionEvent($config, $event, $logs);
    }
}
