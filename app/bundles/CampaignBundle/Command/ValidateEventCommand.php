<?php

namespace Mautic\CampaignBundle\Command;

use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\CampaignBundle\Executioner\InactiveExecutioner;
use Mautic\CoreBundle\Twig\Helper\FormatterHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(
    name: 'mautic:campaigns:validate',
    description: 'Validate if a contact has been inactive for a decision and execute events if so.'
)]
class ValidateEventCommand
{
    use WriteCountTrait;

    public function __construct(
        private InactiveExecutioner $inactiveExecution,
        private TranslatorInterface $translator,
        private FormatterHelper $formatterHelper,
    ) {
    }

    public function __invoke(
        #[\Symfony\Component\Console\Attribute\Option(name: '--decision-id', description: 'ID of the decision to evaluate.')]
        $decisionId = null,
        #[\Symfony\Component\Console\Attribute\Option(name: '--contact-id', description: 'Evaluate for specific contact')]
        $contactId = null,
        #[\Symfony\Component\Console\Attribute\Option(name: '--contact-ids', description: 'CSV of contact IDs to evaluate.')]
        $contactIds = null,
        OutputInterface $output,
    ): int {
        defined('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED') or define('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED', 1);

        if (is_numeric($decisionId)) {
            $decisionId = (int) $decisionId;
        }

        if (is_numeric($contactId)) {
            $contactId = (int) $contactId;
        }

        $contactIds = $this->formatterHelper->simpleCsvToArray($contactIds, 'int');

        if (!$contactIds && !$contactId) {
            $output->writeln(
                "\n".
                '<comment>'.$this->translator->trans('mautic.campaign.trigger.events_executed', ['%count%' => 0])
                .'</comment>'
            );

            return Command::SUCCESS;
        }

        $limiter = new ContactLimiter(null, $contactId, null, null, $contactIds);
        $counter = $this->inactiveExecution->validate($decisionId, $limiter, $output);

        $this->writeCounts($output, $this->translator, $counter);

        return Command::SUCCESS;
    }
}
