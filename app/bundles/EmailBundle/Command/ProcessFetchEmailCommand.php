<?php

namespace Mautic\EmailBundle\Command;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\EmailBundle\MonitoredEmail\Fetcher;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command to check for messages.
 */
#[AsCommand(name: 'mautic:email:fetch', description: 'Fetch and process monitored email.', aliases: [
    'mautic:emails:fetch',
], help: <<<'TXT'
                The <info>%command.name%</info> command is used to fetch and process messages such as bounces and unsubscribe requests. Configure the Monitored Email settings in Mautic's Configuration.

<info>php %command.full_name%</info>
TXT)]
class ProcessFetchEmailCommand
{
    public function __construct(
        private CoreParametersHelper $parametersHelper,
        private Fetcher $fetcher,
    ) {
    }

    public function __invoke(
        #[\Symfony\Component\Console\Attribute\Option(name: '--message-limit', shortcut: '-m', description: 'Limit number of messages to process at a time.')]
        $messageLimit = null,
        OutputInterface $output,
    ): int {
        $limit     = $messageLimit;
        $limit     = null === $limit ? null : (int) $limit;
        $mailboxes = $this->parametersHelper->get('monitored_email');
        unset($mailboxes['general']);
        $mailboxes = array_keys($mailboxes);

        $this->fetcher->setMailboxes($mailboxes)
            ->fetch($limit);

        foreach ($this->fetcher->getLog() as $log) {
            $output->writeln($log);
        }

        return Command::SUCCESS;
    }
}
