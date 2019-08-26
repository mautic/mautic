<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Command;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\CampaignRepository;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\CampaignBundle\Membership\MembershipBuilder;
use Mautic\CoreBundle\Command\ModeratedCommand;
use Mautic\CoreBundle\Templating\Helper\FormatterHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\TranslatorInterface;

class UpdateLeadCampaignsCommand extends ModeratedCommand
{
    /**
     * @var CampaignRepository
     */
    private $campaignRepository;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var MembershipBuilder
     */
    private $membershipBuilder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var FormatterHelper
     */
    private $formatterHelper;

    /**
     * @var int
     */
    private $runLimit;

    /**
     * @var ContactLimiter
     */
    private $contactLimiter;

    /**
     * @var bool
     */
    private $quiet;

    /**
     * UpdateLeadCampaignsCommand constructor.
     *
     * @param CampaignRepository  $campaignRepository
     * @param TranslatorInterface $translator
     * @param MembershipBuilder   $membershipBuilder
     * @param LoggerInterface     $logger
     */
    public function __construct(
        CampaignRepository $campaignRepository,
        TranslatorInterface $translator,
        MembershipBuilder $membershipBuilder,
        LoggerInterface $logger,
        FormatterHelper $formatterHelper
    ) {
        $this->campaignRepository = $campaignRepository;
        $this->translator         = $translator;
        $this->membershipBuilder  = $membershipBuilder;
        $this->logger             = $logger;
        $this->formatterHelper    = $formatterHelper;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('mautic:campaigns:rebuild')
            ->setAliases(['mautic:campaigns:update'])
            ->setDescription('Rebuild campaigns based on contact segments.')
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

    protected function execute(InputInterface $input, OutputInterface $output)
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
        $this->quiet    = $input->getOption('quiet');
        $this->output   = ($this->quiet) ? new NullOutput() : $output;

        if ($threadId && $maxThreads && (int) $threadId > (int) $maxThreads) {
            $this->output->writeln('--thread-id cannot be larger than --max-thread');

            return 1;
        }

        if (!$this->checkRunStatus($input, $output, $id)) {
            return 0;
        }

        $this->contactLimiter = new ContactLimiter($batchLimit, $contactId, $contactMinId, $contactMaxId, $contactIds, $threadId, $maxThreads);

        if ($id) {
            $campaign = $this->campaignRepository->getEntity($id);
            if ($campaign === null) {
                $output->writeln('<error>'.$this->translator->trans('mautic.campaign.rebuild.not_found', ['%id%' => $id]).'</error>');

                return 0;
            }

            $this->updateCampaign($campaign);
        } else {
            $campaigns = $this->campaignRepository->getEntities(
                [
                    'iterator_mode' => true,
                ]
            );

            while (($results = $campaigns->next()) !== false) {
                // Get first item; using reset as the key will be the ID and not 0
                $campaign = reset($results);

                $this->updateCampaign($campaign);

                unset($results, $campaign);
            }
        }

        $this->completeRun();

        return 0;
    }

    /**
     * @param Campaign $campaign
     *
     * @throws \Exception
     */
    private function updateCampaign(Campaign $campaign)
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
}
