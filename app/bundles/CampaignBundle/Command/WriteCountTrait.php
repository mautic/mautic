<?php

namespace Mautic\CampaignBundle\Command;

use Mautic\CampaignBundle\Executioner\Result\Counter;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\TranslatorInterface;

trait WriteCountTrait
{
    private function writeCounts(OutputInterface $output, TranslatorInterface $translator, Counter $counter): void
    {
        $output->writeln('');
        $output->writeln(
            '<comment>'.$translator->transChoice(
                'mautic.campaign.trigger.events_executed',
                $counter->getTotalExecuted(),
                ['%events%' => $counter->getTotalExecuted()]
            )
            .'</comment>'
        );
        $output->writeln(
            '<comment>'.$translator->transChoice(
                'mautic.campaign.trigger.events_scheduled',
                $counter->getTotalScheduled(),
                ['%events%' => $counter->getTotalScheduled()]
            )
            .'</comment>'
        );
        $output->writeln('');
    }
}
