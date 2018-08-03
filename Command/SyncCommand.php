<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticIntegrationsBundle\Command;

use DateTimeImmutable;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use MauticPlugin\MauticIntegrationsBundle\Event\SyncEvent;
use MauticPlugin\MauticIntegrationsBundle\IntegrationEvents;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class SyncCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('mautic:integrations:sync')
            ->setDescription('Fetch objects from integration.')
            ->addArgument(
                'integration',
                InputOption::VALUE_REQUIRED,
                'Fetch objects from integration.',
                null
            )
            ->addOption('--start-date',
                '-d',
                InputOption::VALUE_REQUIRED,
                'Set start date for updated values.'
            );

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io          = new SymfonyStyle($input, $output);
        $integration = $input->getArgument('integration');
        $startDateS  = $input->getOption('start-date');
        $env         = $input->getOption('env', 'production');

        try {
            $startDate = new DateTimeImmutable($startDateS);
        } catch (\Exception $e) {
            $io->error("'$startDateS' is not a valid date. Use 'Y-m-d H:i:s' format like '2018-12-24 20:30:00'");

            return 1;
        }
        
        try {
            $event = new SyncEvent($integration, $startDate);
            $this->getContainer()->get('event_dispatcher')->dispatch(IntegrationEvents::ON_SYNC_TRIGGERED, $event);

            // @todo do the syncing here
        } catch (\Exception $e) {
            if ($env === 'dev') {
                throw $e;
            }
            
            $io->error($e->getMessage());

            return 1;
        }

        $io->success('Execution time: '.number_format(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 3));

        return 0;
    }
}
