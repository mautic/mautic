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

use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\ParseEmailEvent;
use Mautic\EmailBundle\MonitoredEmail\Mailbox;
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

        // Get a list of criteria and group by it
        $event             = $dispatcher->dispatch(EmailEvents::EMAIL_PRE_FETCH, new ParseEmailEvent());
        $criteriaRequested = $event->getCriteriaRequests();

        // Group mailboxes/folders so that we aren't fetching multiple times
        $searchMailboxes = [];

        foreach ($mailboxes as $name) {
            // Switch mailbox to get information
            $config = $imapHelper->getMailboxSettings($name);
            if (empty($config['host']) || empty($config['folder'])) {
                // Not configured so continue
                continue;
            }

            // Default is unread emails
            $criteria = Mailbox::CRITERIA_UNSEEN;

            // Check if a listener injected a criteria request
            if (isset($criteriaRequested[$name])) {
                $criteria = $criteriaRequested[$name];
            }

            if (!isset($searchMailboxes[$criteria])) {
                $searchMailboxes[$criteria] = [];
            }

            $searchMailboxes[$criteria][$config['imap_path'].'_'.$config['user']][] = $name;
        }

        $counter = 0;
        foreach ($searchMailboxes as $criteria => $mailboxes) {
            foreach ($mailboxes as $imapPath => $folders) {
                try {
                    // Get mail and parse into Message objects
                    $messages = [];

                    $imapHelper->switchMailbox($folders[0]);
                    $mailIds = $imapHelper->fetchUnread();

                    $processed = 0;
                    if (count($mailIds)) {
                        foreach ($mailIds as $id) {
                            $messages[] = $imapHelper->getMail($id);
                            ++$counter;
                            ++$processed;

                            if ($limit && $counter >= $limit) {
                                break;
                            }
                        }

                        $event->setMessages($messages)
                            ->setKeys($folders);
                        $dispatcher->dispatch(EmailEvents::EMAIL_PARSE, $event);

                        $output->writeln(
                            $translator->transChoice(
                                'mautic.email.fetch.processed',
                                $processed,
                                ['%processed%' => $processed, '%imapPath%' => $imapPath, '%criteria%' => $criteria]
                            )
                        );

                        if ($limit && $counter >= $limit) {
                            break;
                        }
                    } else {
                        $output->writeln(
                            $translator->transChoice(
                                'mautic.email.fetch.processed',
                                $processed,
                                ['%processed%' => $processed, '%imapPath%' => $imapPath, '%criteria%' => $criteria]
                            )
                        );
                    }
                } catch (\Exception $e) {
                    $output->writeln($e->getMessage());
                }

                unset($mailIds, $messages);
            }
        }

        if (empty($searchMailboxes)) {
            $output->writeln('No mailboxes are configured.');
        }

        return 0;
    }
}
