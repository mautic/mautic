<?php

namespace Mautic\CampaignBundle\Executioner\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\ConditionAccessor;
use Mautic\CampaignBundle\Executioner\Dispatcher\ConditionDispatcher;
use Mautic\CampaignBundle\Executioner\Exception\CannotProcessEventException;
use Mautic\CampaignBundle\Executioner\Exception\ConditionFailedException;
use Mautic\CampaignBundle\Executioner\Result\EvaluatedContacts;

class ConditionExecutioner implements EventInterface
{
    public const TYPE = 'condition';

    public function __construct(
        private ConditionDispatcher $dispatcher
    ) {
    }

    /**
     * @throws CannotProcessEventException
     */
    public function execute(AbstractEventAccessor $config, ArrayCollection $logs): EvaluatedContacts
    {
        \assert($config instanceof ConditionAccessor);
        $evaluatedContacts = new EvaluatedContacts();

        /** @var LeadEventLog $log */
        foreach ($logs as $log) {
            try {
                /* @var ConditionAccessor $config */
                $this->dispatchEvent($config, $log);
                $evaluatedContacts->pass($log->getLead());
            } catch (ConditionFailedException) {
                $evaluatedContacts->fail($log->getLead());
                $log->setNonActionPathTaken(true);
            }

            // Unschedule the condition and update date triggered timestamp
            $log->setDateTriggered(new \DateTime());
        }

        return $evaluatedContacts;
    }

    /**
     * @throws CannotProcessEventException
     * @throws ConditionFailedException
     */
    private function dispatchEvent(ConditionAccessor $config, LeadEventLog $log): void
    {
        if (Event::TYPE_CONDITION !== $log->getEvent()->getEventType()) {
            throw new CannotProcessEventException('Cannot process event ID '.$log->getEvent()->getId().' as a condition.');
        }

        $conditionEvent = $this->dispatcher->dispatchEvent($config, $log);

        if (!$conditionEvent->wasConditionSatisfied()) {
            throw new ConditionFailedException('evaluation failed');
        }
    }
}
