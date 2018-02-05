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
use Mautic\CampaignBundle\EventCollector\Accessor\Event\ConditionAccessor;
use Mautic\CampaignBundle\Executioner\Dispatcher\EventDispatcher;
use Mautic\CampaignBundle\Executioner\Exception\CannotProcessEventException;
use Mautic\CampaignBundle\Executioner\Exception\ConditionFailedException;
use Mautic\CampaignBundle\Executioner\Result\EvaluatedContacts;

class Condition implements EventInterface
{
    const TYPE = 'condition';

    /**
     * @var EventDispatcher
     */
    private $dispatcher;

    /**
     * Condition constructor.
     *
     * @param EventDispatcher $dispatcher
     */
    public function __construct(EventDispatcher $dispatcher)
    {
        $this->dispatcher  = $dispatcher;
    }

    /**
     * @param AbstractEventAccessor $config
     * @param ArrayCollection       $logs
     *
     * @return EvaluatedContacts|mixed
     *
     * @throws CannotProcessEventException
     */
    public function executeLogs(AbstractEventAccessor $config, ArrayCollection $logs)
    {
        $evaluatedContacts = new EvaluatedContacts();

        /** @var LeadEventLog $log */
        foreach ($logs as $log) {
            try {
                /* @var ConditionAccessor $config */
                $this->execute($config, $log);
                $evaluatedContacts->pass($log->getLead());
            } catch (ConditionFailedException $exception) {
                $evaluatedContacts->fail($log->getLead());
                $log->setNonActionPathTaken(true);
            }
        }

        return $evaluatedContacts;
    }

    /**
     * @param ConditionAccessor $config
     * @param LeadEventLog      $log
     *
     * @throws CannotProcessEventException
     * @throws ConditionFailedException
     */
    private function execute(ConditionAccessor $config, LeadEventLog $log)
    {
        if (Event::TYPE_CONDITION !== $log->getEvent()->getEventType()) {
            throw new CannotProcessEventException('Cannot process event ID '.$log->getEvent()->getId().' as a condition.');
        }

        $conditionEvent = $this->dispatcher->dispatchConditionEvent($config, $log);

        if (!$conditionEvent->wasConditionSatisfied()) {
            throw new ConditionFailedException('evaluation failed');
        }
    }
}
