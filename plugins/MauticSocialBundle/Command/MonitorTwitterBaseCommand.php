<?php

namespace MauticPlugin\MauticSocialBundle\Command;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use MauticPlugin\MauticSocialBundle\Entity\Monitoring;
use MauticPlugin\MauticSocialBundle\Event\SocialMonitorEvent;
use MauticPlugin\MauticSocialBundle\Helper\TwitterCommandHelper;
use MauticPlugin\MauticSocialBundle\Integration\TwitterIntegration;
use MauticPlugin\MauticSocialBundle\SocialEvents;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

abstract class MonitorTwitterBaseCommand extends Command
{
    /**
     * @var TwitterIntegration
     */
    protected $twitter;

    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var int
     */
    protected $maxRuns = 5;

    /**
     * @var int
     */
    protected $runCount = 0;

    /**
     * @var int
     */
    protected $queryCount = 100;

    public function __construct(
        protected EventDispatcherInterface $dispatcher,
        protected Translator $translator,
        protected IntegrationHelper $integrationHelper,
        private TwitterCommandHelper $twitterCommandHelper,
        CoreParametersHelper $coreParametersHelper
    ) {
        $this->translator->setLocale($coreParametersHelper->get('locale', 'en_US'));

        parent::__construct();
    }

    /**
     * Command configuration. Set the name, description, and options here.
     */
    protected function configure()
    {
        $this
            ->addOption(
                'mid',
                'i',
                InputOption::VALUE_REQUIRED,
                'The id of the monitor record'
            )
            ->addOption(
                'max-runs',
                null,
                InputOption::VALUE_REQUIRED,
                'The maximum number of recursive iterations permitted',
                5
            )
            ->addOption(
                'query-count',
                null,
                InputOption::VALUE_OPTIONAL,
                'The number of records to search for per iteration.',
                100
            )
            ->addOption(
                'show-posts',
                null,
                InputOption::VALUE_NONE,
                'Use this option to display the posts retrieved'
            )
            ->addOption(
                'show-stats',
                null,
                InputOption::VALUE_NONE,
                'Use this option to display the stats of the tweets fetched'
            );
    }

    /**
     * Used in various areas to set name of the network being searched.
     *
     * @return string twitter|facebook etc..
     */
    abstract public function getNetworkName();

    /**
     * Search for tweets by creating your own search criteria.
     *
     * @param Monitoring $monitor
     *
     * @return array The results of makeRequest
     */
    abstract protected function getTweets($monitor);

    /**
     * Main execution method. Gets the integration settings, processes the search criteria.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input        = $input;
        $this->output       = $output;
        $this->maxRuns      = $this->input->getOption('max-runs');
        $this->queryCount   = $this->input->getOption('query-count');
        $twitterIntegration = $this->integrationHelper->getIntegrationObject('Twitter');

        if (false === $twitterIntegration || false === $twitterIntegration->getIntegrationSettings()->getIsPublished()) {
            $this->output->writeln($this->translator->trans('mautic.social.monitoring.twitter.not.published'));

            return Command::FAILURE;
        }

        \assert($twitterIntegration instanceof TwitterIntegration);
        $this->twitter = $twitterIntegration;

        if (!$this->twitter->isAuthorized()) {
            $this->output->writeln($this->translator->trans('mautic.social.monitoring.twitter.not.configured'));

            return Command::FAILURE;
        }

        // get the mid from the cli
        $mid = (int) $input->getOption('mid');

        if (!$mid) {
            $this->output->writeln($this->translator->trans('mautic.social.monitoring.twitter.mid.empty'));

            return Command::FAILURE;
        }

        $this->twitterCommandHelper->setOutput($output);

        $monitor = $this->twitterCommandHelper->getMonitor($mid);

        if (!$monitor || !$monitor->getId()) {
            $this->output->writeln($this->translator->trans('mautic.social.monitoring.twitter.monitor.does.not.exist', ['%id%' => $mid]));

            return Command::FAILURE;
        }

        // process the monitor
        $this->processMonitor($monitor);

        $this->dispatcher->dispatch(
            new SocialMonitorEvent($this->getNetworkName(), $monitor, $this->twitterCommandHelper->getManipulatedLeads(), $this->twitterCommandHelper->getNewLeadsCount(), $this->twitterCommandHelper->getUpdatedLeadsCount()),
            SocialEvents::MONITOR_POST_PROCESS
        );

        return Command::SUCCESS;
    }

    /**
     * Process the monitor record.
     *
     * @Note: Keeping this method here instead of in the twitterCommandHelper
     *        so that the hashtag and mention commands can easily extend it.
     *
     * @param Monitoring $monitor
     *
     * @return bool
     */
    protected function processMonitor($monitor)
    {
        $results = $this->getTweets($monitor);

        if (false === $results || !isset($results['statuses'])) {
            $this->output->writeln('No statuses found');

            if (!empty($results['errors'])) {
                foreach ($results['errors'] as $error) {
                    $this->output->writeln($error['code'].': '.$error['message']);
                }
            }

            return 0;
        }

        if (count($results['statuses'])) {
            $this->twitterCommandHelper->createLeadsFromStatuses($results['statuses'], $monitor);
        } else {
            $this->output->writeln($this->translator->trans('mautic.social.monitoring.twitter.no.new.tweets'));
        }

        $this->twitterCommandHelper->setMonitorStats($monitor, $results['search_metadata']);
        $this->printInformation($monitor, $results);

        // get stats after being updated
        $stats = $monitor->getStats();

        ++$this->runCount;

        // if we have stats and a next results request, process it here
        // @todo add a check for max iterations
        if (is_array($stats) && array_key_exists('max_id_str', $stats)
            && ($this->runCount < $this->maxRuns)
            && count($results['statuses'])
        ) {
            // recursive
            $this->processMonitor($monitor);
        }

        return 0;
    }

