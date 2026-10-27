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
use Mautic\CoreBundle\Service\OptimisticLockServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ConditionExecutioner implements EventInterface
{
    public const TYPE = 'condition';

    public function __construct(
        private readonly ConditionDispatcher $dispatcher,
        private readonly OptimisticLockServiceInterface $optimisticLockService,
        #[Autowire(service: 'monolog.logger.mautic')]
        private readonly LoggerInterface $logger,
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
            // Acquire optimistic lock only on pre-existing logs (those that already have an ID).
            // A log with version=1 and an ID was inserted during a previous execution that was
            // killed mid-flight — acquiring the lock (version → 2) prevents the stuck-jobs command
            // from treating it as completed.
            // Newly generated logs (no ID yet) are fresh executions and do not need locking.
            if (!$this->optimisticLockService->acquireLock($log)) {
                $this->logger->error(sprintf(
                    'Campaign condition event log ID "%s" was skipped as it had been executed already.',
                    $log->getId(),
                ));
                continue;
            }

            try {
                /** @var ConditionAccessor $config */
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
