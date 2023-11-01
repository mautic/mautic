<?php

namespace Mautic\EmailBundle\Command;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\EmailBundle\MonitoredEmail\Fetcher;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command to check for messages.
 */
class ProcessFetchEmailCommand extends Command
{
    /**
     * @var CoreParametersHelper
     */
    private $parametersHelper;

    /**
     * @var Fetcher
     */
    private $fetcher;

    /**
     * ProcessFetchEmailCommand constructor.
     */
    public function __construct(CoreParametersHelper $parametersHelper, Fetcher $fetcher)
    {
        parent::__construct();

        $this->parametersHelper = $parametersHelper;
        $this->fetcher          = $fetcher;
    }

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
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $limit     = $input->getOption('message-limit');
        $mailboxes = $this->parametersHelper->get('monitored_email');
        unset($mailboxes['general']);
        $mailboxes = array_keys($mailboxes);

        $this->fetcher->setMailboxes($mailboxes)
            ->fetch($limit);

        foreach ($this->fetcher->getLog() as $log) {
            $output->writeln($log);
        }

        return 0;
    }
}
