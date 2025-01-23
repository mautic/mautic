<?php

namespace Mautic\CampaignBundle\Command;

use Doctrine\DBAL\Connection;
use Mautic\CoreBundle\Command\ModeratedCommand;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FetchCampaignDependenciesCommand extends ModeratedCommand
{
    public function __construct(
        private Connection $connection,
        PathsHelper $pathsHelper,
        CoreParametersHelper $coreParametersHelper,
    ) {
        parent::__construct($pathsHelper, $coreParametersHelper);
    }

    protected function configure()
    {
        $this
            ->setName('mautic:campaign:fetch-dependencies')
            ->setDescription('Fetch campaign dependencies from campaign_form_xref and campaign_leadlist_xref tables.')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Campaign ID to fetch dependencies for.')
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

        $forms = $this->fetchDependencies('campaign_form_xref', 'form_id', $campaignId);

        $leadlists = $this->fetchDependencies('campaign_leadlist_xref', 'leadlist_id', $campaignId);

        // Combine the results
        $dependencies = [
            'campaign_form_xref'     => $forms,
            'campaign_leadlist_xref' => $leadlists,
        ];

        // Output or save the data
        return $this->outputData($dependencies, $input, $output);
    }

    private function fetchDependencies(string $table, string $column, int $campaignId): array
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $results = $queryBuilder
            ->select($column)
            ->from($table)
            ->where('campaign_id = :campaignId')
            ->setParameter('campaignId', $campaignId)
            ->executeQuery()
            ->fetchAllAssociative();

        // Extract the column values into a flat array
        return array_column($results, $column);
    }

    private function outputData(array $data, InputInterface $input, OutputInterface $output): int
    {
        $jsonOutput = json_encode($data, JSON_PRETTY_PRINT);

        if ($input->getOption('json-only')) {
            $output->writeln($jsonOutput);
        } elseif ($input->getOption('json-file')) {
            $filePath = sprintf('%s/dependencies_%d.json', sys_get_temp_dir(), $data['dependencies']['campaign_id'] ?? 'unknown');
            file_put_contents($filePath, $jsonOutput);
            $output->writeln('<info>JSON file created at:</info> '.$filePath);
        } else {
            $output->writeln('<error>You must specify either --json-only or --json-file.</error>');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
