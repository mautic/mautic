<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Entity\ExportableInterface;
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
        private EntityManagerInterface $entityManager,
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
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'ID of entities to export.')
            ->addOption('json-only', null, InputOption::VALUE_NONE, 'Output only JSON data.')
            ->addOption('json-file', null, InputOption::VALUE_NONE, 'Save JSON data to a file.')
            ->addOption('zip-file', null, InputOption::VALUE_NONE, 'Save JSON data to a zip file.');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $entityName = $input->getOption('entity');
        $entityId   = (int) $input->getOption('id');

        if (empty($entityId) || empty($entityName)) {
            $output->writeln('<error>You must specify the entity and at least one valid entity ID.</error>');

            return self::FAILURE;
        }

        $entity = $this->getEntityByNameAndId($entityName, $entityId);
        if (!$entity instanceof ExportableInterface) {
            $output->writeln('<error>Invalid entity type or ID.</error>');

            return self::FAILURE;
        }

        $event = $this->dispatchEntityExportEvent($entity);

        return $this->outputData($event->getEntities(), $input, $output);
    }

    private function getEntityByNameAndId(string $entityName, int $entityId): ?ExportableInterface
    {
        $entityClass = $this->getEntityClass($entityName);
        if (!$entityClass) {
            return null;
        }

        $repository = $this->entityManager->getRepository($entityClass);
        $entity     = $repository->find($entityId);

        return $entity instanceof ExportableInterface ? $entity : null;
    }

    private function getEntityClass(string $entityName): ?string
    {
        $entityClasses = [
            'campaign' => \Mautic\CampaignBundle\Entity\Campaign::class,
            'form'     => \Mautic\FormBundle\Entity\Form::class,
        ];

        return $entityClasses[$entityName] ?? null;
    }

    private function dispatchEntityExportEvent(ExportableInterface $entity): EntityExportEvent
    {
        $event = new EntityExportEvent($entity);

        return $this->dispatcher->dispatch($event);
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
            $output->writeln($zipPath);
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
