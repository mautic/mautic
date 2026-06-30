<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Command;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\CampaignRepository;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Event\MaxAllowedRecordsReachedInSingleProcessEvent;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\CampaignBundle\Executioner\EventExecutioner;
use Mautic\CampaignBundle\Executioner\Exception\NoContactsFoundException;
use Mautic\CampaignBundle\Executioner\Result\Counter;
use Mautic\CampaignBundle\Model\EventModel;
use Mautic\CoreBundle\Event\JobExtendTimeEvent;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\ExitCode;
use Mautic\CoreBundle\ProcessSignal\Exception\SignalCaughtException;
use Mautic\CoreBundle\ProcessSignal\ProcessSignalService;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(
    name: ResumeStuckCampaignCommand::COMMAND_NAME,
    description: 'Resume execution for contacts stuck in campaign workflows.'
)]
final class ResumeStuckCampaignCommand extends Command
{
    use WriteCountTrait;

    public const COMMAND_NAME                      = 'mautic:campaigns:resume-stuck';

    private const MAX_ALLOWED_RECORDS_EACH_PROCESS = 500;

    public function __construct(
        private TranslatorInterface $translator,
        private EventModel $eventModel,
        private CampaignRepository $campaignRepository,
        private LeadModel $leadModel,
        private EventExecutioner $eventExecutioner,
        private ProcessSignalService $processSignalService,
        private EventDispatcherInterface $eventDispatcher,
        private CoreParametersHelper $coreParametersHelper,
    ) {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->addArgument(
            'campaign-id',
            InputArgument::REQUIRED,
            'The ID of the campaign to resume.'
        )
            ->addOption(
                '--dry-run',
                null,
                InputOption::VALUE_NONE,
                'Find next events without executing them.'
            )
            ->addOption(
                '--min-contact-id',
                null,
                InputOption::VALUE_OPTIONAL,
                'Fix contacts starting at a specific contact ID.'
            )
            ->addOption(
                '--max-contact-id',
                null,
                InputOption::VALUE_OPTIONAL,
                'Fix contacts up to a specific contact ID.'
            )
            ->addOption(
                '--batch-limit',
                '-l',
                InputOption::VALUE_OPTIONAL,
                'Set batch size of contacts to process per round. Defaults to 100.',
                100
            );

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $campaignId     = (int) $input->getArgument('campaign-id');
        $batchLimit     = (int) $input->getOption('batch-limit');
        $isDryRun       = (bool) $input->getOption('dry-run');
        $contactMinId   = (int) $input->getOption('min-contact-id');
        $contactMaxId   = (int) $input->getOption('max-contact-id');

        $contactLimiter = new ContactLimiter($batchLimit, null, $contactMinId, $contactMaxId);
        $this->processSignalService->registerSignalHandler(
            fn (int $signal) => $output->writeln(sprintf('Signal %d caught.', $signal))
        );

        try {
            $campaign = $this->campaignRepository->getEntity($campaignId);
            if (!$campaign) {
                $output->writeln('<error>Campaign with ID '.$campaignId.' not found.</error>');

                return Command::FAILURE;
            }

            if (!$campaign->isPublished() || $campaign->isDeleted()) {
                $output->writeln('<error>Campaign with ID '.$campaignId.' is not published or is deleted.</error>');

                return Command::FAILURE;
            }

            return $this->processEvents($campaignId, $batchLimit, $contactLimiter, $isDryRun, $output);
        } catch (SignalCaughtException) {
            return ExitCode::TERMINATED;
        }
    }

    /**
     * Display the next events in a table format.
     *
     * @param array<mixed> $nextEvents
     */
    private function displayNextEvents(array $nextEvents, OutputInterface $output): void
    {
        $table = new Table($output);
        $table->setHeaders([
            'Contact ID',
            'Next Event ID',
            'Next Event Name',
            'Event Type',
            'Parent Event ID',
            'Parent Event Date',
        ]);

        $contactCount      = 0;
        $contactEventCount = [];

        foreach ($nextEvents as $event) {
            $contactId = $event['contact_id'];

            if (!isset($contactEventCount[$contactId])) {
                $contactEventCount[$contactId] = 0;
                ++$contactCount;
            }

            ++$contactEventCount[$contactId];

            $table->addRow([
                $event['contact_id'],
                $event['next_event_id'],
                $event['next_event_name'] ?: '(unnamed)',
                $event['next_event_event_type'],
                $event['parent_event_id'],
                $event['last_executed_date'],
            ]);
        }

        $table->render();
    }

