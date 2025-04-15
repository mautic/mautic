<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Command;

use Mautic\AssetBundle\Event\AsssetExportListEvent;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\ExportHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class EntityExportCommand extends ModeratedCommand
{
    public const COMMAND_NAME = 'mautic:entity:export';

    public function __construct(
        private EventDispatcherInterface $dispatcher,
        private ExportHelper $exportHelper,
        PathsHelper $pathsHelper,
        CoreParametersHelper $coreParametersHelper,
    ) {
        parent::__construct($pathsHelper, $coreParametersHelper);
    }

    protected function configure(): void
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->setDescription('Export entity data as JSON.')
            ->addOption('entity', null, InputOption::VALUE_REQUIRED, 'The name of the entity to export (e.g., campaign, email)')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Comma-separated list of entity IDs to export (e.g., --id=1,2,3)')
            ->addOption('json-only', null, InputOption::VALUE_NONE, 'Output only JSON data.')
            ->addOption('json-file', null, InputOption::VALUE_NONE, 'Save JSON data to a file.')
            ->addOption('zip-file', null, InputOption::VALUE_NONE, 'Save JSON data to a zip file.');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $entityName = $input->getOption('entity');
        $idOption   = $input->getOption('id');

        $entityIds = array_filter(array_map('intval', explode(',', (string) $idOption)));

        if (empty($entityName) || empty($entityIds)) {
            $output->writeln('<error>You must specify the entity and at least one valid entity ID.</error>');

            return self::FAILURE;
        }

        $allData = [];

        foreach ($entityIds as $entityId) {
            $event = $this->dispatchEntityExportEvent($entityName, $entityId);
            $data  = $event->getEntities();

            if (!empty($data)) {
                $allData[] = $data;
            }
        }

        if (empty($allData)) {
            $output->writeln('<error>No data found for export.</error>');

            return self::FAILURE;
        }

        $assetListEvent = new AsssetExportListEvent($allData);
        $assetListEvent = $this->dispatcher->dispatch($assetListEvent);
        $assetList      = $assetListEvent->getList();

        return $this->outputData($allData, $assetList, $input, $output);
    }

    private function dispatchEntityExportEvent(string $entityName, int $entityId): EntityExportEvent
    {
        $event = new EntityExportEvent($entityName, $entityId);

        return $this->dispatcher->dispatch($event);
    }

    /**
     * @param array<array<string, mixed>> $data
     * @param array<string|int, string>   $assetList
     */
    private function outputData(array $data, array $assetList, InputInterface $input, OutputInterface $output): int
    {
        $jsonOutput = json_encode($data, JSON_PRETTY_PRINT);

        if ($input->getOption('json-only')) {
            $output->writeln($jsonOutput);
        } elseif ($input->getOption('json-file')) {
            $filePath = $this->writeToFile($jsonOutput);
            $output->writeln('<info>JSON file created at:</info> '.$filePath);
        } elseif ($input->getOption('zip-file')) {
            $zipPath = $this->exportHelper->writeToZipFile($jsonOutput, $assetList);
            $output->writeln('<info>ZIP file created at:</info> '.$zipPath);
        } else {
            $output->writeln('<error>You must specify one of --json-only, --json-file, or --zip-file options.</error>');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function writeToFile(string $jsonOutput): string
    {
        $filePath = sprintf('%s/entity_data.json', sys_get_temp_dir());
        file_put_contents($filePath, $jsonOutput);

        return $filePath;
    }
}
