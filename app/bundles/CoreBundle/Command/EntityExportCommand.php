<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Command;

use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class EntityExportCommand extends ModeratedCommand
{
    public function __construct(
        private EventDispatcherInterface $dispatcher,
        PathsHelper $pathsHelper,
        CoreParametersHelper $coreParametersHelper,
    ) {
        parent::__construct($pathsHelper, $coreParametersHelper);
    }

    protected function configure(): void
    {
        $this
            ->setName('mautic:entity:export')
            ->setDescription('Export entity data as JSON.')
            ->addOption('entity', null, InputOption::VALUE_REQUIRED, 'The name of the entity to export (e.g., campaign, email)')
            ->addOption('id', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'IDs of entities to export.')
            ->addOption('json-only', null, InputOption::VALUE_NONE, 'Output only JSON data.')
            ->addOption('json-file', null, InputOption::VALUE_NONE, 'Save JSON data to a file.')
            ->addOption('zip-file', null, InputOption::VALUE_NONE, 'Save JSON data to a zip file.');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $entityName = $input->getOption('entity');
        $entityId   = $input->getOption('id');

        if (empty($entityId) || empty($entityName)) {
            $output->writeln('<error>You must specify the entity and at least one valid entity ID.</error>');

            return self::FAILURE;
        }

        $entityId = array_map('intval', $entityId);

        $event = $this->dispatchEntityExportEvent($entityName, $entityId);

        return $this->outputData($event->getEntities(), $input, $output);
    }

    /**
     * Dispatch the EntityExportEvent.
     *
     * @param array<int> $entityId
     */
    private function dispatchEntityExportEvent(string $entityName, array $entityId): EntityExportEvent
    {
        $event = new EntityExportEvent($entityName, (int) $entityId);

        return $this->dispatcher->dispatch($event, $entityName);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function outputData(array $data, InputInterface $input, OutputInterface $output): int
    {
        $jsonOutput = json_encode($data, JSON_PRETTY_PRINT);

        if ($input->getOption('json-only')) {
            $output->writeln($jsonOutput);
        } elseif ($input->getOption('json-file')) {
            $filePath = $this->writeToFile($jsonOutput);
            $output->writeln('<info>JSON file created at:</info> '.$filePath);
        } elseif ($input->getOption('zip-file')) {
            $zipPath = $this->writeToZipFile($jsonOutput);
            $output->writeln(''.$zipPath);
        } else {
            $output->writeln('<error>You must specify one of --json-only, --json-file, or --zip-file options.</error>');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function writeToFile(string $jsonOutput): string
    {
        $filePath = sprintf('%s/campaign_data.json', sys_get_temp_dir());
        file_put_contents($filePath, $jsonOutput);

        return $filePath;
    }

    private function writeToZipFile(string $jsonOutput): string
    {
        $tempDir      = sys_get_temp_dir();
        $jsonFilePath = sprintf('%s/campaign_data.json', $tempDir);
        $zipFilePath  = sprintf('%s/campaign_data.zip', $tempDir);

        file_put_contents($jsonFilePath, $jsonOutput);

        $zip = new \ZipArchive();
        if (true === $zip->open($zipFilePath, \ZipArchive::CREATE)) {
            $zip->addFile($jsonFilePath, 'campaign_data.json');
            $zip->close();
        }

        return $zipFilePath;
    }
}
