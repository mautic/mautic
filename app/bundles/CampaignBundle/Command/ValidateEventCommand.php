<?php

namespace Mautic\CampaignBundle\Command;

use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\CampaignBundle\Executioner\InactiveExecutioner;
use Mautic\CoreBundle\Twig\Helper\FormatterHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ValidateEventCommand extends Command
{
    use WriteCountTrait;

    public function __construct(
        private InactiveExecutioner $inactiveExecution,
        private TranslatorInterface $translator,
        private FormatterHelper $formatterHelper
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('mautic:campaigns:validate')
            ->addOption(
                '--decision-id',
                null,
                InputOption::VALUE_REQUIRED,
                'ID of the decision to evaluate.'
            )
            ->addOption(
                '--contact-id',
                null,
                InputOption::VALUE_OPTIONAL,
                'Evaluate for specific contact'
            )
            ->addOption(
                '--contact-ids',
                null,
                InputOption::VALUE_OPTIONAL,
                'CSV of contact IDs to evaluate.'
            );

        parent::configure();
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        defined('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED') or define('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED', 1);

        $decisionId = $input->getOption('decision-id');
        $contactId  = $input->getOption('contact-id');
        $contactIds = $this->formatterHelper->simpleCsvToArray($input->getOption('contact-ids'), 'int');

        if (!$contactIds && !$contactId) {
            $output->writeln(
                "\n".
                '<comment>'.$this->translator->trans('mautic.campaign.trigger.events_executed', ['%count%' => 0])
                .'</comment>'
            );

            return \Symfony\Component\Console\Command\Command::SUCCESS;
        }

        $limiter = new ContactLimiter(null, $contactId, null, null, $contactIds);
        $counter = $this->inactiveExecution->validate($decisionId, $limiter, $output);

        $this->writeCounts($output, $this->translator, $counter);

        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }

    protected static $defaultDescription = 'Validate if a contact has been inactive for a decision and execute events if so.';
}
