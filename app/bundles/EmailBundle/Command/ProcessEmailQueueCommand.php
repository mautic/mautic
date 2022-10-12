<?php

namespace Mautic\EmailBundle\Command;

use Mautic\CoreBundle\Command\ModeratedCommand;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\QueueEmailEvent;
use Swift_Transport;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Finder\Finder;

/**
 * CLI command to process the e-mail queue.
 */
class ProcessEmailQueueCommand extends ModeratedCommand
{
    private Swift_Transport $swiftTransport;
    private EventDispatcherInterface $eventDispatcher;
    private CoreParametersHelper $parametersHelper;

    public function __construct(Swift_Transport $swiftTransport, EventDispatcherInterface $eventDispatcher, CoreParametersHelper $parametersHelper)
    {
        parent::__construct();

        $this->swiftTransport   = $swiftTransport;
        $this->eventDispatcher  = $eventDispatcher;
        $this->parametersHelper = $parametersHelper;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('mautic:emails:send')
            ->setDescription('Processes SwiftMail\'s mail queue')
            ->addOption('--message-limit', null, InputOption::VALUE_OPTIONAL, 'Limit number of messages sent at a time. Defaults to value set in config.')
            ->addOption('--time-limit', null, InputOption::VALUE_OPTIONAL, 'Limit the number of seconds per batch. Defaults to value set in config.')
            ->addOption('--do-not-clear', null, InputOption::VALUE_NONE, 'By default, failed messages older than the --recover-timeout setting will be attempted one more time then deleted if it fails again.  If this is set, sending of failed messages will continue to be attempted.')
            ->addOption('--recover-timeout', null, InputOption::VALUE_OPTIONAL, 'Sets the amount of time in seconds before attempting to resend failed messages.  Defaults to value set in config.')
            ->addOption('--clear-timeout', null, InputOption::VALUE_OPTIONAL, 'Sets the amount of time in seconds before deleting failed messages.  Defaults to value set in config.')
            ->addOption('--lock-name', null, InputOption::VALUE_OPTIONAL, 'Set name of lock to run multiple mautic:emails:send command at time')
            ->setHelp(<<<'EOT'
The <info>%command.name%</info> command is used to process the application's e-mail queue

<info>php %command.full_name%</info>
EOT
        );

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $options     = $input->getOptions();
        $env         = (!empty($options['env'])) ? $options['env'] : 'dev';
        $skipClear   = $input->getOption('do-not-clear');
        $quiet       = (bool) $input->getOption('quiet');
        $timeout     = $input->getOption('clear-timeout');
        $queueMode   = $this->parametersHelper->get('mailer_spool_type');
        $lockName    = $input->getOption('lock-name') ?? '';

        if ('file' !== $queueMode) {
            $output->writeln('Mautic is not set to queue email.');

            return 0;
        }

        if (!$this->checkRunStatus($input, $output, $lockName)) {
            return 0;
        }

        if (empty($timeout)) {
            $timeout = $this->parametersHelper->get('mautic.mailer_spool_clear_timeout');
        }

        if (!$skipClear) {
            //Swift mailer's send command does not handle failed messages well rather it will retry sending forever
            //so let's first handle emails stuck in the queue and remove them if necessary
            if (!$this->swiftTransport->isStarted()) {
                $this->swiftTransport->start();
            }

            $spoolPath = $this->parametersHelper->get('mautic.mailer_spool_path');
            if (file_exists($spoolPath)) {
                $finder = Finder::create()->in($spoolPath)->name('*.{finalretry,sending,tryagain}');

                foreach ($finder as $failedFile) {
                    $file = $failedFile->getRealPath();

                    $lockedtime = filectime($file);
                    if (!(time() - $lockedtime) > $timeout) {
                        //the file is not old enough to be resent yet
                        continue;
                    }
                    if (!$handle = @fopen($file, 'r+')) {
                        continue;
                    }
                    if (!flock($handle, LOCK_EX | LOCK_NB)) {
                        /* This message has just been catched by another process */
                        continue;
                    }

                    //rename the file so no other process tries to find it
                    $tmpFilename = str_replace(['.finalretry', '.sending', '.tryagain'], '', $file);
                    $tmpFilename .= '.finalretry';
                    rename($file, $tmpFilename);

                    $message = unserialize(file_get_contents($tmpFilename));
                    if (false !== $message && is_object($message) && 'Swift_Message' === get_class($message)) {
                        $tryAgain = false;
                        if ($this->eventDispatcher->hasListeners(EmailEvents::EMAIL_RESEND)) {
                            $event = new QueueEmailEvent($message);
                            $this->eventDispatcher->dispatch(EmailEvents::EMAIL_RESEND, $event);
                            $tryAgain = $event->shouldTryAgain();
                        }

                        try {
                            $this->swiftTransport->send($message);
                        } catch (\Swift_TransportException $e) {
                            if (!$tryAgain && $this->eventDispatcher->hasListeners(EmailEvents::EMAIL_FAILED)) {
                                $event = new QueueEmailEvent($message);
                                $this->eventDispatcher->dispatch(EmailEvents::EMAIL_FAILED, $event);
                            }
                        }
                    } else {
                        // $message isn't a valid message file
                        $tryAgain = false;
                    }
                    if ($tryAgain) {
                        $retryFilename = str_replace('.finalretry', '.tryagain', $tmpFilename);
                        rename($tmpFilename, $retryFilename, $handle);
                    } else {
                        //delete the file, either because it sent or because it failed
                        unlink($tmpFilename);
                    }
                    fclose($handle);
                }
            }
        }

        //now process new emails
        if ($quiet) {
            $output->setVerbosity(OutputInterface::VERBOSITY_QUIET);
        }

        $command     = $this->getApplication()->find('swiftmailer:spool:send');
        $commandArgs = [
            'command' => 'swiftmailer:spool:send',
            '--env'   => $env,
        ];
        if ($quiet) {
            $commandArgs['--quiet'] = true;
        }

        //set spool message limit
        if ($msgLimit = $input->getOption('message-limit')) {
            $commandArgs['--message-limit'] = $msgLimit;
        } elseif ($msgLimit = $this->parametersHelper->get('mautic.mailer_spool_msg_limit')) {
            $commandArgs['--message-limit'] = $msgLimit;
        }

        //set time limit
        if ($timeLimit = $input->getOption('time-limit')) {
            $commandArgs['--time-limit'] = $timeLimit;
        } elseif ($timeLimit = $this->parametersHelper->get('mautic.mailer_spool_time_limit')) {
            $commandArgs['--time-limit'] = $timeLimit;
        }

        //set the recover timeout
        if ($timeout = $input->getOption('recover-timeout')) {
            $commandArgs['--recover-timeout'] = $timeout;
        } elseif ($timeout = $this->parametersHelper->get('mautic.mailer_spool_recover_timeout')) {
            $commandArgs['--recover-timeout'] = $timeout;
        }
        $input      = new ArrayInput($commandArgs);
        $returnCode = $command->run($input, $output);

        $this->completeRun();

        if (0 !== $returnCode) {
            return $returnCode;
        }

        return 0;
    }
}
