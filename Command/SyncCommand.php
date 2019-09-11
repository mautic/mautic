<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Command;

use DateTimeImmutable;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\InputOptionsDAO;
use MauticPlugin\IntegrationsBundle\Sync\SyncService\SyncServiceInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use MauticPlugin\IntegrationsBundle\Exception\InvalidValueException;

class SyncCommand extends ContainerAwareCommand
{
    /**
     * @var SyncServiceInterface
     */
    private $syncService;

    /**
     * @param SyncServiceInterface $syncService
     */
    public function __construct(SyncServiceInterface $syncService)
    {
        parent::__construct();

        $this->syncService = $syncService;
    }

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
            ->addOption(
                '--start-datetime',
                '-t',
                InputOption::VALUE_OPTIONAL,
                'Set start date/time for updated values.'
            )
            ->addOption(
                '--end-datetime',
                null,
                InputOption::VALUE_OPTIONAL,
                'Set start date/time for updated values.'
            )
            ->addOption(
                '--first-time-sync',
                '-f',
                InputOption::VALUE_NONE,
                'Notate if this is a first time sync where Mautic will sync existing objects instead of just tracked changes'
            );

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $inputOptions = new InputOptionsDAO($input);
        } catch (InvalidValueException $e) {
            $io->error($e->getMessage());

            return 1;
        }

        try {
            defined('MAUTIC_INTEGRATION_SYNC_IN_PROGRESS') or define('MAUTIC_INTEGRATION_SYNC_IN_PROGRESS', $inputOptions->getIntegration());

            $this->syncService->processIntegrationSync($inputOptions);
        } catch (\Exception $e) {
            if ($inputOptions->getEnv() === 'dev' || MAUTIC_ENV === 'dev') {
                throw $e;
            }

            $io->error($e->getMessage());

            return 1;
        }

        $io->success('Execution time: '.number_format(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 3));

        return 0;
    }
}
