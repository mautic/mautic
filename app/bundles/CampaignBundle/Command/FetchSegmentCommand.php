<?php

namespace Mautic\CampaignBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Command\ModeratedCommand;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FetchSegmentCommand extends ModeratedCommand
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        PathsHelper $pathsHelper,
        CoreParametersHelper $coreParametersHelper,
    ) {
        parent::__construct($pathsHelper, $coreParametersHelper);
    }

    protected function configure()
    {
        $this
            ->setName('mautic:campaign:fetch-segments')
            ->setDescription('Fetch campaign segments as JSON.')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'The ID of the campaign to fetch segments for.')
            ->addOption('json-only', null, InputOption::VALUE_NONE, 'Output only JSON data.')
            ->addOption('json-file', null, InputOption::VALUE_NONE, 'Save JSON data to a file.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $campaignId = $input->getOption('id');
        $jsonOnly   = $input->getOption('json-only');
        $jsonFile   = $input->getOption('json-file');

        if (!$campaignId || !is_numeric($campaignId)) {
            $output->writeln('<error>You must specify a valid campaign ID using --id.</error>');

            return self::FAILURE;
        }

        $segmentData = $this->getSegmentData((int) $campaignId);

        if (empty($segmentData)) {
            $output->writeln('<info>No segments found for campaign ID '.$campaignId.'.</info>');

            return self::SUCCESS;
        }

        $jsonOutput = json_encode($segmentData, JSON_PRETTY_PRINT);

        if ($jsonOnly) {
            $output->write($jsonOutput);
        } elseif ($jsonFile) {
            $filePath = $this->writeToFile($jsonOutput, (int) $campaignId);
            $output->writeln('<info>JSON file created at:</info> '.$filePath);
        } else {
            $output->writeln('<error>You must specify one of --json-only or --json-file options.</error>');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getSegmentData(int $campaignId): array
    {
        $queryBuilder = $this->entityManager->getConnection()->createQueryBuilder();

        $segmentResults = $queryBuilder
            ->select('cl.leadlist_id, ll.name, ll.category_id, ll.is_published, ll.description, ll.alias, ll.public_name, ll.filters, ll.is_global, ll.is_preference_center')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_leadlist_xref', 'cl')
            ->innerJoin('cl', MAUTIC_TABLE_PREFIX.'lead_lists', 'll', 'll.id = cl.leadlist_id AND ll.is_published = 1')
            ->where('cl.campaign_id = :campaignId')
            ->setParameter('campaignId', $campaignId, \Doctrine\DBAL\ParameterType::INTEGER)
            ->executeQuery()
            ->fetchAllAssociative();

        return array_map(static fn ($result) => [
            'id'                   => $result['leadlist_id'],
            'name'                 => $result['name'],
            'is_published'         => $result['is_published'],
            'category_id'          => $result['category_id'],
            'description'          => $result['description'],
            'alias'                => $result['alias'],
            'public_name'          => $result['public_name'],
            'filters'              => $result['filters'],
            'is_global'            => $result['is_global'],
            'is_preference_center' => $result['is_preference_center'],
        ], $segmentResults);
    }

    private function writeToFile(string $jsonOutput, int $campaignId): string
    {
        $filePath = sprintf('%s/segments_%d.json', sys_get_temp_dir(), $campaignId);
        file_put_contents($filePath, $jsonOutput);

        return $filePath;
    }
}
