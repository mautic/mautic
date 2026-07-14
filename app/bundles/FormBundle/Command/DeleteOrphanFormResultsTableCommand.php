<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Command;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\ExitCode;
use Mautic\FormBundle\Entity\FormRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(
    name: DeleteOrphanFormResultsTableCommand::COMMAND_NAME,
    description: 'Deletes form results table for already deleted forms'
)]
class DeleteOrphanFormResultsTableCommand extends Command
{
    public const COMMAND_NAME = 'mautic:forms:delete-results-table';

    private readonly Connection $conn;

    public function __construct(
        private readonly EntityManager $entityManager,
        private readonly LoggerInterface $logger,
        private readonly TranslatorInterface $translator,
        private readonly FormRepository $formRepository,
    ) {
        parent::__construct();

        $this->conn = $this->entityManager->getConnection();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $orphanFormResultsTableNames = $this->getOrphanFormResultsTable();

        $sm = $this->conn->createSchemaManager();

        foreach ($orphanFormResultsTableNames as $tableName) {
            try {
                $sm->dropTable($tableName);
                $this->logger->info('dropped table '.$tableName);
            } catch (\Exception $e) {
                $this->logger->error('An exception occurred in dropping '.$tableName);
                $this->logger->error($e->getMessage());

                return ExitCode::FAILURE;
            }
        }
        $output->writeln($this->translator->trans('mautic.forms.command.dropped_tables_count', ['%table_count%' => count($orphanFormResultsTableNames)]));

        return ExitCode::SUCCESS;
    }

    /**
     * @return array<int,mixed>
     *
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    private function getOrphanFormResultsTable(): array
    {
        $validFormTables = [];

        try {
            $validFormTables = $this->formRepository->getValidFormResultsTable();
        } catch (\Exception $e) {
            $this->logger->error('An exception occurred in retrieving orphan form results table');
            $this->logger->error($e->getMessage());
        }

        $tempTables = [];

        foreach ($validFormTables as $table) {
            $tempTables[] = $table['validFormTable'];
        }

        $validFormTables = $tempTables;

        $allTables = $this->conn->createSchemaManager()->listTableNames();

        $inValidFormResultsTable = [];

        foreach ($allTables as $tableName) {
            if (str_contains($tableName, 'form_results') && !in_array($tableName, $validFormTables)) {
                $inValidFormResultsTable[] = $tableName;
            }
        }

        return $inValidFormResultsTable;
    }
}
