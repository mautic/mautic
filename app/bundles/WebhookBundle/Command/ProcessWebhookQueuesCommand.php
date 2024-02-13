<?php

namespace Mautic\WebhookBundle\Command;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\WebhookBundle\Model\WebhookModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI Command to process queued webhook payloads.
 */
class ProcessWebhookQueuesCommand extends Command
{
    public const COMMAND_NAME = 'mautic:webhooks:process';

    public function __construct(
        private CoreParametersHelper $coreParametersHelper,
        private WebhookModel $webhookModel
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName(self::COMMAND_NAME)
            ->addOption(
                '--webhook-id',
                '-i',
                InputOption::VALUE_OPTIONAL,
                'Process payload for a specific webhook.  If not specified, all webhooks will be processed.',
                null
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // check to make sure we are in queue mode
        if ($this->coreParametersHelper->get('queue_mode') != $this->webhookModel::COMMAND_PROCESS) {
            $output->writeLn('Webhook Bundle is in immediate process mode. To use the command function change to command mode.');

            return \Symfony\Component\Console\Command\Command::SUCCESS;
        }

        $id = $input->getOption('webhook-id');

        if ($id) {
            $webhook  = $this->webhookModel->getEntity($id);
            $webhooks = (null !== $webhook && $webhook->isPublished()) ? [$id => $webhook] : [];
        } else {
            // make sure we only get published webhook entities
            $webhooks = $this->webhookModel->getEntities(
                [
                    'filter' => [
                        'force' => [
                            [
                                'column' => 'e.isPublished',
                                'expr'   => 'eq',
                                'value'  => 1,
                            ],
                        ],
                    ],
                ]
            );
        }

        if (!count($webhooks)) {
            $output->writeln('<error>No published webhooks found. Try again later.</error>');

            return \Symfony\Component\Console\Command\Command::FAILURE;
        }

        $output->writeLn('<info>Processing Webhooks</info>');

        try {
            $this->webhookModel->processWebhooks($webhooks);
        } catch (\Exception $e) {
            $output->writeLn('<error>'.$e->getMessage().'</error>');
            $output->writeLn('<error>'.$e->getTraceAsString().'</error>');

            return \Symfony\Component\Console\Command\Command::FAILURE;
        }

        $output->writeLn('<info>Webhook Processing Complete</info>');

        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }

    protected static $defaultDescription = 'Process queued webhook payloads';
}
