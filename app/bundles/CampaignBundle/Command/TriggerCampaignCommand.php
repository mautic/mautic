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
use Mautic\CoreBundle\Templating\Helper\FormatterHelper;
use Mautic\LeadBundle\Helper\SegmentCountCacheHelper;
use Mautic\LeadBundle\Model\ListModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\TranslatorInterface;

class TriggerCampaignCommand extends ModeratedCommand
{
    use WriteCountTrait;

    /**
     * @var CampaignRepository
     */
    private $campaignRepository;

    /**
     * @var EventDispatcher
     */
    private $dispatcher;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var KickoffExecutioner
     */
    private $kickoffExecutioner;

    /**
     * @var ScheduledExecutioner
     */
    private $scheduledExecutioner;

    /**
     * @var InactiveExecutioner
     */
    private $inactiveExecutioner;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var FormatterHelper
     */
    private $formatterHelper;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var bool
     */
    private $kickoffOnly = false;

    /**
     * @var bool
     */
    private $inactiveOnly = false;

    /**
     * @var bool
     */
    private $scheduleOnly = false;

    /**
     * @var ContactLimiter
     */
    private $limiter;

    /**
     * @var Campaign
     */
    private $campaign;

    /**
     * @var ListModel
     */
    private $listModel;

    /**
     * @var SegmentCountCacheHelper
     */
    private $segmentCountCacheHelper;

    public function __construct(
        CampaignRepository $campaignRepository,
        EventDispatcherInterface $dispatcher,
        TranslatorInterface $translator,
        KickoffExecutioner $kickoffExecutioner,
        ScheduledExecutioner $scheduledExecutioner,
        InactiveExecutioner $inactiveExecutioner,
        LoggerInterface $logger,
        FormatterHelper $formatterHelper,
        ListModel $listModel,
        SegmentCountCacheHelper $segmentCountCacheHelper
    ) {
        parent::__construct();

        $this->campaignRepository        = $campaignRepository;
        $this->dispatcher                = $dispatcher;
        $this->translator                = $translator;
        $this->kickoffExecutioner        = $kickoffExecutioner;
        $this->scheduledExecutioner      = $scheduledExecutioner;
        $this->inactiveExecutioner       = $inactiveExecutioner;
        $this->logger                    = $logger;
        $this->formatterHelper           = $formatterHelper;
        $this->listModel                 = $listModel;
        $this->segmentCountCacheHelper   = $segmentCountCacheHelper;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('mautic:campaigns:trigger')
            ->setDescription('Trigger timed events for published campaigns.')
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
            );

        parent::configure();
    }

    /**
     * @return int|null
     *
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $quiet              = $input->getOption('quiet');
        $this->output       = $quiet ? new NullOutput() : $output;
        $this->kickoffOnly  = $input->getOption('kickoff-only');
        $this->scheduleOnly = $input->getOption('scheduled-only');
        $this->inactiveOnly = $input->getOption('inactive-only');

        $batchLimit    = $input->getOption('batch-limit');
        $campaignLimit = $input->getOption('campaign-limit');
        $contactMinId  = $input->getOption('min-contact-id');
        $contactMaxId  = $input->getOption('max-contact-id');
        $contactId     = $input->getOption('contact-id');
        $contactIds    = $this->formatterHelper->simpleCsvToArray($input->getOption('contact-ids'), 'int');
        $threadId      = $input->getOption('thread-id');
        $maxThreads    = $input->getOption('max-threads');

        if ($threadId && $maxThreads && (int) $threadId > (int) $maxThreads) {
            $this->output->writeln('--thread-id cannot be larger than --max-thread');

            return 1;
        }

        $this->limiter = new ContactLimiter($batchLimit, $contactId, $contactMinId, $contactMaxId, $contactIds, $threadId, $maxThreads, $campaignLimit);

        defined('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED') or define('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED', 1);

        $id = $input->getOption('campaign-id');
        if (!$this->checkRunStatus($input, $this->output, $id)) {
            return 0;
        }

        // Specific campaign;
        if ($id) {
            /** @var \Mautic\CampaignBundle\Entity\Campaign $campaign */
            if ($campaign = $this->campaignRepository->getEntity($id)) {
                $this->triggerCampaign($campaign);
            } else {
                $output->writeln('<error>'.$this->translator->trans('mautic.campaign.rebuild.not_found', ['%id%' => $id]).'</error>');
            }

            $this->completeRun();

            return 0;
        }

        // All published campaigns
        /** @var \Doctrine\ORM\Internal\Hydration\IterableResult $campaigns */
        $campaigns = $this->campaignRepository->getEntities(['iterator_mode' => true]);

        while (false !== ($next = $campaigns->next())) {
            // Key is ID and not 0
            $campaign = reset($next);
            $this->triggerCampaign($campaign);
            if ($this->limiter->hasCampaignLimit()) {
                $this->limiter->resetCampaignLimitRemaining();
            }
        }

        $this->completeRun();

        return 0;
    }

    /**
     * @return bool
     */
    protected function dispatchTriggerEvent(Campaign $campaign)
    {
        if ($this->dispatcher->hasListeners(CampaignEvents::CAMPAIGN_ON_TRIGGER)) {
            /** @var CampaignTriggerEvent $event */
            $event = $this->dispatcher->dispatch(
                CampaignEvents::CAMPAIGN_ON_TRIGGER,
                new CampaignTriggerEvent($campaign)
            );

            return $event->shouldTrigger();
        }

        return true;
    }

    /**
     * @throws Exception
     */
    private function triggerCampaign(Campaign $campaign)
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
        } catch (Exception $exception) {
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
    private function executeKickoff()
    {
        //trigger starting action events for newly added contacts
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
    private function executeScheduled()
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
    private function executeInactive()
    {
        //find and trigger "no" path events
        $this->output->writeln('<comment>'.$this->translator->trans('mautic.campaign.trigger.negative').'</comment>');

        $counter = $this->inactiveExecutioner->execute($this->campaign, $this->limiter, $this->output);

        $this->writeCounts($this->output, $this->translator, $counter);
    }

    /**
     * @throws Exception
     */
    private function updateCampaignSegmentContactCount(Campaign $campaign): void
    {
        $segmentIds = $this->campaignRepository->getCampaignListIds((int) $campaign->getId());

        foreach ($segmentIds as $segmentId) {
            $totalLeadCount = $this->listModel->getRepository()->getLeadCount($segmentId);
            $this->segmentCountCacheHelper->setSegmentContactCount($segmentId, (int) $totalLeadCount);
        }
    }
}
