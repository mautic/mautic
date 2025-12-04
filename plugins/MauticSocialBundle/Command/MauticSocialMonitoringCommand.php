<?php

namespace MauticPlugin\MauticSocialBundle\Command;

use MauticPlugin\MauticSocialBundle\Entity\MonitoringRepository;
use MauticPlugin\MauticSocialBundle\Model\MonitoringModel;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'mautic:social:monitoring',
    description: 'Looks at the records of monitors and iterates through them.'
)]
class MauticSocialMonitoringCommand
{
    public function __construct(
        private MonitoringModel $monitoringModel,
        private MonitorTwitterHashtagsCommand $hashtagsCommand,
        private MonitorTwitterMentionsCommand $mentionsCommand,
    ) {
    }

    public function __invoke(
        OutputInterface $output,
        #[\Symfony\Component\Console\Attribute\Option(name: 'mid', shortcut: 'i', description: 'The id of a specific monitor record to process')]
        $mid = null,
        #[\Symfony\Component\Console\Attribute\Option(name: 'batch-size', description: 'The maximum number of iterations the cron runs per cycle. This value gets distributed by the number of monitor records published')]
        $batchSize = null,
        #[\Symfony\Component\Console\Attribute\Option(name: 'query-count', description: 'The number of records to search for per iteration. Default is 100.')]
        ?int $queryCount = 100,
    ): int {
        // monitor record
        $monitorId   = $mid;
        $monitorList = $this->getMonitors($monitorId);

        // no mid found, quit now
        if (!$monitorList->count()) {
            $output->writeln('No published monitors found. Make sure the id you supplied is published');

            return Command::SUCCESS;
        }

        if (!is_numeric($batchSize)) {
            $output->writeln('batch-size is not number.');

            return Command::FAILURE;
        }

        // max iterations
        $maxPerIterations = ceil((int) $batchSize / count($monitorList));

        foreach ($monitorList as $monitor) {
            $output->writeln('Executing Monitor Item '.$monitor->getId());
            $resultCode = $this->processMonitorListItem($monitor, $maxPerIterations, $output, $queryCount);
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
    protected function processMonitorListItem($listItem, float $maxPerIterations, OutputInterface $output, int $queryCount)
    {
        // @todo set this up to use the command type per-monitor record.
        $networkType = $listItem->getNetworkType();

        $command     = null;
        $commandName = '';

        // hashtag command
        if ('twitter_hashtag' == $networkType) {
            $command     = $this->hashtagsCommand;
            $commandName = 'social:monitor:twitter:hashtags';
        }

        // mention command
        if ('twitter_handle' == $networkType) {
            $command     = $this->mentionsCommand;
            $commandName = 'social:monitor:twitter:mentions';
        }

        if (null === $command) {
            $output->writeln('Matching command not found.');

            return 1;
        }

        // create command options
        $cliArgs = [
            'command'       => $commandName,
            '--mid'         => $listItem->getId(),
            '--max-runs'    => $maxPerIterations,
            '--query-count' => $queryCount,
        ];

        // execute the command
        $returnCode = $command->run(new ArrayInput($cliArgs), $output);

        return $returnCode;
    }
}
