<?php

namespace Mautic\CoreBundle\Command;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\IpLookup\MaxMindDoNotSellList;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Constraints\Ip;
use Symfony\Component\Validator\Validation;

/**
 * CLI Command to purge data from Mautic that appears on the
 * MaxMind Do Not Sell list.
 */
class MaxMindDoNotSellPurgeCommand extends Command
{
    private $batchSize = 3;

    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(EntityManager $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('mautic:max-mind:purge')
            ->setDescription('Purge data connected to MaxMind Do Not Sell list.')
            ->addOption(
                'dry-run',
                'd',
                InputOption::VALUE_NONE,
                'Get a list of data that will be purged.'
            )
            ->addOption(
                'batch-size',
                's',
                InputOption::VALUE_REQUIRED,
                'Set the batch size to use when loading the Do Not Sell List.'
            )
            ->setHelp(<<<'EOT'
The <info>%command.name%</info> command will purge all data from Mautic which is related to any IP found on the MaxMind Do Not Sell List.

<info>php %command.full_name% --dry-run</info>

Performs a dry-run which will not actually purge any data, but will produce a list of what would be purged.

<info>php %command.full_name% --batch-size</info>

Set the number of records to return in a batch when processing the Do Not Sell List. This option is ignored if IPs are passed as an argument.
EOT
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //TODO:
        // Load the DoNot Sell List file and set ENV to its location
        // Compare list values against IP table and retrieve contact id matches
        // Check audit log to see if contact values where added by maxmind
        // Purge data that was added this way
        // Report on changes
        $dryRun          = $input->getOption('dry-run');
        $this->batchSize = $input->getOption('batch-size') ?? $this->batchSize;

        $output->writeln("Step 1: Checking Do Not Sell List for matches...\n");

        $matches = $this->findIPMatches($output);

        if (0 == count($matches)) {
            $output->writeln("\nNo matches found, exiting.");

            return 0;
        }

        $output->writeln("\nFound ".count($matches).' matches.');

        if ($dryRun) {
            $output->writeln('Dry run, skipping purge.');

            return 0;
        }

        $this->purgeData($output, $matches);

        return 0;
    }

    private function customIPsAreValid(array $ips): bool
    {
        $validator = Validation::createValidator();

        $violations   = [];
        $ipConstraint = new Ip();
        foreach ($ips as $ip) {
            $violations = $validator->validate($ip, [$ipConstraint]);
        }

        return boolval(count($violations));
    }

    private function findIPMatches(OutputInterface $output): array
    {
        $progressBar = new ProgressBar($output);
        $progressBar->start();

        $doNotSellList = new MaxMindDoNotSellList();

        $offset  = 0;
        $matches = [];
        while ($doNotSellList->loadList($offset, $this->batchSize)) {
            $contacts = $this->findContactsFromIPs($doNotSellList->getList());
            $matches  = array_merge($matches, $contacts);

            $progressBar->advance(count($doNotSellList->getList()));

            $offset += $this->batchSize;
        }

        $progressBar->finish();

        return $matches;
    }

    private function findContactsFromIPs(array $ips): array
    {
        $in  = "'".implode("','", $ips)."'";
        $sql =
            'SELECT lead_id '.
             'FROM '.MAUTIC_TABLE_PREFIX.'lead_ips_xref x '.
             'JOIN '.MAUTIC_TABLE_PREFIX.'ip_addresses ip ON x.ip_id = ip.id '.
             'WHERE ip.ip_address IN ('.$in.')';

        $conn = $this->em->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    private function purgeData(OutputInterface $output, array $ipsToPurge)
    {
        $output->writeln('Purging data....');
        $purgeProgress = new ProgressBar($output, count($ipsToPurge));

        foreach ($ipsToPurge as $match) {
            sleep(1);
            $purgeProgress->advance();
        }

        $purgeProgress->finish();
    }
}
