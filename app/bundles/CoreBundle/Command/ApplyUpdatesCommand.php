<?php

namespace Mautic\CoreBundle\Command;

use Mautic\CoreBundle\Exception\UpdateFailedException;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\ProgressBarHelper;
use Mautic\CoreBundle\Update\StepProvider;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\SymfonyQuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CLI Command to update the application.
 */
#[AsCommand(name: 'mautic:update:apply', description: 'Updates the Mautic application', help: <<<'TXT'
                The <info>%command.name%</info> command updates the Mautic application.

<info>php %command.full_name%</info>

You can optionally specify to bypass the verification check with the --force option:

<info>php %command.full_name% --force</info>

To force install a local package, pass the full path to the package as follows:

<info>php %command.full_name% --update-package=/path/to/updatepackage.zip</info>
TXT)]
class ApplyUpdatesCommand
{
    public function __construct(
        private TranslatorInterface $translator,
        private StepProvider $stepProvider,
        private CoreParametersHelper $coreParametersHelper,
    ) {
    }

    public function __invoke(
        #[\Symfony\Component\Console\Attribute\Option(name: '--force', description: 'Bypasses the verification check.')]
        $force = false,
        #[\Symfony\Component\Console\Attribute\Option(name: '--finish', description: 'Finalize the upgrade.')]
        $finish = null,
        InputInterface $input,
        OutputInterface $output,
    ): int {
        /** @var array<string, mixed> $options */
        $options = $input->getOptions();

        // Start a progress bar, don't give a max number of steps because it is conditional
        $progressBar = ProgressBarHelper::init($output);
        $progressBar->setFormat('Step %current% [%bar%] <info>%message%</info>');

        // Define this just in case
        if (!defined('MAUTIC_ENV')) {
            define('MAUTIC_ENV', $options['env'] ?? 'prod');
        }

        if (true === $this->coreParametersHelper->get('composer_updates', false)) {
            $output->writeln('<error>'.$this->translator->trans('mautic.core.command.update.composer').'</error>');

            return Command::FAILURE;
        }

        try {
            if (empty($finish)) {
                $returnCode = $this->startUpgrade($input, $output, $progressBar, $force);

                $output->writeln(
                    "\n\n<warning>".$this->translator->trans('mautic.core.command.update.finalize_instructions').'</warning>'
                );

                // Must hard exit here to prevent Symfony from trying to use the kernel while in the same PHP process
                exit($returnCode);
            }

            return $this->finishUpgrade($input, $output, $progressBar);
        } catch (UpdateFailedException $exception) {
            $output->writeln(
                "\n\n<error>".$exception->getMessage().'</error>'
            );
        }

        return Command::FAILURE;
    }

    /**
     * @throws UpdateFailedException
     */
    private function startUpgrade(InputInterface $input, OutputInterface $output, ProgressBar $progressBar, bool $force): int
    {
        if (!$force) {
            $helper   = new SymfonyQuestionHelper();
            $question = new ConfirmationQuestion($this->translator->trans('mautic.core.update.confirm_application_update').' ', false);

            if (!$helper->ask($input, $output, $question)) {
                throw new UpdateFailedException($this->translator->trans('mautic.core.update.aborted'));
            }
        }

        foreach ($this->stepProvider->getInitialSteps() as $step) {
            $step->execute($progressBar, $input, $output);
        }

        return Command::SUCCESS;
    }

    /**
     * @throws UpdateFailedException
     */
    private function finishUpgrade(InputInterface $input, OutputInterface $output, ProgressBar $progressBar): int
    {
        foreach ($this->stepProvider->getFinalSteps() as $step) {
            $step->execute($progressBar, $input, $output);
        }

        return Command::SUCCESS;
    }
}
