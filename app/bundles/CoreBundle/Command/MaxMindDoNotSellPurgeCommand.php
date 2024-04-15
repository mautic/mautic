<?php

namespace Mautic\CoreBundle\Command;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\IpLookup\DoNotSellList\MaxMindDoNotSellList;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI Command to purge data from Mautic that appears on the
 * MaxMind Do Not Sell list.
 */
class MaxMindDoNotSellPurgeCommand extends Command
{
    /**
     * @var LeadRepository
     */
    private \Doctrine\ORM\EntityRepository $leadRepository;

    public function __construct(
        private EntityManager $em,
        private MaxMindDoNotSellList $doNotSellList
    ) {
        parent::__construct();
        $this->leadRepository = $this->em->getRepository(Lead::class);
    }

    protected function configure()
    {
        $this->setName('mautic:max-mind:purge')
            ->addOption(
                'dry-run',
                'd',
                InputOption::VALUE_NONE,
                'Get a list of data that will be purged.'
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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $dryRun = $input->getOption('dry-run');

            $output->writeln('<info>Step 1: Searching for contacts with data from Do Not Sell List...</info>');

            $this->doNotSellList->loadList();
            $doNotSellListIPs = array_map(fn ($item): string|array =>
                // strip subnet mask characters
                substr_replace($item['value'], '', strpos($item['value'], '/'), 3), $this->doNotSellList->getList());
            $doNotSellContacts = $this->findContactsFromIPs($doNotSellListIPs);

            if (0 == count($doNotSellContacts)) {
                $output->writeln('<info>No matches found.</info>');

                return \Symfony\Component\Console\Command\Command::SUCCESS;
            }

            $output->writeln('Found '.count($doNotSellContacts)." contacts with an IP from the Do Not Sell list.\n");

            if ($dryRun) {
                $output->writeln('<info>Dry run; skipping purge.</info>');

                return \Symfony\Component\Console\Command\Command::SUCCESS;
            }

            $output->writeln('<info>Step 2: Purging data...</info>');
            $purgeProgress = new ProgressBar($output, count($doNotSellContacts));

            foreach ($doNotSellContacts as $contact) {
                $this->purgeData($contact['id'], $contact['ip_address']);
                $purgeProgress->advance(1);
            }

            $purgeProgress->finish();
            $output->writeln("\n<info>Purge complete.</info>\n");

            return \Symfony\Component\Console\Command\Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln("\n<error>".$e->getMessage().'</error>');

            return \Symfony\Component\Console\Command\Command::FAILURE;
        }
    }

    private function findContactsFromIPs(array $ips): array
    {
        $in  = "'".implode("','", $ips)."'";
        $sql =
            'SELECT x.lead_id AS id, ip.ip_address AS ip_address '.
             'FROM '.MAUTIC_TABLE_PREFIX.'lead_ips_xref x '.
             'JOIN '.MAUTIC_TABLE_PREFIX.'ip_addresses ip ON x.ip_id = ip.id '.
             'WHERE ip.ip_address IN ('.$in.')';

        $conn   = $this->em->getConnection();
        $stmt   = $conn->prepare($sql);
        $result = $stmt->executeQuery();

        return $result->fetchAllAssociative();
    }

    private function purgeData(string $contactId, string $ip): bool
    {
        /** @var Lead $lead */
        $lead       = $this->leadRepository->findOneBy(['id' => $contactId]);
        $matchedIps = array_filter($lead->getIpAddresses()->getValues(), fn ($item): bool => $item->getIpAddress() == $ip);

        // We only purge data from the contact if it matches the data in the IP details
        if ($ipDetails = $matchedIps[0]->getIpDetails()) {
            if (($ipDetails['city'] ?? '') == $lead->getCity()) {
                $lead->setCity(null);
            }
            if (($ipDetails['region'] ?? '') == $lead->getState()) {
                $lead->setState(null);
            }
            if (($ipDetails['country'] ?? '') == $lead->getCountry()) {
                $lead->setCountry(null);
            }
            if (($ipDetails['zipcode'] ?? '') == $lead->getZipcode()) {
                $lead->setZipcode(null);
            }

            $this->leadRepository->saveEntity($lead);

            return true;
        }

        return false;
    }

    protected static $defaultDescription = 'Purge data connected to MaxMind Do Not Sell list.';
}