    /**
     * Execute the next events for contacts.
     *
     * @param array<mixed> $nextEvents
     */
    private function executeNextEvents(array $nextEvents, OutputInterface $output): Counter
    {
        $counter = new Counter();

        $groupedEvents = $this->groupEventsByIdAndDate($nextEvents);

        foreach ($groupedEvents as $eventId => $dateGroups) {
            $event = $this->eventModel->getEntity($eventId);
            if (!$event) {
                $output->writeln('<error>Event with ID '.$eventId.' not found.</error>');
                continue;
            }

            foreach ($dateGroups as $eventData) {
                $this->processEventGroup($event, $eventData, $counter);
            }

            $this->processSignalService->throwExceptionIfSignalIsCaught();
        }

        return $counter;
    }

    /**
     * Group events by event ID and execution date.
     *
     * @param array<mixed> $nextEvents
     *
     * @return array<mixed> Events grouped by ID and date
     */
    private function groupEventsByIdAndDate(array $nextEvents): array
    {
        $eventMap = [];

        foreach ($nextEvents as $event) {
            $eventId            = $event['next_event_id'];
            $contactId          = $event['contact_id'];
            $parentExecutedDate = $event['last_executed_date'];

            if (!isset($eventMap[$eventId][$parentExecutedDate])) {
                $eventMap[$eventId][$parentExecutedDate] = [
                    'event_id'    => $eventId,
                    'contact_ids' => [],
                ];
            }

            $eventMap[$eventId][$parentExecutedDate]['contact_ids'][] = $contactId;
        }

        return $eventMap;
    }

    /**
     * Process campaign events in batches.
     */
    private function processEvents(int $campaignId, int $batchLimit, ContactLimiter $contactLimiter,
        bool $isDryRun, OutputInterface $output): int
    {
        $recordsProcessed   = 0;
        $recordsAfter       = $this->coreParametersHelper->get('campaigns_resume_stuck_records_after');
        while ($nextEvents = $this->campaignRepository->findStuckEventsToExecute($campaignId, $batchLimit,
            $contactLimiter->getMinContactId(), $contactLimiter->getMaxContactId(), $recordsAfter)) {
            if ($isDryRun) {
                $this->displayNextEvents($nextEvents, $output);
                $output->writeln('<comment>Dry run only. No events were executed.</comment>');

                return Command::SUCCESS;
            }

            $output->writeln('<info>Executing next events...</info>');
            $counter = $this->executeNextEvents($nextEvents, $output);

            $lastContactId = max(array_column($nextEvents, 'contact_id'));
            try {
                $contactLimiter->setBatchMinContactId($lastContactId);
            } catch (NoContactsFoundException) {
                $output->writeln('<error>No contacts found for the next events.</error>');

                return ExitCode::SUCCESS;
            }

            $this->writeCounts($output, $this->translator, $counter);
            $this->eventDispatcher->dispatch(new JobExtendTimeEvent());

            if (count($nextEvents) < $batchLimit) {
                $output->writeln('<info>All next events processed.</info>');

                return Command::SUCCESS;
            }

            $recordsProcessed += count($nextEvents);

            if ($recordsProcessed >= self::MAX_ALLOWED_RECORDS_EACH_PROCESS
                && $this->eventDispatcher->hasListeners(MaxAllowedRecordsReachedInSingleProcessEvent::class)) {
                $output->writeln('<error>Maximum allowed records reached. Stopping execution.</error>');

                $this->eventDispatcher->dispatch(new MaxAllowedRecordsReachedInSingleProcessEvent($campaignId));

                return Command::SUCCESS;
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Process a group of contacts for a specific event and date.
     *
     * @param array<mixed> $eventData Data including contact IDs
     */
    private function processEventGroup(
        Event $event,
        array $eventData,
        Counter $counter,
    ): void {
        $contacts = [];
        foreach ($eventData['contact_ids'] as $contactId) {
            $contact = $this->leadModel->getEntity($contactId);
            if ($contact) {
                $contacts[] = $contact;
            }
        }

        if (empty($contacts)) {
            return;
        }

        $eventCollection   = new ArrayCollection([$event]);
        $contactCollection = new ArrayCollection($contacts);

        $this->eventExecutioner->executeEventsForContacts(
            $eventCollection,
            $contactCollection,
            $counter
        );
    }
}
