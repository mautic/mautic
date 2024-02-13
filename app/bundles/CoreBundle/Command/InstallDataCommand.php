<?php

namespace Mautic\CoreBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CLI Command to install Mautic sample data.
 */
class InstallDataCommand extends Command
{
    public function __construct(
        private TranslatorInterface $translator
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('mautic:install:data')
            ->setDefinition([
                new InputOption(
                    'force', null, InputOption::VALUE_NONE, 'Bypasses the verification check.'
                ),
            ])
            ->setHelp(<<<'EOT'
The <info>%command.name%</info> command re-installs Mautic with sample data.

<info>php %command.full_name%</info>

You can optionally specify to bypass the verification check with the --force option:

<info>php %command.full_name% --force</info>
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $options = $input->getOptions();
        $force   = $options['force'];

        if (!$force) {
            $helper         = $this->getHelper('question');
            $questionString = $this->translator->trans('mautic.core.command.install_data_confirm').' (y = '.$this->translator->trans('mautic.core.form.yes').', n = '.$this->translator->trans('mautic.core.form.no').'): ';
            $question       = new ConfirmationQuestion($questionString, false);

            if (!$helper->ask($input, $output, $question)) {
                return \Symfony\Component\Console\Command\Command::SUCCESS;
            }
        }

        $env = $options['env'];

        // TODO - This should respect the --quiet flag
        $verbosity = $output->getVerbosity();
        $output->setVerbosity(OutputInterface::VERBOSITY_QUIET);

        // due to foreign restraint and truncate issues with doctrine, the whole schema must be dropped and recreated
        $command = $this->getApplication()->find('doctrine:schema:drop');
        $input   = new ArrayInput([
            'command' => 'doctrine:schema:drop',
            '--force' => true,
            '--env'   => $env,
            '--quiet' => true,
        ]);
        $returnCode = $command->run($input, $output);

        if (0 !== $returnCode) {
            return (int) $returnCode;
        }

        // recreate the database
        $command = $this->getApplication()->find('doctrine:schema:create');
        $input   = new ArrayInput([
            'command' => 'doctrine:schema:create',
            '--env'   => $env,
            '--quiet' => true,
        ]);
        $returnCode = $command->run($input, $output);
        if (0 !== $returnCode) {
            return (int) $returnCode;
        }

        // now populate the tables with fixture
        $command = $this->getApplication()->find('doctrine:fixtures:load');
        $args    = [
            'command'  => 'doctrine:fixtures:load',
            '--append' => true,
            '--env'    => $env,
            '--quiet'  => true,
            '--group'  => ['group_mautic_install_data'],
        ];

        $input      = new ArrayInput($args);
        $returnCode = $command->run($input, $output);

        if (0 !== $returnCode) {
            return (int) $returnCode;
        }

        $output->setVerbosity($verbosity);
        $output->writeln(
            $this->translator->trans('mautic.core.command.install_data_success')
        );

        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }

    protected static $defaultDescription = 'Installs Mautic with sample data';
}
