<?php

namespace Mautic\CoreBundle\Command;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\MaintenanceEvent;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
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
    public const NAME                    = 'mautic:maintenance:cleanup';

    public function __construct(
        private TranslatorInterface $translator,
        private EventDispatcherInterface $dispatcher,
        PathsHelper $pathsHelper,
        private CoreParametersHelper $coreParametersHelper,
        private AuditLogModel $auditLogModel,
        private IpLookupHelper $ipLookupHelper,
    ) {
        parent::__construct($pathsHelper, $coreParametersHelper);
    }

    protected function configure(): void
    {
        $this->setName(self::NAME)
            ->setDefinition(
                [
                    new InputOption(
                        'days-old',
                        'd',
                        InputOption::VALUE_OPTIONAL,
                        'Purge records older than this number of days. Defaults to 365.',
                        365
                    ),
                    new InputOption('dry-run', 'r', InputOption::VALUE_NONE, 'Performs a dry run. Shows no. of affected rows. Won\'t actually delete anything.'),
                    new InputOption('gdpr', 'g', InputOption::VALUE_NONE, 'Deletes records of inactive users to fulfill GDPR requirements.'),
                ]
            )
            ->setHelp(
                <<<'EOT'
<info>%command.name%</info> purges records of anonymous contacts (<comment>unless the <info>--gdpr</info> flag is set</comment>) that are older than 365 days.
Adjust the threshold by using <info>--days-old</info>.

<comment><info>%command.name% --gdpr</info> purges records of anonymous <options=bold>and identified</> contacts.
The command purges only identified contacts that were <options=bold>inactive for more than 3 years</> (1095 days).</comment>

If you set <info>--gdpr</info> then <info>%command.name%</info> will ignore <info>--days-old</info>.
The threshold is hard coded to <info>1095</info> days. This is security measure to prevent accidental loss of contact data.

<comment>Examples:</comment>

<info>php %command.full_name%</info>
Deletes records of anonymous contacts older than 365 days.

<info>php %command.full_name% --days-old=90</info>
Deletes records of anonymous contacts older than 90 days.

<info>php %command.full_name% --gdpr</info>
Deletes records of anonymous <options=bold>and inactive identified</> contacts older than 1095 days.

<comment>Add <info>--dry-run</info> to do a dry run without deleting any records.</comment>

<info>php %command.full_name% --dry-run</info>
Shows you how many records of anonymous contacts <info>%command.name%</info> will purge.

The <info>%command.name%</info> command dispatches the <info>CoreEvents::MAINTENANCE_CLEANUP_DATA</info> event in order to purge old data (data must be supported by event listeners, as not all data is applicable to be purged).
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
        $dryRun        = (bool) $input->getOption('dry-run');
        $noInteraction = $input->getOption('no-interaction');
        $gdpr          = $input->getOption('gdpr');

        if (empty($daysOld) && empty($gdpr)) {
            // Safety catch; bail
            return \Symfony\Component\Console\Command\Command::FAILURE;
        }

        if (!empty($gdpr)) {
            // Override threshold to delete records of inactive users, default 3 years
            $daysOld = $this->coreParametersHelper->get('mautic.gdpr_user_purge_threshold', 1095);
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
        // store to audit log
        $this->storeToAuditLog($stats, $dryRun, $input->getOptions());

        $this->completeRun();

        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }

    /**
     * @param array<int|string>                                   $stats
     * @param array<string|bool|int|float|array<int|string>|null> $options
     */
    protected function storeToAuditLog(array $stats, bool $dryRun, array $options): void
    {
        $notEmptyStats = array_filter($stats);
        if (!$dryRun && count($notEmptyStats)) {
            $log = [
                'userName'  => 'system',
                'userId'    => 0,
                'bundle'    => 'core',
                'object'    => 'maintenance',
                'objectId'  => 0,
                'action'    => 'cleanup',
                'details'   => [
                    'options' => array_filter($options),
                    'stats'   => $notEmptyStats,
                ],
                'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
            ];
            $this->auditLogModel->writeToLog($log);
        }
    }

    protected static $defaultDescription = 'Updates the Mautic application';
}
