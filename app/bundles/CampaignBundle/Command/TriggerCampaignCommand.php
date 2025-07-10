<?php

namespace Mautic\CampaignBundle\Command;

use Exception;
use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\CampaignRepository;
use Mautic\CampaignBundle\Event\CampaignTriggerEvent;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\CampaignBundle\Executioner\InactiveExecutioner;
use Mautic\CampaignBundle\Executioner\KickoffExecutioner;
use Mautic\CampaignBundle\Executioner\ScheduledExecutioner;
use Mautic\CoreBundle\Command\ModeratedCommand;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Twig\Helper\FormatterHelper;
use Mautic\LeadBundle\Helper\SegmentCountCacheHelper;
use Mautic\LeadBundle\Model\ListModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class TriggerCampaignCommand extends ModeratedCommand
{
    use WriteCountTrait;

    private bool $kickoffOnly  = false;

    private bool $inactiveOnly = false;

    private bool $scheduleOnly = false;

    /**
     * @var OutputInterface
     */
    protected $output;

    private ?ContactLimiter $limiter = null;

    private ?Campaign $campaign = null;

    public function __construct(
        private CampaignRepository $campaignRepository,
        private EventDispatcherInterface $dispatcher,
        private TranslatorInterface $translator,
        private KickoffExecutioner $kickoffExecutioner,
        private ScheduledExecutioner $scheduledExecutioner,
        private InactiveExecutioner $inactiveExecutioner,
        private LoggerInterface $logger,
        private FormatterHelper $formatterHelper,
        private ListModel $listModel,
        private SegmentCountCacheHelper $segmentCountCacheHelper,
        PathsHelper $pathsHelper,
        CoreParametersHelper $coreParametersHelper,
    ) {
        parent::__construct($pathsHelper, $coreParametersHelper);
    }

    protected function configure()
    {
        $this
            ->setName('mautic:campaigns:trigger')
            ->addOption(
                '--campaign-id',
                '-i',
                InputOption::VALUE_OPTIONAL,
                'Trigger events for a specific campaign.  Otherwise, all campaigns will be triggered.',
                null
            )
            ->addOption(
                '--campaign-limit',
                null,
                InputOption::VALUE_OPTIONAL,
                'Limit number of contacts on a per campaign basis',
                null
            )
            ->addOption(
                '--contact-id',
                null,
                InputOption::VALUE_OPTIONAL,
                'Trigger events for a specific contact.',
                null
            )
            ->addOption(
                '--contact-ids',
                null,
                InputOption::VALUE_OPTIONAL,
                'CSV of contact IDs to evaluate.'
            )
            ->addOption(
                '--min-contact-id',
                null,
                InputOption::VALUE_OPTIONAL,
                'Trigger events starting at a specific contact ID.',
                null
            )
            ->addOption(
                '--max-contact-id',
                null,
                InputOption::VALUE_OPTIONAL,
                'Trigger events starting up to a specific contact ID.',
                null
            )
            ->addOption(
                '--thread-id',
                null,
                InputOption::VALUE_OPTIONAL,
                'The number of this current process if running multiple in parallel.'
            )
            ->addOption(
                '--max-threads',
                null,
                InputOption::VALUE_OPTIONAL,
                'The maximum number of processes you intend to run in parallel.'
            )
            ->addOption(
                '--kickoff-only',
                null,
                InputOption::VALUE_NONE,
                'Just kickoff the campaign'
            )
            ->addOption(
                '--scheduled-only',
                null,
                InputOption::VALUE_NONE,
                'Just execute scheduled events'
            )
            ->addOption(
                '--inactive-only',
                null,
                InputOption::VALUE_NONE,
                'Just execute scheduled events'
            )
            ->addOption(
                '--batch-limit',
                '-l',
                InputOption::VALUE_OPTIONAL,
                'Set batch size of contacts to process per round. Defaults to 100.',
                100
            )
            ->addOption(
                'exclude',
                'd',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                'Exclude a specific campaign from being triggered. Otherwise, all campaigns will be triggered.',
                []
            );

        parent::configure();
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $quiet              = $input->getOption('quiet');
        $this->output       = $quiet ? new NullOutput() : $output;
        $this->kickoffOnly  = $input->getOption('kickoff-only');
        $this->scheduleOnly = $input->getOption('scheduled-only');
        $this->inactiveOnly = $input->getOption('inactive-only');

        $id               = $input->getOption('campaign-id');
        $batchLimit       = $input->getOption('batch-limit');
        $campaignLimit    = $input->getOption('campaign-limit');
        $contactMinId     = $input->getOption('min-contact-id');
        $contactMaxId     = $input->getOption('max-contact-id');
        $contactId        = $input->getOption('contact-id');
        $contactIds       = $this->formatterHelper->simpleCsvToArray($input->getOption('contact-ids'), 'int');
        $threadId         = $input->getOption('thread-id');
        $maxThreads       = $input->getOption('max-threads');
        $excludeCampaigns = $input->getOption('exclude');

        if (is_numeric($id)) {
            $id = (int) $id;
        }

        if (is_numeric($maxThreads)) {
            $maxThreads = (int) $maxThreads;
        }

        if (is_numeric($threadId)) {
            $threadId = (int) $threadId;
        }

        if (is_numeric($contactMaxId)) {
            $contactMaxId = (int) $contactMaxId;
        }

        if (is_numeric($contactMinId)) {
            $contactMinId = (int) $contactMinId;
        }

        if (is_numeric($contactId)) {
            $contactId = (int) $contactId;
        }

        if (is_numeric($campaignLimit)) {
            $campaignLimit = (int) $campaignLimit;
        }

        if ($threadId && $maxThreads && (int) $threadId > (int) $maxThreads) {
            $this->output->writeln('--thread-id cannot be larger than --max-thread');

            return \Symfony\Component\Console\Command\Command::FAILURE;
        }

        $this->limiter = new ContactLimiter($batchLimit, $contactId, $contactMinId, $contactMaxId, $contactIds, $threadId, $maxThreads, $campaignLimit);

        defined('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED') or define('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED', 1);

        $moderationKey = sprintf('%s-%s', $id, $threadId);
        if (!$this->checkRunStatus($input, $this->output, $moderationKey)) {
            return \Symfony\Component\Console\Command\Command::SUCCESS;
        }

        // Specific campaign;
        if ($id) {
            $statusCode = 0;
            /** @var Campaign $campaign */
            if ($campaign = $this->campaignRepository->getEntity($id)) {
                $this->triggerCampaign($campaign);
            } else {
                $output->writeln('<error>'.$this->translator->trans('mautic.campaign.rebuild.not_found', ['%id%' => $id]).'</error>');
                $statusCode = 1;
            }

            $this->completeRun();

            return (int) $statusCode;
        }

        // All published campaigns
        $filter = [
            'iterable_mode' => true,
            'orderBy'       => 'c.dateAdded',
            'orderByDir'    => 'DESC',
        ];

        // exclude excluded campaigns
        if (is_array($excludeCampaigns) && count($excludeCampaigns) > 0) {
            $filter['filter'] = [
                'force' => [
                    [
                        'expr'   => 'notIn',
                        'column' => $this->campaignRepository->getTableAlias().'.id',
                        'value'  => $excludeCampaigns,
                    ],
                ],
            ];
        }

        /** @var \Doctrine\ORM\Internal\Hydration\IterableResult $campaigns */
        $campaigns = $this->campaignRepository->getEntities($filter);

        foreach ($campaigns as $campaign) {
            $this->triggerCampaign($campaign);
            if ($this->limiter->hasCampaignLimit()) {
                $this->limiter->resetCampaignLimitRemaining();
            }
        }

        $this->completeRun();

        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }

    /**
     * @return bool
     */
    protected function dispatchTriggerEvent(Campaign $campaign)
    {
        if ($this->dispatcher->hasListeners(CampaignEvents::CAMPAIGN_ON_TRIGGER)) {
            /** @var CampaignTriggerEvent $event */
            $event = $this->dispatcher->dispatch(
                new CampaignTriggerEvent($campaign),
                CampaignEvents::CAMPAIGN_ON_TRIGGER
            );

            return $event->shouldTrigger();
        }

        return true;
    }

    /**
     * @throws \Exception
     */
    private function triggerCampaign(Campaign $campaign): void
    {
        if (!$campaign->isPublished()) {
            return;
        }

        if (!$this->dispatchTriggerEvent($campaign)) {
            return;
        }

        $this->campaign = $campaign;

        try {
            $this->output->writeln('<info>'.$this->translator->trans('mautic.campaign.trigger.triggering', ['%id%' => $campaign->getId()]).'</info>');
            // Reset batch limiter
            $this->limiter->resetBatchMinContactId();

            // Execute starting events
            if (!$this->inactiveOnly && !$this->scheduleOnly) {
                $this->executeKickoff();
            }

            // Reset batch limiter
            $this->limiter->resetBatchMinContactId();

            // Execute scheduled events
            if (!$this->inactiveOnly && !$this->kickoffOnly) {
                $this->executeScheduled();
            }

            // Reset batch limiter
            $this->limiter->resetBatchMinContactId();

            // Execute inactive events
            if (!$this->scheduleOnly && !$this->kickoffOnly) {
                $this->executeInactive();
            }
        } catch (\Exception $exception) {
            if ('prod' !== MAUTIC_ENV) {
                // Throw the exception for dev/test mode
                throw $exception;
            }

            $this->logger->error('CAMPAIGN: '.$exception->getMessage());
        } finally {
            // Update campaign linked segment cache count.
            $this->updateCampaignSegmentContactCount($campaign);
        }

        // Don't detach in tests since this command will be ran multiple times in the same process
        if ('test' !== MAUTIC_ENV) {
            $this->campaignRepository->detachEntity($campaign);
        }
    }

    /**
     * @throws \Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogNotProcessedException
     * @throws \Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogPassedAndFailedException
     * @throws \Mautic\CampaignBundle\Executioner\Exception\CannotProcessEventException
     * @throws \Mautic\CampaignBundle\Executioner\Scheduler\Exception\NotSchedulableException
     */
    private function executeKickoff(): void
    {
        // trigger starting action events for newly added contacts
        $this->output->writeln('<comment>'.$this->translator->trans('mautic.campaign.trigger.starting').'</comment>');

        $counter = $this->kickoffExecutioner->execute($this->campaign, $this->limiter, $this->output);

        $this->writeCounts($this->output, $this->translator, $counter);
    }

    /**
     * @throws \Doctrine\ORM\Query\QueryException
     * @throws \Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogNotProcessedException
     * @throws \Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogPassedAndFailedException
     * @throws \Mautic\CampaignBundle\Executioner\Exception\CannotProcessEventException
     * @throws \Mautic\CampaignBundle\Executioner\Scheduler\Exception\NotSchedulableException
     */
    private function executeScheduled(): void
    {
        $this->output->writeln('<comment>'.$this->translator->trans('mautic.campaign.trigger.scheduled').'</comment>');

        $counter = $this->scheduledExecutioner->execute($this->campaign, $this->limiter, $this->output);

        $this->writeCounts($this->output, $this->translator, $counter);
    }

    /**
     * @throws \Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogNotProcessedException
     * @throws \Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogPassedAndFailedException
     * @throws \Mautic\CampaignBundle\Executioner\Exception\CannotProcessEventException
     * @throws \Mautic\CampaignBundle\Executioner\Scheduler\Exception\NotSchedulableException
     */
    private function executeInactive(): void
    {
        // find and trigger "no" path events
        $this->output->writeln('<comment>'.$this->translator->trans('mautic.campaign.trigger.negative').'</comment>');

        $counter = $this->inactiveExecutioner->execute($this->campaign, $this->limiter, $this->output);

        $this->writeCounts($this->output, $this->translator, $counter);
    }

    /**
     * @throws \Exception
     */
    private function updateCampaignSegmentContactCount(Campaign $campaign): void
    {
        $segmentIds = $this->campaignRepository->getCampaignListIds((int) $campaign->getId());

        foreach ($segmentIds as $segmentId) {
            $totalLeadCount = $this->listModel->getRepository()->getLeadCount($segmentId);
            $this->segmentCountCacheHelper->setSegmentContactCount($segmentId, (int) $totalLeadCount);
        }
    }

    protected static $defaultDescription = 'Trigger timed events for published campaigns.';
}
