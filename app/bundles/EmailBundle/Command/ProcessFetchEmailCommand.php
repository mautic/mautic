<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Command;

use Mautic\EmailBundle\MonitoredEmail\Fetcher;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command to check for messages.
 */
class ProcessFetchEmailCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('mautic:email:fetch')
            ->setAliases(
                [
                    'mautic:emails:fetch',
                ]
            )
            ->setDescription('Fetch and process monitored email.')
            ->addOption('--message-limit', '-m', InputOption::VALUE_OPTIONAL, 'Limit number of messages to process at a time.')
            ->setHelp(
                <<<'EOT'
                The <info>%command.name%</info> command is used to fetch and process messages such as bounces and unsubscribe requests. Configure the Monitored Email settings in Mautic's Configuration.

<info>php %command.full_name%</info>
EOT
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container  = $this->getContainer();
        $dispatcher = $container->get('event_dispatcher');
        $translator = $container->get('translator');
        $limit      = $input->getOption('message-limit');
        $mailboxes  = $container->get('mautic.helper.core_parameters')->getParameter('monitored_email');
        unset($mailboxes['general']);
        $mailboxes = array_keys($mailboxes);

        /** @var \Mautic\EmailBundle\MonitoredEmail\Mailbox $imapHelper */
        $imapHelper = $container->get('mautic.helper.mailbox');

        $fetcher = new Fetcher($imapHelper, $dispatcher, $translator, $mailboxes);
        $fetcher->fetch($limit);

        foreach ($fetcher->getLog() as $log) {
            $output->writeln($log);
        }

        return 0;
    }
}
