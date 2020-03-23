<?php

namespace Mautic\CoreBundle\Command;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\IpLookup\DoNotSellList\MaxMindDoNotSellList;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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

    /**
     * @var MaxMindDoNotSellList
     */
    private $doNotSellList;

    /**
     * @var LeadRepository
     */
    private $leadRepository;

    public function __construct(EntityManager $em, MaxMindDoNotSellList $doNotSellList)
    {
        parent::__construct();
        $this->em             = $em;
        $this->doNotSellList  = $doNotSellList;
        $this->leadRepository = $this->em->getRepository(Lead::class);
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
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dryRun          = $input->getOption('dry-run');
        $this->batchSize = $input->getOption('batch-size') ?? $this->batchSize;

        $output->writeln('<info>Step 1: Searching for contacts with data from Do Not Sell List...</info>');

        $progressBar = new ProgressBar($output);
        $progressBar->start();

        $offset            = 0;
        $doNotSellContacts = [];
        while ($this->doNotSellList->loadList($offset, $this->batchSize)) {
            $contacts          = $this->findContactsFromIPs($this->doNotSellList->getList());
            $doNotSellContacts = array_merge($doNotSellContacts, $contacts);

            $progressBar->advance(count($this->doNotSellList->getList()));

            $offset += $this->batchSize;
        }

        $progressBar->finish();

        if (0 == count($doNotSellContacts)) {
            $output->writeln('<info>No matches found.</info>');

            return 0;
        }

        $output->writeln("\nFound ".count($doNotSellContacts)." contacts with IPs from Do Not Sell list.\n");

        if ($dryRun) {
            $output->writeln('<info>Dry run; skipping purge.</info>');

            return 0;
        }

        $output->writeln('<info>Step 2: Purging data....</info>');
        $purgeProgress = new ProgressBar($output, count($doNotSellContacts));

        foreach ($doNotSellContacts as $contact) {
            $this->purgeData($contact['id'], $contact['ip_address']);
            $purgeProgress->advance(1);
        }

        $purgeProgress->finish();
        $output->writeln("\n<info>Purge complete.</info>\n");

        return 0;
    }

    private function findContactsFromIPs(array $ips): array
    {
        $in  = "'".implode("','", $ips)."'";
        $sql =
            'SELECT x.lead_id AS id, ip.ip_address AS ip_address '.
             'FROM '.MAUTIC_TABLE_PREFIX.'lead_ips_xref x '.
             'JOIN '.MAUTIC_TABLE_PREFIX.'ip_addresses ip ON x.ip_id = ip.id '.
             'WHERE ip.ip_address IN ('.$in.')';

        $conn = $this->em->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    private function purgeData(string $contactId, string $ip): bool
    {
        /** @var Lead $lead */
        $lead       = $this->leadRepository->findOneBy(['id' => $contactId]);
        $matchedIps = array_filter($lead->getIpAddresses()->getValues(), function ($item) use ($ip) {
            return $item->getIpAddress() == $ip;
        });

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
//        else {
//            /** @var IpAddress $ipDetails */
//            $ipp = $matchedIps[0]->setIpDetails(['city' => 'Boston', 'country' => 'us']);
//            $this->em->getRepository('MauticCoreBundle:IpAddress')->saveEntity($ipp);
//        }

        return false;
    }
}
