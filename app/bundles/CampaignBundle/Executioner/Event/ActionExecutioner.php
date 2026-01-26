<?php

namespace Mautic\CampaignBundle\Executioner\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\ActionAccessor;
use Mautic\CampaignBundle\Executioner\Dispatcher\ActionDispatcher;
use Mautic\CampaignBundle\Executioner\Exception\CannotProcessEventException;
use Mautic\CampaignBundle\Executioner\Logger\EventLogger;
use Mautic\CampaignBundle\Executioner\Result\EvaluatedContacts;
use Mautic\CoreBundle\Service\OptimisticLockServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ActionExecutioner implements EventInterface
{
    public const TYPE = 'action';

    public function __construct(
        private ActionDispatcher $dispatcher,
        private EventLogger $eventLogger,
        private OptimisticLockServiceInterface $optimisticLockService,
        #[Autowire(service: 'monolog.logger.mautic')]
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws CannotProcessEventException
     * @throws \Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogNotProcessedException
     * @throws \Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogPassedAndFailedException
     */
    public function execute(AbstractEventAccessor $config, ArrayCollection $logs): EvaluatedContacts
    {
        \assert($config instanceof ActionAccessor);

        /** @var LeadEventLog $firstLog */
        if (!$firstLog = $logs->first()) {
            return new EvaluatedContacts();
        }

        $event = $firstLog->getEvent();

        if (Event::TYPE_ACTION !== $event->getEventType()) {
            throw new CannotProcessEventException('Cannot process event ID '.$event->getId().' as an action.');
        }

        $this->lockLogs($logs);

        // Execute to process the batch of contacts
        $pendingEvent = $this->dispatcher->dispatchEvent($config, $event, $logs);

        $passed = $this->eventLogger->extractContactsFromLogs($pendingEvent->getSuccessful());
        $failed = $this->eventLogger->extractContactsFromLogs($pendingEvent->getFailures());

        return new EvaluatedContacts($passed, $failed);
    }

    /**
     * @param Collection<LeadEventLog> $logs
     */
    private function lockLogs(Collection $logs): void
    {
        foreach ($logs as $key => $log) {
            if (!$this->optimisticLockService->acquireLock($log)) {
                $logs->remove($key);
                $this->logger->error(message: sprintf(
                    'Campaign event log ID "%s" was skipped as it had been executed already.',
                    $log->getId(),
                ));
            }
        }
    }
}
