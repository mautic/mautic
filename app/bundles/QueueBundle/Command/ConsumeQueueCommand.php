<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\QueueBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI Command to process orders that have been queued.
 * Class ProcessQueuesCommand.
 */
class ConsumeQueueCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('mautic:queue:process')
            ->setDescription('Process queues')
            ->addOption(
                '--queue-name',
                '-i',
                InputOption::VALUE_OPTIONAL,
                'Process queues orders for a specific queue. If not specified, all queues will be processed.',
                null
            );
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();

      /** @var \Mautic\CoreBundle\Factory\MauticFactory $factory */
      $factory  = $container->get('mautic.factory');
        $useQueue = $factory->getParameter('use_queue');

      // check to make sure we are in queue mode
      if (!$useQueue) {
          $output->writeLn('You have not configured mautic to use queue mode, nothing will be processed');

          return 0;
      }

      // $queue_name = $input->getOption('queue_name');

      // /** @var \Mautic\QueueBundle\Model\QueueModel $model */
      // $model = $this->factory->getModel('queue');
      return 0;
    }
}
