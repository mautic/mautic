<?php

namespace Mautic\CoreBundle\Command;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\MaintenanceEvent;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CLI Command to purge old data per settings.
 */
class CleanupMaintenanceCommand extends ModeratedCommand
{
    public function __construct(
        private TranslatorInterface $translator,
        private EventDispatcherInterface $dispatcher,
        PathsHelper $pathsHelper,
        CoreParametersHelper $coreParametersHelper
    ) {
        parent::__construct($pathsHelper, $coreParametersHelper);
    }

    protected function configure()
    {
        $this->setName('mautic:maintenance:cleanup')
            ->setDefinition(
                [
                    new InputOption(
                        'days-old',
                        'd',
                        InputOption::VALUE_OPTIONAL,
                        'Purge records older than this number of days. Defaults to 365.',
                        365
                    ),
                    new InputOption('dry-run', 'r', InputOption::VALUE_NONE, 'Do a dry run without actually deleting anything.'),
                    new InputOption('gdpr', 'g', InputOption::VALUE_NONE, 'Delete data to fullfil GDPR requirement.'),
                ]
            )
            ->setHelp(
                <<<'EOT'
                The <info>%command.name%</info> command dispatches the CoreEvents::MAINTENANCE_CLEANUP_DATA event in order to purge old data (data must be supported by event listeners as not all data is applicable to be purged).

<info>php %command.full_name%</info>

Specify the number of days old data should be before purging.

<info>php %command.full_name% --days-old=365</info>

You can also optionally specify a dry run without deleting any records:

<info>php %command.full_name% --days-old=365 --dry-run</info>
EOT
            );
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->checkRunStatus($input, $output)) {
            return \Symfony\Component\Console\Command\Command::SUCCESS;
        }

        $daysOld       = $input->getOption('days-old');
        $dryRun        = $input->getOption('dry-run');
        $noInteraction = $input->getOption('no-interaction');
        $gdpr          = $input->getOption('gdpr');
        if (empty($daysOld) && empty($gdpr)) {
            // Safety catch; bail
            return \Symfony\Component\Console\Command\Command::FAILURE;
        }

        if (!empty($gdpr)) {
            // to fullfil GDPR, you must delete inactive user data older than 3years
            $daysOld = 365 * 3;
        }

        if (empty($dryRun) && empty($noInteraction)) {
            /** @var \Symfony\Component\Console\Helper\SymfonyQuestionHelper $helper */
            $helper   = $this->getHelperSet()->get('question');
            $question = new ConfirmationQuestion(
                '<info>'.$this->translator->trans('mautic.maintenance.confirm_data_purge', ['%days%' => $daysOld]).'</info> ', false
            );

            if (!$helper->ask($input, $output, $question)) {
                $this->completeRun();

                return \Symfony\Component\Console\Command\Command::SUCCESS;
            }
        }

        $event = new MaintenanceEvent($daysOld, !empty($dryRun), !empty($gdpr));
        $this->dispatcher->dispatch($event, CoreEvents::MAINTENANCE_CLEANUP_DATA);
        $stats = $event->getStats();

        $rows = [];
        foreach ($stats as $key => $count) {
            $rows[] = [$key, $count];
        }

        $table = new Table($output);
        $table
            ->setHeaders([$this->translator->trans('mautic.maintenance.header.key'), $this->translator->trans('mautic.maintenance.header.records_affected')])
            ->setRows($rows);
        $table->render();

        if ('dev' == MAUTIC_ENV) {
            $output->writeln('<comment>Debug</comment>');
            $debug = $event->getDebug();

            foreach ($debug as $key => $query) {
                $output->writeln("<info>$key</info>");
                $output->writeln($query);
            }
        }

        $this->completeRun();

        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }

    protected static $defaultDescription = 'Updates the Mautic application';
}