    /**
     * Prints all the returned tweets.
     *
     * @param array $statuses
     */
    protected function printTweets($statuses)
    {
        if (!$this->input->getOption('show-posts') && $this->output->getVerbosity() < OutputInterface::VERBOSITY_VERY_VERBOSE) {
            return;
        }

        foreach ($statuses as $status) {
            $this->output->writeln('-- tweet -- ');
            $this->output->writeln('ID: '.$status['id']);
            $this->output->writeln('Message: '.$status['text']);
            $this->output->writeln('Handle: '.$status['user']['screen_name']);
            $this->output->writeln('Name: '.$status['user']['name']);
            $this->output->writeln('Location: '.$status['user']['location']);
            $this->output->writeln('Profile Img: '.$status['user']['profile_image_url']);
            $this->output->writeln('Profile Description: '.$status['user']['description']);
            $this->output->writeln('// tweet // ');
        }
    }

    /**
     * Prints the search query metadata from twitter.
     * Only shows stats if explicitly requested or if we're in verbose mode.
     *
     * @param array $metadata
     */
    protected function printQueryMetadata($metadata)
    {
        if (!$this->input->getOption('show-stats') && $this->output->getVerbosity() < OutputInterface::VERBOSITY_VERBOSE) {
            return;
        }

        $this->output->writeln('-- search meta -- ');
        $this->output->writeln('max_id_str: '.$metadata['max_id_str']);
        $this->output->writeln('since_id_str: '.$metadata['since_id_str']);
        $this->output->writeln('Page Count: '.$metadata['count']);
        $this->output->writeln('query: '.$metadata['query']);

        if (array_key_exists('next_results', $metadata)) {
            $this->output->writeln('next results: '.$metadata['next_results']);
        }

        $this->output->writeln('// search meta // ');
    }

    /**
     * Prints a summary of the search query.
     * Only shows stats if explicitly requested or if we're in verbose mode.
     *
     * @param Monitoring $monitor
     * @param array      $results
     */
    protected function printInformation($monitor, $results)
    {
        if (!$this->input->getOption('show-stats') && $this->output->getVerbosity() < OutputInterface::VERBOSITY_VERBOSE) {
            return;
        }

        $this->output->writeln('------------------------');
        $this->output->writeln($monitor->getTitle());
        $this->output->writeln('Published '.$monitor->isPublished());
        $this->output->writeln($monitor->getNetworkType());
        $this->output->writeln('New Leads '.$this->twitterCommandHelper->getNewLeadsCount());
        $this->output->writeln('Updated Leads '.$this->twitterCommandHelper->getUpdatedLeadsCount());
        $this->printQueryMetadata($results['search_metadata']);
        $this->printTweets($results['statuses']);
        $this->output->writeln('------------------------');
    }
}
