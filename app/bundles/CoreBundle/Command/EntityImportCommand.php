<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Command;

use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class EntityImportCommand extends ModeratedCommand
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
            ->setName('mautic:entity:import')
            ->setDescription('Import entity data as JSON.')
            ->addOption('entity', null, InputOption::VALUE_REQUIRED, 'The name of the entity to import (e.g., campaign, email)')
            ->addOption(
                'file',
                null,
                InputOption::VALUE_REQUIRED,
                'The file path of the JSON or ZIP file to import.'
            )
            ->addOption(
                'user',
                null,
                InputOption::VALUE_OPTIONAL,
                'The user ID of the person importing the entity.'
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $entityName = $input->getOption('entity');
        $filePath   = $input->getOption('file');
        $userId     = (int) $input->getOption('user');

        if (!$filePath || !file_exists($filePath)) {
            $output->writeln('<error>You must specify a valid file path using --file.</error>');

            return self::FAILURE;
        }

        $fileData = $this->readFile($filePath);

        if (null === $fileData) {
            $output->writeln('<error>Failed to read or decode the file data.</error>');

            return self::FAILURE;
        }

        $validationResult = $this->validateData($fileData, $entityName);
        if (!$validationResult['isValid']) {
            $output->writeln('<error>Invalid data: '.$validationResult['message'].'</error>');

            return self::FAILURE;
        }

        $event = new EntityImportEvent($entityName, $fileData, $userId);

        $this->dispatcher->dispatch($event);

        // $importSummary = $event->getArgument('import_status') ?? ['error' => 'Unknown status'];

        // print_r($importSummary);
        $output->writeln('<info>Campaign data imported successfully.</info>');

        return self::SUCCESS;
    }

    /**
     * @return ?array<string, mixed>
     */
    private function readFile(string $filePath): ?array
    {
        if ('zip' === pathinfo($filePath, PATHINFO_EXTENSION)) {
            $tempDir = sys_get_temp_dir();
            $zip     = new \ZipArchive();

            if (true === $zip->open($filePath)) {
                $zip->extractTo($tempDir);
                $jsonFilePath = null;
                $mediaPath    = $this->pathsHelper->getSystemPath('media').'/files/';

                for ($i = 0; $i < $zip->numFiles; ++$i) {
                    $filename = $zip->getNameIndex($i);

                    if (str_starts_with($filename, 'assets/')) {
                        $sourcePath      = $tempDir.'/'.$filename;
                        $destinationPath = $mediaPath.substr($filename, strlen('assets/'));

                        if (is_dir($sourcePath)) {
                            @mkdir($destinationPath, 0755, true);
                        } else {
                            @mkdir(dirname($destinationPath), 0755, true);
                            copy($sourcePath, $destinationPath);
                        }
                    } elseif ('json' === pathinfo($filename, PATHINFO_EXTENSION)) {
                        $jsonFilePath = $tempDir.'/'.$filename;
                    }
                }

                $zip->close();
                if ($jsonFilePath) {
                    $filePath = $jsonFilePath;
                } else {
                    return null;
                }
            } else {
                return null;
            }
        }

        $fileContents = file_get_contents($filePath);

        return json_decode($fileContents, true);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function validateData(array $data, string $entityName): array
    {
        if (!isset($data[$entityName]) || !isset($data['dependencies'])) {
            return ['isValid' => false, 'message' => 'Missing required keys.'];
        }

        return ['isValid' => true, 'message' => ''];
    }
}
