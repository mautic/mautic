<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Command;

use Mautic\CampaignBundle\Executioner\Result\Counter;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\TranslatorInterface;

trait WriteCountTrait
{
    /**
     * @param OutputInterface     $output
     * @param TranslatorInterface $translator
     * @param Counter             $counter
     */
    private function writeCounts(OutputInterface $output, TranslatorInterface $translator, Counter $counter)
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
