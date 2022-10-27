<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Command;

use Mautic\CoreBundle\Command\ModeratedCommand;
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
            ->setHelp(
                <<<'EOT'
The <info>%command.name%</info> command will dedpulicate contacts based on unique identifier values. 

<info>php %command.full_name%</info>
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $newerIntoOlder = (bool) $input->getOption('newer-into-older');
        $uniqueFields   = $this->contactDeduper->getUniqueFields('lead');
        $duplicateCount = $this->contactDeduper->countDuplicatedContacts(array_keys($uniqueFields));
        $progressBar    = new ProgressBar($output, $duplicateCount);

        $output->writeln("Deduplicating contacts based on unique identifiers: ".implode(', ', $uniqueFields));
        $output->writeln("{$duplicateCount} contacts found to deduplicate");

        $progressBar->setFormat('debug');
        $progressBar->start();

        while ($contact = $this->contactDeduper->getOneDuplicateContact($uniqueFields)) {
            $duplicates = $this->contactDeduper->checkForDuplicateContacts($contact->getProfileFields(), $newerIntoOlder);
            
            $this->contactDeduper->mergeContacts($duplicates);
            $this->contactDeduper->detachContacts($duplicates);

            $progressBar->advance();
        }

        $progressBar->finish();
    }
}
