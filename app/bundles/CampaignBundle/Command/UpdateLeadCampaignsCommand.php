<?php

namespace Mautic\CampaignBundle\Command;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\CampaignRepository;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\CampaignBundle\Membership\MembershipBuilder;
use Mautic\CoreBundle\Command\ModeratedCommand;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Twig\Helper\FormatterHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class UpdateLeadCampaignsCommand extends ModeratedCommand
{
    private int $runLimit = 0;

    private ContactLimiter $contactLimiter;

    private bool $quiet = false;

    public function __construct(
        private CampaignRepository $campaignRepository,
        private TranslatorInterface $translator,
        private MembershipBuilder $membershipBuilder,
        private LoggerInterface $logger,
        private FormatterHelper $formatterHelper,
        PathsHelper $pathsHelper,
        CoreParametersHelper $coreParametersHelper
    ) {
        parent::__construct($pathsHelper, $coreParametersHelper);
    }

    protected function configure()
    {
        $this
            ->setName('mautic:campaigns:rebuild')
            ->setAliases(['mautic:campaigns:update'])
            ->addOption('--batch-limit', '-l', InputOption::VALUE_OPTIONAL, 'Set batch size of contacts to process per round. Defaults to 300.', 300)
            ->addOption(
                '--max-contacts',
                '-m',
                InputOption::VALUE_OPTIONAL,
                'Set max number of contacts to process per campaign for this script execution. Defaults to all.',
                0
            )
            ->addOption(
                '--campaign-id',
                '-i',
                InputOption::VALUE_OPTIONAL,
                'Build membership for a specific campaign.  Otherwise, all campaigns will be rebuilt.',
                null
            )
            ->addOption(
                '--contact-id',
                null,
                InputOption::VALUE_OPTIONAL,
                'Build membership for a specific contact.',
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
                'Build membership starting at a specific contact ID.',
                null
            )
            ->addOption(
                '--max-contact-id',
                null,
                InputOption::VALUE_OPTIONAL,
                'Build membership up to a specific contact ID.',
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
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id             = $input->getOption('campaign-id');
        $batchLimit     = $input->getOption('batch-limit');
        $contactMinId   = $input->getOption('min-contact-id');
        $contactMaxId   = $input->getOption('max-contact-id');
        $contactId      = $input->getOption('contact-id');
        $contactIds     = $this->formatterHelper->simpleCsvToArray($input->getOption('contact-ids'), 'int');
        $threadId       = $input->getOption('thread-id');
        $maxThreads     = $input->getOption('max-threads');
        $this->runLimit = $input->getOption('max-contacts');
        $this->quiet    = (bool) $input->getOption('quiet');
        $this->output   = ($this->quiet) ? new NullOutput() : $output;

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

        if ($threadId && $maxThreads && (int) $threadId > (int) $maxThreads) {
            $this->output->writeln('--thread-id cannot be larger than --max-thread');

            return \Symfony\Component\Console\Command\Command::FAILURE;
        }

        if (!$this->checkRunStatus($input, $output, $id)) {
            return \Symfony\Component\Console\Command\Command::SUCCESS;
        }

        $this->contactLimiter = new ContactLimiter($batchLimit, $contactId, $contactMinId, $contactMaxId, $contactIds, $threadId, $maxThreads);

        if ($id) {
            $campaign = $this->campaignRepository->getEntity($id);
            if (null === $campaign) {
                $output->writeln('<error>'.$this->translator->trans('mautic.campaign.rebuild.not_found', ['%id%' => $id]).'</error>');

                return \Symfony\Component\Console\Command\Command::FAILURE;
            }

            $this->updateCampaign($campaign);
        } else {
            $campaigns = $this->campaignRepository->getEntities(
                [
                    'iterable_mode' => true,
                ]
            );

            foreach ($campaigns as $campaign) {
                $this->updateCampaign($campaign);

                unset($campaign);
            }
        }

        $this->completeRun();

        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }

    /**
     * @throws \Exception
     */
    private function updateCampaign(Campaign $campaign): void
    {
        if (!$campaign->isPublished()) {
            return;
        }

        try {
            $this->output->writeln(
                '<info>'.$this->translator->trans('mautic.campaign.rebuild.rebuilding', ['%id%' => $campaign->getId()]).'</info>'
            );

            // Reset batch limiter
            $this->contactLimiter->resetBatchMinContactId();

            $this->membershipBuilder->build($campaign, $this->contactLimiter, $this->runLimit, ($this->quiet) ? null : $this->output);
        } catch (\Exception $exception) {
            if ('prod' !== MAUTIC_ENV) {
                // Throw the exception for dev/test mode
                throw $exception;
            }

            $this->logger->error('CAMPAIGN: '.$exception->getMessage());
        }

        // Don't detach in tests since this command will be ran multiple times in the same process
        if ('test' !== MAUTIC_ENV) {
            $this->campaignRepository->detachEntity($campaign);
        }

        $this->output->writeln('');
    }

    protected static $defaultDescription = 'Rebuild campaigns based on contact segments.';
}
