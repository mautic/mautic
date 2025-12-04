<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Command;

use Mautic\IntegrationsBundle\Entity\FieldChangeRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

#[AsCommand(
    name: CleanupCommand::NAME,
    description: 'Delete records from field changes which are invalid'
)]
class CleanupCommand
{
    public const NAME = 'mautic:integrations:cleanup';

    public function __construct(private FieldChangeRepository $fieldChangeRepository)
    {
    }

    public function __invoke(\Symfony\Component\Console\Style\SymfonyStyle $io): int
    {
        $numberOfRecordsDeleted = $this->fieldChangeRepository->deleteOrphanLeadChanges();
        $io->success("$numberOfRecordsDeleted records deleted.");
        $io->success('Execution time: '.number_format(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 3));

        return Command::SUCCESS;
    }
}
