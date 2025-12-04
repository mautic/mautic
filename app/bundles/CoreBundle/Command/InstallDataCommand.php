<?php

namespace Mautic\CoreBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\SymfonyQuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CLI Command to install Mautic sample data.
 */
#[AsCommand(name: 'mautic:install:data', description: 'Installs Mautic with sample data', help: <<<'TXT'
The <info>%command.name%</info> command re-installs Mautic with sample data.

<info>php %command.full_name%</info>

You can optionally specify to bypass the verification check with the --force option:

<info>php %command.full_name% --force</info>
TXT)]
class InstallDataCommand
{
    public function __construct(
        private TranslatorInterface $translator,
        #[Autowire(service: 'doctrine.schema_drop_command')]
        private Command $dropSchemaCommand,
        #[Autowire(service: 'doctrine.schema_create_command')]
        private Command $createSchemaCommand,
        #[Autowire(service: 'doctrine.fixtures_load_command')]
        private Command $loadFixturesCommand,
    ) {
    }

    public function __invoke(
        #[\Symfony\Component\Console\Attribute\Option(name: '--force', mode: InputOption::VALUE_NONE, description: 'Bypasses the verification check.')]
        $force,
        InputInterface $input,
        OutputInterface $output,
    ): int {
        if (!$force) {
            $helper         = new SymfonyQuestionHelper();
            $questionString = $this->translator->trans('mautic.core.command.install_data_confirm').' (y = '.$this->translator->trans('mautic.core.form.yes').', n = '.$this->translator->trans('mautic.core.form.no').'): ';
            $question       = new ConfirmationQuestion($questionString, false);

            if (!$helper->ask($input, $output, $question)) {
                return Command::SUCCESS;
            }
        }

        $env = $input->getOption('env') ?? 'prod';

        // TODO - This should respect the --quiet flag
        $verbosity = $output->getVerbosity();
        $output->setVerbosity(OutputInterface::VERBOSITY_QUIET);

        // due to foreign restraint and truncate issues with doctrine, the whole schema must be dropped and recreated
        $input   = new ArrayInput([
            'command' => 'doctrine:schema:drop',
            '--force' => true,
            '--env'   => $env,
            '--quiet' => true,
        ]);
        $returnCode = $this->dropSchemaCommand->run($input, $output);

        if (0 !== $returnCode) {
            return (int) $returnCode;
        }

        // recreate the database
        $input   = new ArrayInput([
            'command' => 'doctrine:schema:create',
            '--env'   => $env,
            '--quiet' => true,
        ]);
        $returnCode = $this->createSchemaCommand->run($input, $output);
        if (0 !== $returnCode) {
            return (int) $returnCode;
        }

        // now populate the tables with fixture
        $args    = [
            'command'  => 'doctrine:fixtures:load',
            '--append' => true,
            '--env'    => $env,
            '--quiet'  => true,
            '--group'  => ['group_mautic_install_data'],
        ];

        $input      = new ArrayInput($args);
        $returnCode = $this->loadFixturesCommand->run($input, $output);

        if (0 !== $returnCode) {
            return (int) $returnCode;
        }

        $output->setVerbosity($verbosity);
        $output->writeln(
            $this->translator->trans('mautic.core.command.install_data_success')
        );

        return Command::SUCCESS;
    }
}
