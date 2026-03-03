<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Command;

use Mautic\IntegrationsBundle\Entity\FieldChangeRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: CleanupCommand::NAME,
    description: 'Delete records from field changes which are invalid'
)]
class CleanupCommand extends Command
{
    public const NAME = 'mautic:integrations:cleanup';

    public function __construct(private FieldChangeRepository $fieldChangeRepository)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $numberOfRecordsDeleted = $this->fieldChangeRepository->deleteOrphanLeadChanges();
        $io->success("$numberOfRecordsDeleted records deleted.");
        $io->success('Execution time: '.number_format(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 3));

        return Command::SUCCESS;
    }
}
