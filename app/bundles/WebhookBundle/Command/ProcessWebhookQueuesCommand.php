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
    private CoreParametersHelper $coreParametersHelper;
    private WebhookModel $webhookModel;

    public function __construct(CoreParametersHelper $coreParametersHelper, WebhookModel $webhookModel)
    {
        parent::__construct();

        $this->coreParametersHelper = $coreParametersHelper;
        $this->webhookModel         = $webhookModel;
    }

    protected function configure()
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription('Process queued webhook payloads')
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

            return 0;
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

            return 0;
        }

        $output->writeLn('<info>Processing Webhooks</info>');

        try {
            $this->webhookModel->processWebhooks($webhooks);
        } catch (\Exception $e) {
            $output->writeLn('<error>'.$e->getMessage().'</error>');
            $output->writeLn('<error>'.$e->getTraceAsString().'</error>');

            return 1;
        }

        $output->writeLn('<info>Webhook Processing Complete</info>');

        return 0;
    }
}
