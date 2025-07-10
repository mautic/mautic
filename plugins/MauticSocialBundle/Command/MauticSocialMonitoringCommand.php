<?php

namespace MauticPlugin\MauticSocialBundle\Command;

use MauticPlugin\MauticSocialBundle\Entity\MonitoringRepository;
use MauticPlugin\MauticSocialBundle\Model\MonitoringModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MauticSocialMonitoringCommand extends Command
{
    public function __construct(
        private MonitoringModel $monitoringModel,
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('mautic:social:monitoring')
            ->addOption('mid', 'i', InputOption::VALUE_OPTIONAL, 'The id of a specific monitor record to process')
            ->addOption(
                'batch-size',
                null,
                InputOption::VALUE_REQUIRED,
                'The maximum number of iterations the cron runs per cycle. This value gets distributed by the number of monitor records published'
            )
            ->addOption('query-count', null, InputOption::VALUE_OPTIONAL, 'The number of records to search for per iteration. Default is 100.', 100);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // get the mid from the cli
        $batchSize = $input->getOption('batch-size');

        // monitor record
        $monitorId   = $input->getOption('mid');
        $monitorList = $this->getMonitors($monitorId);

        // no mid found, quit now
        if (!$monitorList->count()) {
            $output->writeln('No published monitors found. Make sure the id you supplied is published');

            return Command::SUCCESS;
        }

        if (!is_numeric($batchSize)) {
            $output->writeln('batch-size is not number.');

            return self::FAILURE;
        }

        // max iterations
        $maxPerIterations = ceil((int) $batchSize / count($monitorList));

        foreach ($monitorList as $monitor) {
            $output->writeln('Executing Monitor Item '.$monitor->getId());
            $resultCode = $this->processMonitorListItem($monitor, $maxPerIterations, $input, $output);
            $output->writeln('Result Code: '.$resultCode);
        }

        return Command::SUCCESS;
    }

    /**
     * @return \Doctrine\ORM\Tools\Pagination\Paginator
     */
    protected function getMonitors($id = null)
    {
        $filter = [
            'start' => 0,
            'limit' => 100,
        ];

        /** @var MonitoringRepository $repository */
        $repository = $this->monitoringModel->getRepository();

        if (null !== $id) {
            $filter['filter'] = [
                'force' => [
                    [
                        'column' => $repository->getTableAlias().'.id',
                        'expr'   => 'eq',
                        'value'  => (int) $id,
                    ],
                ],
            ];
        }

        return $repository->getPublishedEntities($filter);
    }

    /**
     * @return bool|int
     *
     * @throws \Exception
     */
    protected function processMonitorListItem($listItem, float $maxPerIterations, InputInterface $input, OutputInterface $output)
    {
        // @todo set this up to use the command type per-monitor record.
        $networkType = $listItem->getNetworkType();

        $commandName = '';

        // hashtag command
        if ('twitter_hashtag' == $networkType) {
            $commandName = 'social:monitor:twitter:hashtags';
        }

        // mention command
        if ('twitter_handle' == $networkType) {
            $commandName = 'social:monitor:twitter:mentions';
        }

        if ('' == $commandName) {
            $output->writeln('Matching command not found.');

            return 1;
        }

        // monitor hash command
        $command = $this->getApplication()->find($commandName);

        // create command options
        $cliArgs = [
            'command'       => $commandName,
            '--mid'         => $listItem->getId(),
            '--max-runs'    => $maxPerIterations,
            '--query-count' => $input->getOption('query-count'),
        ];

        // execute the command
        $returnCode = $command->run(new ArrayInput($cliArgs), $output);

        return $returnCode;
    }

    protected static $defaultDescription = 'Looks at the records of monitors and iterates through them. ';
}
