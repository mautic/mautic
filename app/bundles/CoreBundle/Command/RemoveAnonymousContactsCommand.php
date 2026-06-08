<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Command;

use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Entity\LeadRepository;
use Mautic\CoreBundle\Helper\ExitCode;
use Mautic\LeadBundle\Entity\ListLeadRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveAnonymousContactsCommand extends Command
{
    /**
     * @var string
     */
    public const COMMAND_NAME = 'mautic:remove:anonymous_contacts';

    public function __construct(
        private ListLeadRepository $listLeadRepository,
        private LeadRepository $campaignLeadRepository,
        private LeadEventLogRepository $campaignLeadEventLog,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription('Delete all anonymous contacts from segment, campaign and campaign event logs.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $deletedRecords = $this->listLeadRepository->deleteAnonymousContacts();
        $output->writeln(sprintf('<info>%d record(s) deleted from segment leads.</info>', $deletedRecords));

        $deletedRecords = $this->campaignLeadRepository->deleteAnonymousContacts();
        $output->writeln(sprintf('<info>%d record(s) deleted from campaign leads.</info>', $deletedRecords));

        $deletedRecords = $this->campaignLeadEventLog->deleteAnonymousContacts();
        $output->writeln(sprintf('<info>%d record(s) deleted from campaign leads event logs.</info>', $deletedRecords));

        return ExitCode::SUCCESS;
    }
}
