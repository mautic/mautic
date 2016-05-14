<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Command;

use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\ParseEmailEvent;
use Mautic\EmailBundle\Helper\ImapHelper;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command to check for messages
 */
class ProcessFetchEmailCommand extends ContainerAwareCommand
{

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('mautic:fetch:email')
            ->setAliases(array(
                'mautic:email:fetch',
                'mautic:fetch:mail',
                'mautic:check:email',
                'mautic:check:mail'
            ))
            ->setDescription('Fetch and process monitored email.')
            ->addOption('--message-limit', '-m', InputOption::VALUE_OPTIONAL, 'Limit number of messages to process at a time.')
            ->setHelp(<<<EOT
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
        /** @var \Mautic\CoreBundle\Factory\MauticFactory $factory */
        $factory    = $container->get('mautic.factory');
        $dispatcher = $factory->getDispatcher();
        $limit      = $input->getOption('message-limit');
        $mailboxes  = $factory->getParameter('monitored_email');
        unset($mailboxes['general']);
        $mailboxes  = array_keys($mailboxes);

        /** @var \Mautic\EmailBundle\MonitoredEmail\Mailbox $imapHelper */
        $imapHelper = $factory->getHelper('mailbox');

        // Group mailboxes/folders so that we aren't fetching multiple times
        $searchMailboxes = array();

        foreach ($mailboxes as $name) {
            // Switch mailbox to get information
            $config = $imapHelper->getMailboxSettings($name);
            if (empty($config['host']) || empty($config['folder'])) {
                // Not configured so continue
                continue;
            }

            $searchMailboxes[$config['imap_path'] . '_' . $config['user']][] = $name;
        }

        $counter = 0;
        foreach ($searchMailboxes as $imapPath => $folders) {
            try {
                // Get mail and parse into Message objects
                $messages = array();

                $imapHelper->switchMailbox($folders[0]);

                $mailIds = $imapHelper->fetchUnread();
                $processed = 0;
                if (count($mailIds)) {
                    foreach ($mailIds as $id) {
                        $messages[] = $imapHelper->getMail($id);
                        $counter++;
                        $processed++;

                        if ($limit && $counter >= $limit) {
                            break;
                        }
                    }

                    if ($dispatcher->hasListeners(EmailEvents::EMAIL_PARSE)) {
                        $event = new ParseEmailEvent($messages, $folders);
                        $dispatcher->dispatch(EmailEvents::EMAIL_PARSE, $event);
                    }

                    $output->writeln($processed . ' emails processed for ' . $imapPath);

                    if ($limit && $counter >= $limit) {
                        break;
                    }
                } else {
                    $output->writeln('0 emails processed for ' . $imapPath);
                }
            } catch (\Exception $e) {
                $output->writeln($e->getMessage());
            }

            unset($mailIds, $messages);
        }

        if (empty($searchMailboxes)) {

            $output->writeln('No mailboxes are configured.');
        }

        return 0;
    }
}
