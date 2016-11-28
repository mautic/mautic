<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Command;

use Mautic\CoreBundle\Command\ModeratedCommand;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\QueueEmailEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

/**
 * CLI command to process the e-mail queue.
 */
class ProcessEmailQueueCommand extends ModeratedCommand
{
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
        $options    = $input->getOptions();
        $env        = (!empty($options['env'])) ? $options['env'] : 'dev';
        $container  = $this->getContainer();
        $dispatcher = $container->get('event_dispatcher');

        $skipClear = $input->getOption('do-not-clear');
        $quiet     = $input->getOption('quiet');
        $timeout   = $input->getOption('clear-timeout');
        $queueMode = $container->get('mautic.helper.core_parameters')->getParameter('mailer_spool_type');

        if ($queueMode != 'file') {
            $output->writeln('Mautic is not set to queue email.');

            return 0;
        }

        if (!$this->checkRunStatus($input, $output)) {
            return 0;
        }

        if (empty($timeout)) {
            $timeout = $container->getParameter('mautic.mailer_spool_clear_timeout');
        }

        if (!$skipClear) {
            //Swift mailer's send command does not handle failed messages well rather it will retry sending forever
            //so let's first handle emails stuck in the queue and remove them if necessary
            $transport = $this->getContainer()->get('swiftmailer.transport.real');
            if (!$transport->isStarted()) {
                $transport->start();
            }

            $spoolPath = $container->getParameter('mautic.mailer_spool_path');
            if (file_exists($spoolPath)) {
                $finder = Finder::create()->in($spoolPath)->name('*.{finalretry,sending,tryagain}');

                foreach ($finder as $failedFile) {
                    $file = $failedFile->getRealPath();

                    $lockedtime = filectime($file);
                    if (!(time() - $lockedtime) > $timeout) {
                        //the file is not old enough to be resent yet
                        continue;
                    }

                    //rename the file so no other process tries to find it
                    $tmpFilename = str_replace(['.finalretry', '.sending', '.tryagain'], '', $failedFile);
                    $tmpFilename .= '.finalretry';
                    rename($failedFile, $tmpFilename);

                    $message = unserialize(file_get_contents($tmpFilename));
                    if ($message !== false && is_object($message) && get_class($message) === 'Swift_Message') {
                        $tryAgain = false;
                        if ($dispatcher->hasListeners(EmailEvents::EMAIL_RESEND)) {
                            $event = new QueueEmailEvent($message);
                            $dispatcher->dispatch(EmailEvents::EMAIL_RESEND, $event);
                            $tryAgain = $event->shouldTryAgain();
                        }

                        try {
                            $transport->send($message);
                        } catch (\Swift_TransportException $e) {
                            if ($dispatcher->hasListeners(EmailEvents::EMAIL_FAILED)) {
                                $event = new QueueEmailEvent($message);
                                $dispatcher->dispatch(EmailEvents::EMAIL_FAILED, $event);
                            }
                        }
                    } else {
                        // $message isn't a valid message file
                        $tryAgain = false;
                    }
                    if ($tryAgain) {
                        $retryFilename = str_replace('.finalretry', '.tryagain', $tmpFilename);
                        rename($tmpFilename, $retryFilename);
                    } else {
                        //delete the file, either because it sent or because it failed
                        unlink($tmpFilename);
                    }
                }
            }
        }

        //now process new emails
        if (!$quiet) {
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
        } elseif ($msgLimit = $container->getParameter('mautic.mailer_spool_msg_limit')) {
            $commandArgs['--message-limit'] = $msgLimit;
        }

        //set time limit
        if ($timeLimit = $input->getOption('time-limit')) {
            $commandArgs['--time-limit'] = $timeLimit;
        } elseif ($timeLimit = $container->getParameter('mautic.mailer_spool_time_limit')) {
            $commandArgs['--time-limit'] = $timeLimit;
        }

        //set the recover timeout
        if ($timeout = $input->getOption('recover-timeout')) {
            $commandArgs['--recover-timeout'] = $timeout;
        } elseif ($timeout = $container->getParameter('mautic.mailer_spool_recover_timeout')) {
            $commandArgs['--recover-timeout'] = $timeout;
        }
        $input      = new ArrayInput($commandArgs);
        $returnCode = $command->run($input, $output);

        $this->completeRun();

        if ($returnCode !== 0) {
            return $returnCode;
        }

        return 0;
    }
}
