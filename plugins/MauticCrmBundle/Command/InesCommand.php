<?php
/**
 * @copyright   2016 Webmecanik
 * @author      Webmecanik
 *
 * @link        http://www.webmecanik.com
 */

namespace MauticPlugin\MauticCrmBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Commande CLI : Synchronizing ATMT leads to INES CRM.
 *
 * php app/console crm:ines [--number-of-leads-to-process=]
 */
class InesCommand extends ContainerAwareCommand
{
    /**
     * Configure the command.
     */
    protected function configure()
    {
        $this->setName('crm:ines')
             ->addOption('--number-of-leads-to-process', null, InputOption::VALUE_OPTIONAL, 'Nombre de leads maximum à synchroniser à chaque appel.');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // By default : sync 10 contacts
        $options         = $input->getOptions();
        $numberToProcess = $options['number-of-leads-to-process'] ? $options['number-of-leads-to-process'] : 10;

        // INES integration must be active, in full-sync mode, and linked with INES WS

        $inesIntegration = $this->getContainer()->get('mautic.helper.integration')->getIntegrationObject('Ines');

        if (!$inesIntegration->isFullSync()) {
            $output->writeln('The INES integration must be active and in full-sync mode.');

            return 0;
        }

        if (!$inesIntegration->isAuthorized()) {
            $output->writeln("CRONJOB FAILED: can't connect to INES.");

            return 1;
        }

        // WS works : start the sync
        list($nbUpdated, $nbFailedUpdated, $nbDeleted, $nbFailedDeleted) = $inesIntegration->syncPendingLeadsToInes($numberToProcess);

        $s = ($nbUpdated > 1) ? 's' : '';
        $output->writeln($nbUpdated.' lead'.$s.' updated of '.($nbUpdated + $nbFailedUpdated).'.');

        $s = ($nbDeleted > 1) ? 's' : '';
        $output->writeln($nbDeleted.' lead'.$s.' deleted of '.($nbDeleted + $nbFailedDeleted).'.');

        // If the queue is empty, try to feed it with a batch of leads that have never been synchronized
        $nbEnqueued = $inesIntegration->firstSyncCheckAndEnqueue();
        $s          = ($nbEnqueued > 1) ? 's' : '';
        $output->writeln($nbEnqueued.' lead'.$s.' enqueued (1st sync)');

        return 0;
    }
}
