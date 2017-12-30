<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MauticSocialMonitoringCommand extends ContainerAwareCommand
{
    protected $batchSize;

    /**
     * @var \MauticPlugin\MauticSocialBundle\Entity\MonitoringRepository;
     */
    protected $monitorRepo;

    /**
     * @var
     */
    protected $maxPerIterations;

    /**
     * @var
     */
    protected $output;

    /**
     * @var
     */
    protected $input;

    /**
     * Configure the command.
     */
    protected function configure()
    {
        $this->setName('mautic:social:monitoring')
            ->setDescription('Looks at the records of monitors and iterates through them. ')
            ->addOption('mid', 'i', InputOption::VALUE_OPTIONAL, 'The id of a specific monitor record to process')
            ->addOption(
                'batch-size',
                null,
                InputOption::VALUE_REQUIRED,
                'The maximum number of iterations the cron runs per cycle. This value gets distributed by the number of monitor records published'
            )
            ->addOption('query-count', null, InputOption::VALUE_OPTIONAL, 'The number of records to search for per iteration. Default is 100.', 100);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input  = $input;
        $this->output = $output;

        /** @var \MauticPlugin\MauticSocialBundle\Model\MonitoringModel $model */
        $model = $this->getContainer()
            ->get('mautic.social.model.monitoring');

        // set the repository
        $this->monitorRepo = $model->getRepository();

        $translator = $this->getContainer()->get('translator');
        $translator->setLocale($this->getContainer()->getParameter('mautic.locale'));

        // get the mid from the cli
        $this->batchSize = $this->input->getOption('batch-size');

        // monitor record
        $monitorId   = $input->getOption('mid');
        $monitorList = $this->getMonitors($monitorId);

        // no mid found, quit now
        if (!$monitorList->count()) {
            $this->output->writeln('No published monitors found. Make sure the id you supplied is published');

            return;
        }

        // max iterations
        $this->maxPerIterations = ceil($this->batchSize / count($monitorList));

        foreach ($monitorList as $monitor) {
            $this->output->writeln('Executing Monitor Item '.$monitor->getId());
            $resultCode = $this->processMonitorListItem($monitor);
            $this->output->writeln('Result Code: '.$resultCode);
        }

        return 0;
    }

    /**
     * @param null $id
     *
     * @return \Doctrine\ORM\Tools\Pagination\Paginator
     */
    protected function getMonitors($id = null)
    {
        $filter = [
            'start' => 0,
            'limit' => 100,
        ];

        if ($id !== null) {
            $filter['filter'] = [
                'force' => [
                    [
                        'column' => $this->monitorRepo->getTableAlias().'.id',
                        'expr'   => 'eq',
                        'value'  => (int) $id,
                    ],
                ],
            ];
        }

        $monitorList = $this->monitorRepo->getPublishedEntities($filter);

        return $monitorList;
    }

    /**
     * @param $listItem
     *
     * @return bool|int
     *
     * @throws \Exception
     */
    protected function processMonitorListItem($listItem)
    {
        // @todo set this up to use the command type per-monitor record.
        $networkType = $listItem->getNetworkType();

        $commandName = '';

        // hashtag command
        if ($networkType == 'twitter_hashtag') {
            $commandName = 'social:monitor:twitter:hashtags';
        }

        // mention command
        if ($networkType == 'twitter_handle') {
            $commandName = 'social:monitor:twitter:mentions';
        }

        if ($commandName == '') {
            $this->output->writeln('Matching command not found.');

            return 1;
        }

        // monitor hash command
        $command = $this->getApplication()->find($commandName);

        // create command options
        $cliArgs = [
            'command'       => $commandName,
            '--mid'         => $listItem->getId(),
            '--max-runs'    => $this->maxPerIterations,
            '--query-count' => $this->input->getOption('query-count'),
        ];

        // create an input array
        $input = new ArrayInput($cliArgs);

        // execute the command
        $returnCode = $command->run($input, $this->output);

        return $returnCode;
    }
}
