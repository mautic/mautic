<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Command;

use Mautic\LeadBundle\Deduplicate\ContactDeduper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Stopwatch\Stopwatch;

#[AsCommand(name: DeduplicateIdsCommand::NAME, description: 'Merge contacts based on same unique identifiers', help: <<<'TXT'
The <info>%command.name%</info> command will dedpulicate contacts based on unique identifier values. 

<info>php %command.full_name%</info>
TXT)]
class DeduplicateIdsCommand
{
    public const NAME = 'mautic:contacts:deduplicate:ids';

    public function __construct(
        private ContactDeduper $contactDeduper,
    ) {
    }

    public function __invoke(
        OutputInterface $output,
        #[\Symfony\Component\Console\Attribute\Option(name: '--newer-into-older', description: 'By default, this command will merge older contacts and activity into the newer. Use this flag to reverse that behavior.')]
        bool $newerIntoOlder = false,
        #[\Symfony\Component\Console\Attribute\Option(name: '--contact-ids', description: 'Comma separated list of contact IDs to deduplicate. If not provided, all contacts will be deduplicated. Example: --contact-ids=23,3,11')]
        $contactIds = null,
    ): int {
        $newerIntoOlder = (bool) $newerIntoOlder;
        $contactIds     = array_filter(explode(',', $contactIds));
        $duplicateCount = count($contactIds);
        $progressBar    = new ProgressBar($output, $duplicateCount);
        $stopwatch      = new Stopwatch();

        if (!$contactIds) {
            $output->writeln('<error>No contacts to deduplicate.</error>');

            return Command::FAILURE;
        }

        $output->writeln("{$duplicateCount} contacts passed to deduplicate");

        $progressBar->setFormat('debug');
        $progressBar->start();
        $stopwatch->start('deduplicate');

        $contacts = $this->contactDeduper->getContactsByIds($contactIds);
        $this->contactDeduper->deduplicateContactBatch($contacts, $newerIntoOlder, fn () => $progressBar->advance());

        $progressBar->finish();

        $event = $stopwatch->stop('deduplicate');
        $output->writeln("Duration: {$event->getDuration()} ms, Memory: {$event->getMemory()} bytes");

        return Command::SUCCESS;
    }
}
