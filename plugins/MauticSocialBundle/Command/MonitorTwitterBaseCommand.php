<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle\Command;

use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticSocialBundle\Event\SocialMonitorEvent;
use MauticPlugin\MauticSocialBundle\Exception\ExitMonitorException;
use MauticPlugin\MauticSocialBundle\Model\MonitoringModel;
use MauticPlugin\MauticSocialBundle\SocialEvents;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class MonitorTwitterBaseCommand extends ContainerAwareCommand
{
    /** @var \MauticPlugin\MauticSocialBundle\Integration\TwitterIntegration */
    protected $twitter;

    protected $output;

    protected $input;

    protected $maxRuns = 5;

    protected $runCount = 0;

    protected $newLeads = 0;

    protected $updatedLeads = 0;

    protected $queryCount = 100;

    protected $manipulatedLeads = [];

    /**
     * @var MonitoringModel
     */
    protected $monitoringModel;

    /*
     * Command configuration. Set the name, description, and options here.
     */
    protected function configure()
    {
        $this->setHelp(
            <<<'EOT'
            I'm not sure what to put here yet
EOT
        )
            ->addOption('mid', null, InputOption::VALUE_REQUIRED, 'The id of the monitor record')
            ->addOption('max-runs', null, InputOption::VALUE_REQUIRED, 'The maximum number of recursive iterations permitted')
            ->addOption('query-count', null, InputOption::VALUE_OPTIONAL, 'The number of records to search for per iteration. Default is 100.', 100)
            ->addOption('show-posts', null, InputOption::VALUE_NONE, 'Use this option to display the posts retrieved')
            ->addOption('show-stats', null, InputOption::VALUE_NONE, 'Use this option to display the stats of the tweets fetched');
    }

    /*
     * Main execution method. Gets the integration settings, processes the search criteria.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input           = $input;
        $this->output          = $output;
        $this->maxRuns         = $this->input->getOption('max-runs');
        $this->queryCount      = $this->input->getOption('query-count');
        $this->monitoringModel = $this->getContainer()->get('mautic.social.model.monitoring');
        $this->translator      = $this->getContainer()->get('translator');

        $this->translator->setLocale($this->getContainer()->getParameter('mautic.locale'));

        // get the twitter integration
        $this->twitter = $this->getTwitterIntegration();

        if (!$this->twitter->isAuthorized()) {
            $this->output->writeln($this->translator->trans('mautic.social.monitoring.twitter.not.configured'));

            return 1;
        }

        // get the mid from the cli
        $mid = $input->getOption('mid');

        if (!$mid) {
            $this->output->writeln($this->translator->trans('mautic.social.monitoring.twitter.mid.empty'));

            return 1;
        }

        // monitor record
        $monitor = $this->getMonitor($mid);

        if (!$monitor || !$monitor->getId()) {
            $this->output->writeln($this->translator->trans('mautic.social.monitoring.twitter.monitor.does.not.exist', ['%id%' => $mid]));

            return 1;
        }

        // process the monitor
        $this->processMonitor($monitor);

        $dispatcher = $this->getContainer()->get('event_dispatcher');

        $dispatcher->dispatch(
            SocialEvents::MONITOR_POST_PROCESS,
            new SocialMonitorEvent($this->getNetworkName(), $monitor, $this->manipulatedLeads, $this->newLeads, $this->updatedLeads)
        );

        return 0;
    }

    /*
     * Process the monitor record
     */
    protected function processMonitor($monitor)
    {
        $results = $this->getTweets($monitor);

        if ($results === false || !isset($results['statuses'])) {
            $this->output->writeln('No statuses found');

            if (!empty($results['errors'])) {
                foreach ($results['errors'] as $error) {
                    $this->output->writeln($error['code'].': '.$error['message']);
                }
            }

            return;
        }

        if (count($results['statuses'])) {
            $this->createLeadsFromStatuses($results['statuses'], $monitor);
        } else {
            $this->output->writeln($this->translator->trans('mautic.social.monitoring.twitter.no.new.tweets'));
        }

        $this->setMonitorStats($monitor, $results['search_metadata']);

        // @todo set this up to only print on verbose
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
    }

    /*
     * Search for tweets by creating your own search criteria.
     *
     * @return a makeRequest results array object.
     */
    abstract protected function getTweets($monitor);

    /*
     * Get monitor record entity
     *
     * @return \MauticPlugin\MauticSocialBundle\Entity\Monitoring $entity
     */
    protected function getMonitor($mid)
    {
        // get the entity record
        $entity = $this->monitoringModel->getEntity($mid);

        return $entity;
    }

    /*
     * Processes a list of tweets and creates / updates leads in Mautic
     *
     */
    protected function createLeadsFromStatuses($statusList, $monitor)
    {
        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $leadModel = $this->getContainer()
            ->get('mautic.lead.model.lead');

        /** @var \Mautic\LeadBundle\Model\FieldModel $leadFieldModel */
        $leadFieldModel = $this->getContainer()
            ->get('mautic.lead.model.field');

        // handle field
        $handleField = $this->getContainer()->getParameter('mautic.twitter_handle_field', $this->getNetworkName());

        $leadField = $leadFieldModel->getRepository()->findOneBy(
            [
                'alias' => $handleField,
            ]
        );

        if (!$leadField) {
            // Field has been deleted or something
            $this->output->writeln($this->translator->trans('mautic.social.monitoring.twitter.filed.not.found'));

            return;
        }

        $handleFieldGroup = $leadField->getGroup();

        // Just a means to let any LeadEvents listeners know that many leads are likely coming in case that matters to their logic
        defined('MASS_LEADS_MANIPULATION') or define('MASS_LEADS_MANIPULATION', 1);
        defined('SOCIAL_MONITOR_IMPORT') or define('SOCIAL_MONITOR_IMPORT', 1);

        // Get a list of existing leads to tone down on queries
        $twitterLeads = [];
        $qb           = $leadModel->getRepository()->createQueryBuilder('f');
        $expr         = $qb->expr();
        foreach ($statusList as $status) {
            if ($status['user']['screen_name']) {
                $twitterLeads[$status['user']['screen_name']] = $expr->literal($status['user']['screen_name']);
            }
        }
        unset($qb, $expr);

        if (!empty($twitterLeads)) {
            $leads = $leadModel->getRepository()->getEntities(
                [
                    'filter' => [
                        'force' => [
                            [
                                'column' => 'l.'.$handleField,
                                'expr'   => 'in',
                                'value'  => $twitterLeads,
                            ],
                        ],
                    ],
                ]
            );

            // Key by twitter handle
            $twitterLeads = [];
            foreach ($leads as $leadId => $lead) {
                $fields                       = $lead->getFields();
                $twitterHandle                = strtolower($fields[$handleFieldGroup][$handleField]['value']);
                $twitterLeads[$twitterHandle] = $lead;
            }

            unset($leads);
        }

        $processedLeads = [];
        foreach ($statusList as $status) {
            if (empty($status['user']['screen_name'])) {
                continue;
            }

            // tweet timestamp
            $tweetTimestamp = $status['created_at'];
            $lastActive     = new \DateTime($tweetTimestamp);
            $handle         = strtolower($status['user']['screen_name']);

            /* @var \Mautic\LeadBundle\Entity\Lead $leadEntity */
            if (!isset($processedLeads[$handle])) {
                $processedLeads[$handle] = 1;

                if (isset($twitterLeads[$handle])) {
                    ++$this->updatedLeads;

                    $isNew      = false;
                    $leadEntity = $twitterLeads[$handle];

                    $this->output->writeln('Updating existing lead ID #'.$leadEntity->getId().' ('.$handle.')');
                } else {
                    ++$this->newLeads;

                    $this->output->writeln('Creating new lead');

                    $isNew      = true;
                    $leadEntity = new Lead();
                    $leadEntity->setNewlyCreated(true);

                    list($firstName, $lastName) = $this->splitName($status['user']['name']);

                    // build new lead fields
                    $fields = [
                        $handleField => $handle,
                        'firstname'  => $firstName,
                        'lastname'   => $lastName,
                        'country'    => $status['user']['location'],
                    ];

                    // set field values
                    $leadModel->setFieldValues($leadEntity, $fields, false);

                    // mark as identified just to be sure
                    $leadEntity->setDateIdentified(new \DateTime());
                }

                $leadEntity->setPreferredProfileImage(ucfirst($this->getNetworkName()));

                // save the lead now
                $leadEntity->setLastActive($lastActive->format('Y-m-d H:i:s'));

                try {
                    // save the lead entity
                    $leadModel->saveEntity($leadEntity);

                    // Note lead ids
                    $this->manipulatedLeads[$leadEntity->getId()] = 1;

                    // add lead entity to the lead list
                    $leadModel->addToLists($leadEntity, $monitor->getLists());

                    if ($isNew) {
                        $this->setMonitorLeadStat($monitor, $leadEntity);
                    }
                } catch (ExitMonitorException $e) {
                    $this->output->writeln($e->getMessage());

                    return;
                } catch (\Exception $e) {
                    $this->output->writeln($e->getMessage());

                    continue;
                }
            }

            // Increment the post count
            $this->incrementPostCount($monitor, $status);
        }

        unset($processedLeads);

        return;
    }

    /*
     * handles splitting a string handle into first / last name based on a space
     *
     * @return array($first, $last)
     */
    private function splitName($name)
    {
        // array the entire name
        $nameParts = explode(' ', $name);

        // last part of the array is our last
        $lastName = array_pop($nameParts);

        // push the rest of the name into first name
        $firstName = implode(' ', $nameParts);

        return [$firstName, $lastName];
    }

    /*
     * Add new monitoring_leads record to track leads found via the search
     */
    private function setMonitorLeadStat($monitor, $lead)
    {
        // track the lead in our monitor_leads table
        $monitorLead = new \MauticPlugin\MauticSocialBundle\Entity\Lead();
        $monitorLead->setMonitor($monitor);
        $monitorLead->setLead($lead);
        $monitorLead->setDateAdded(new \DateTime());

        /* @var \MauticPlugin\MauticSocialBundle\Entity\LeadRepository $monitorRepository */
        $monitorRepository = $this->getContainer()->get('doctrine.orm.entity_manager')->getRepository('MauticSocialBundle:lead');

        // save it
        $monitorRepository->saveEntity($monitorLead);
    }

    /*
     * Increment the post counter
     */
    protected function incrementPostCount($monitor, $tweet)
    {
        /** @var \MauticPlugin\MauticSocialBundle\Model\PostCountModel $postCount */
        $postCount = $this->getContainer()
            ->get('mautic.social.model.postcount');

        $date = new \DateTime($tweet['created_at']);

        $postCount->updatePostCount($monitor, $date);
    }

    /*
     * Gets the twitter integration addon object and returns the settings
     *
     * @return \MauticPlugin\MauticSocialBundle\Integration\TwitterIntegration
     */
    protected function getTwitterIntegration()
    {
        /** @var \Mautic\PluginBundle\Helper\IntegrationHelper $integrationHelper */
        $integrationHelper = $this->getContainer()->get('mautic.helper.integration');

        /** @var \MauticPlugin\MauticSocialBundle\Integration\TwitterIntegration $twitterIntegration */
        $twitterIntegration = $integrationHelper->getIntegrationObject('Twitter');

        return $twitterIntegration;
    }

    /*
     * Set the monitor's stat record with the metadata.
     */
    protected function setMonitorStats($monitor, $searchMeta)
    {
        $monitor->setStats($searchMeta);

        $this->monitoringModel->saveEntity($monitor);
    }

    /*
     * takes an array of query params for twitter and gives a list back.
     *
     * URL Encoding done in makeRequest()
     */
    protected function buildTwitterSearchQuery(array $query)
    {
        $queryString = implode(' ', $query);

        return $queryString;
    }

    /*
     * Prints all the returned tweets
     */
    protected function printTweets($statuses)
    {
        // don't show posts unless explicitly requested
        if (!$this->input->getOption('show-posts')) {
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

    /*
     * Prints the search query metadata from twitter
     */
    protected function printQueryMetadata($metadata)
    {
        // don't show stats unless explicitly requested
        if (!$this->input->getOption('show-stats')) {
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

    /*
     * Prints a summary of the search query
     */
    protected function printInformation($monitor, $results)
    {
        // don't show ststs unless explicitly requested
        if (!$this->input->getOption('show-stats')) {
            return;
        }
        $this->output->writeln('------------------------');
        $this->output->writeln($monitor->getTitle());
        $this->output->writeln('Published '.$monitor->isPublished());
        $this->output->writeln($monitor->getNetworkType());
        $this->output->writeln('New Leads '.$this->newLeads);
        $this->output->writeln('Updated Leads '.$this->updatedLeads);
        $this->printQueryMetadata($results['search_metadata']);
        $this->printTweets($results['statuses']);
        $this->output->writeln('------------------------');
    }

    /*
     * Used in various areas to set name of the network being searched.
     *
     * @return string twitter|facebook|linkedin etc..
     */
    abstract public function getNetworkName();
}
