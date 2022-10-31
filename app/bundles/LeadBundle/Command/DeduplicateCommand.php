<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Command;

use Mautic\CoreBundle\Command\ModeratedCommand;
use Mautic\CoreBundle\Helper\ExitCode;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\LeadBundle\Deduplicate\ContactDeduper;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DeduplicateCommand extends ModeratedCommand
{
    public const NAME = 'mautic:contacts:deduplicate';

    private ContactDeduper $contactDeduper;

    public function __construct(ContactDeduper $contactDeduper, PathsHelper $pathsHelper)
    {
        parent::__construct($pathsHelper);

        $this->contactDeduper = $contactDeduper;
    }

    public function configure()
    {
        parent::configure();

        $this->setName(self::NAME)
            ->setDescription('Merge contacts based on same unique identifiers')
            ->addOption(
                '--newer-into-older',
                null,
                InputOption::VALUE_NONE,
                'By default, this command will merge older contacts and activity into the newer. Use this flag to reverse that behavior.'
            )
            ->addOption(
                '--contact-ids',
                null,
                InputOption::VALUE_REQUIRED,
                'Comma separated list of contact IDs to deduplicate. If not provided, all contacts will be deduplicated.'
            )
            ->addOption(
                '--batch',
                null,
                InputOption::VALUE_REQUIRED,
                'How many contact duplicates to process at once. Defaults to 1000.',
                1000
            )
            ->addOption(
                '--prepare-commands',
                null,
                InputOption::VALUE_NONE,
                'In case you want to run this command in parallel then this option will just print the commands with concrete contact IDs so the commands do not overlap the work.',
            )
            ->setHelp(
                <<<'EOT'
The <info>%command.name%</info> command will dedpulicate contacts based on unique identifier values. 

<info>php %command.full_name%</info>
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $newerIntoOlder  = (bool) $input->getOption('newer-into-older');
        $prepareCommands = (bool) $input->getOption('prepare-commands');
        $batch           = (int) $input->getOption('batch');
        $contactIds      = $input->getOption('contact-ids');
        $uniqueFields    = $this->contactDeduper->getUniqueFields('lead');
        $duplicateCount  = $this->contactDeduper->countDuplicatedContacts(array_keys($uniqueFields));
        $progressBar     = new ProgressBar($output, $duplicateCount);

        $output->writeln('Deduplicating contacts based on unique identifiers: '.implode(', ', $uniqueFields));
        $output->writeln("{$duplicateCount} contacts found to deduplicate");

        if ($prepareCommands) {
            $lastId = 1;
            while ($contactIds = $this->contactDeduper->getDuplicateContactIdBatch($uniqueFields, $batch, $lastId)) {
                $output->writeln(sprintf('bin/console %s --contact-ids=%s', self::NAME, implode(',', $contactIds)));
                $lastId = (int) end($contactIds);
            }

            return ExitCode::SUCCESS;
        }

        $progressBar->setFormat('debug');
        $progressBar->start();

        if ($contactIds) {
            $contacts = $this->contactDeduper->getContactsByIds(explode(',', $contactIds));
            $this->contactDeduper->deduplicateContactBatch($contacts, $newerIntoOlder, fn () => $progressBar->advance());
        } else {
            while ($contactIds = $this->contactDeduper->getDuplicateContactIdBatch($uniqueFields, $batch)) {
                $contacts = $this->contactDeduper->getContactsByIds($contactIds);
                $this->contactDeduper->deduplicateContactBatch($contacts, $newerIntoOlder, fn () => $progressBar->advance());
            }
        }

        $progressBar->finish();

        return ExitCode::SUCCESS;
    }
}
