<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Command;

use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\ImportHelper;
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
        private ImportHelper $importHelper,
    ) {
        parent::__construct($pathsHelper, $coreParametersHelper);
    }

    protected function configure(): void
    {
        $this
            ->setName('mautic:entity:import')
            ->setDescription('Import entity data from a ZIP file.')
            ->addOption('entity', null, InputOption::VALUE_REQUIRED, 'The name of the entity to import (e.g., campaign, email)')
            ->addOption(
                'file',
                null,
                InputOption::VALUE_REQUIRED,
                'The file path of the ZIP file to import.'
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

        try {
            $fileData = $this->importHelper->readZipFile($filePath);
        } catch (\RuntimeException $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');

            return self::FAILURE;
        }

        $validationResult = $this->validateData($fileData, $entityName);
        if (!$validationResult['isValid']) {
            $output->writeln('<error>Invalid data: '.$validationResult['message'].'</error>');

            return self::FAILURE;
        }

        foreach ($fileData as $entity) {
            $event = new EntityImportEvent($entityName, $entity, $userId);
            $this->dispatcher->dispatch($event);
        }

        $output->writeln('<info>Campaign data imported successfully.</info>');

        return self::SUCCESS;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array{isValid: bool, message: string}
     */
    private function validateData(array $data, string $entityName): array
    {
        foreach ($data as $entity) {
            if (!isset($entity[$entityName]) || !isset($entity['dependencies'])) {
                return ['isValid' => false, 'message' => 'Missing required keys.'];
            }
        }

        return ['isValid' => true, 'message' => ''];
    }
}
