<?php

namespace Mautic\CampaignBundle\Command;

use Mautic\CampaignBundle\Entity\CampaignRepository;
use Mautic\CoreBundle\Command\ModeratedCommand;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FetchCampaignCommand extends ModeratedCommand
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
            ->setName('mautic:campaign:fetch')
            ->setDescription('Fetch campaign details.')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Campaign ID to fetch.')
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

        $data = [
            'id'           => $campaign->getId(),
            'name'         => $campaign->getName(),
            'description'  => $campaign->getDescription(),
            'is_published' => $campaign->getIsPublished(),
        ];

        return $this->outputData($data, $input, $output);
    }

    private function outputData(array $data, InputInterface $input, OutputInterface $output): int
    {
        $jsonOutput = json_encode($data, JSON_PRETTY_PRINT);

        if ($input->getOption('json-only')) {
            $output->writeln($jsonOutput);
        } elseif ($input->getOption('json-file')) {
            $filePath = sprintf('%s/campaign_%s.json', sys_get_temp_dir(), $data['id']);
            file_put_contents($filePath, $jsonOutput);
            $output->writeln('<info>JSON file created at:</info> '.$filePath);
        } else {
            $output->writeln('<error>You must specify either --json-only or --json-file.</error>');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
