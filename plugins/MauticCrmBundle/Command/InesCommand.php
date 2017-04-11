<?php
/**
 * @copyright   2016 Webmecanik
 * @author      Webmecanik
 * @link        http://www.webmecanik.com
 */

namespace MauticPlugin\MauticCrmBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Commande CLI : Synchronisation des leads ATMT vers INES CRM
 *
 * php app/console crm:ines [--number-of-leads-to-process=]
 *
 */
class InesCommand extends ContainerAwareCommand
{
	protected $factory;

    /**
     * Configure the command
     */
    protected function configure()
    {
		$this->setName('crm:ines')
			 ->addOption('--number-of-leads-to-process', null, InputOption::VALUE_OPTIONAL, 'Nombre de leads maximum à synchroniser à chaque appel.');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
		// Combien de leads max à synchroniser ?
		// Par défaut : 10
		$options = $input->getOptions();
		$numberToProcess = $options['number-of-leads-to-process'] ? $options['number-of-leads-to-process'] : 10;

		// L'intégration INES doit-être active, en mode full-sync, et configurée pour communiquer avec les WS INES

		$this->factory = $this->getContainer()->get('mautic.factory');
		$inesIntegration = $this->factory->getHelper('integration')->getIntegrationObject('Ines');

		if ( !$inesIntegration->isFullSync()) {
			$output->writeln("L'intégration INES doit être active et en mode full-sync.");
			return 0;
		}

		if ( !$inesIntegration->isAuthorized()) {
			$output->writeln("ECHEC CRONJOB : connexion aux web-services INES impossible.");
			return 1;
		}


		// Les WS fonctionnent : on peut lancer la synchro
		list($nbUpdated, $nbFailedUpdated, $nbDeleted, $nbFailedDeleted) = $inesIntegration->syncPendingLeadsToInes($numberToProcess);

		$s = ($nbUpdated > 1) ? 's' : '';
		$output->writeln($nbUpdated.' lead'.$s.' synchronisé'.$s.' sur un total de '.($nbUpdated + $nbFailedUpdated).'.');

		$s = ($nbDeleted > 1) ? 's' : '';
		$output->writeln($nbDeleted.' lead'.$s.' supprimés'.$s.' sur un total de '.($nbDeleted + $nbFailedDeleted).'.');


		// Si la file d'attente est vide, on tente de l'alimenter avec un lot de leads qui n'ont jamais été synchronisés
		$nbEnqueued = $inesIntegration->firstSyncCheckAndEnqueue();
		$s = ($nbEnqueued > 1) ? 's' : '';
		$output->writeln($nbEnqueued.' lead'.$s." ajouté".$s." à la file d'attente (1ère synchro)");

		return 0;
    }

}
