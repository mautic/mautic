<?php

namespace Mautic\CampaignBundle\Command;

use Mautic\CampaignBundle\Entity\CampaignRepository;
use Mautic\CoreBundle\Command\ModeratedCommand;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FetchEventCommand extends ModeratedCommand
{
    public function __construct(
        private CampaignRepository $campaignRepository,
        PathsHelper $pathsHelper,
        CoreParametersHelper $coreParametersHelper,
    ) {
        parent::__construct($pathsHelper, $coreParametersHelper);
    }

    protected function configure()
    {
        $this
            ->setName('mautic:campaign:fetch-events')
            ->setDescription('Fetch campaign events.')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Campaign ID to fetch events for.')
            ->addOption('json-only', null, InputOption::VALUE_NONE, 'Output only JSON data.')
            ->addOption('json-file', null, InputOption::VALUE_NONE, 'Save JSON data to a file.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $campaignId = $input->getOption('id');

        if (!$campaignId || !is_numeric($campaignId)) {
            $output->writeln('<error>You must specify a valid campaign ID using --id.</error>');

            return self::FAILURE;
        }

        $campaign = $this->campaignRepository->find($campaignId);

        if (!$campaign) {
            $output->writeln('<error>Campaign not found for ID '.$campaignId.'.</error>');

            return self::FAILURE;
        }

        $events    = $campaign->getEvents();
        $eventData = [];

        foreach ($events as $event) {
            $parent   = $event->getParent();
            $parentId = $parent ? $parent->getId() : null;

            $eventData[] = [
                'id'                    => $event->getId(),
                'campaign_id'           => $campaign->getId(),
                'name'                  => $event->getName(),
                'description'           => $event->getDescription(),
                'type'                  => $event->getType(),
                'event_type'            => $event->getEventType(),
                'event_order'           => $event->getOrder(),
                'properties'            => $event->getProperties(),
                'trigger_interval'      => $event->getTriggerInterval(),
                'trigger_interval_unit' => $event->getTriggerIntervalUnit(),
                'trigger_mode'          => $event->getTriggerMode(),
                'triggerDate'           => $event->getTriggerDate() ? $event->getTriggerDate()->format(DATE_ATOM) : null,
                'channel'               => $event->getChannel(),
                'channel_id'            => $event->getChannelId(),
                'parent_id'             => $parentId,
            ];
        }

        return $this->outputData($eventData, $input, $output);
    }

    /**
     * @param array<int, array<string, mixed>> $data
     */
    private function outputData(array $data, InputInterface $input, OutputInterface $output): int
    {
        $jsonOutput = json_encode($data, JSON_PRETTY_PRINT);

        if ($input->getOption('json-only')) {
            $output->writeln($jsonOutput);
        } elseif ($input->getOption('json-file')) {
            $filePath = sprintf('%s/events_%s.json', sys_get_temp_dir(), $data[0]['id'] ?? 'unknown');
            file_put_contents($filePath, $jsonOutput);
            $output->writeln('<info>JSON file created at:</info> '.$filePath);
        } else {
            $output->writeln('<error>You must specify either --json-only or --json-file.</error>');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
