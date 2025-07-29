<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Command;

use Mautic\AssetBundle\Event\AssetExportListEvent;
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
            ->setDescription('Export entity data.')
            ->addOption('entity', null, InputOption::VALUE_REQUIRED, 'The name of the entity to export (e.g., campaign, email)')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Comma-separated list of entity IDs to export (e.g., --id=1,2,3)')
            ->addOption('json-only', null, InputOption::VALUE_NONE, 'Output only JSON data.')
            ->addOption('zip-file', null, InputOption::VALUE_NONE, 'Save JSON data to a zip file.')
            ->addOption('path', null, InputOption::VALUE_REQUIRED, 'Custom directory to save exported files.'); // NEW OPTION
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

        $assetListEvent = new AssetExportListEvent($allData);
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
        $customPath = $input->getOption('path');

        if ($input->getOption('json-only')) {
            $output->writeln($jsonOutput);
        } elseif ($input->getOption('zip-file')) {
            $resolvedPath = '';
            if ($customPath) {
                $resolvedPath = $this->resolveAndValidatePath($customPath, $output);
                if (null === $resolvedPath) {
                    return self::FAILURE;
                }
            }
            $zipPath = $this->exportHelper->writeToZipFile($jsonOutput, $assetList, $resolvedPath);
            $output->writeln('<info>ZIP file created at:</info> '.$zipPath);
        } else {
            $output->writeln('<error>You must specify one of --json-only or --zip-file options.</error>');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Validate and resolve the provided path.
     */
    private function resolveAndValidatePath(string $path, OutputInterface $output): ?string
    {
        $resolvedPath = realpath($path) ?: $path; // Accept both absolute and relative
        if (!is_dir($resolvedPath)) {
            $output->writeln('<error>The specified path is not a valid directory: '.$resolvedPath.'</error>');

            return null;
        }
        if (!is_writable($resolvedPath)) {
            $output->writeln('<error>The specified directory is not writable: '.$resolvedPath.'</error>');

            return null;
        }

        return $resolvedPath;
    }
}
