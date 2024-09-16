<?php

namespace Mautic\CoreBundle\Command;

use Mautic\CoreBundle\Exception\UpdateFailedException;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\ProgressBarHelper;
use Mautic\CoreBundle\Update\StepProvider;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\SymfonyQuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * CLI Command to update the application.
 */
class ApplyUpdatesCommand extends ContainerAwareCommand
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    /**
     * @var StepProvider
     */
    private $stepProvider;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var ProgressBar
     */
    private $progressBar;

    public function __construct(TranslatorInterface $translator, CoreParametersHelper $coreParametersHelper, StepProvider $stepProvider)
    {
        parent::__construct();

        $this->translator           = $translator;
        $this->coreParametersHelper = $coreParametersHelper;
        $this->stepProvider         = $stepProvider;
    }

    protected function configure()
    {
        $this->setName('mautic:update:apply')
            ->setDescription('Updates the Mautic application')
            ->setDefinition(
                [
                    new InputOption(
                        'force', null, InputOption::VALUE_NONE,
                        'Bypasses the verification check.'
                    ),
                    new InputOption(
                        'update-package',
                        'p', InputOption::VALUE_OPTIONAL, 'Optional full path to the update package to apply.'
                    ),
                    new InputOption(
                        'finish', null, InputOption::VALUE_NONE,
                        'Finalize the upgrade.'
                    ),
                ]
            )
            ->setHelp(
                <<<'EOT'
                The <info>%command.name%</info> command updates the Mautic application.

<info>php %command.full_name%</info>

You can optionally specify to bypass the verification check with the --force option:

<info>php %command.full_name% --force</info>

To force install a local package, pass the full path to the package as follows:

<info>php %command.full_name% --update-package=/path/to/updatepackage.zip</info>
EOT
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input  = $input;
        $this->output = $output;

        $options = $input->getOptions();

        // Set the locale for the translator
        $this->translator->setLocale($this->coreParametersHelper->get('locale'));

        // Start a progress bar, don't give a max number of steps because it is conditional
        $this->progressBar = ProgressBarHelper::init($this->output);
        $this->progressBar->setFormat('Step %current% [%bar%] <info>%message%</info>');

        // Define this just in case
        defined('MAUTIC_ENV') or define('MAUTIC_ENV', (isset($options['env'])) ? $options['env'] : 'prod');

        if (true === $this->coreParametersHelper->get('composer_updates', false)) {
            $output->writeln('<error>'.$this->translator->trans('mautic.core.command.update.composer').'</error>');

            return 1;
        }

        try {
            if (empty($options['finish'])) {
                $returnCode = $this->startUpgrade();

                $output->writeln(
                    "\n\n<warning>".$this->translator->trans('mautic.core.command.update.finalize_instructions').'</warning>'
                );

                // Must hard exit here to prevent Symfony from trying to use the kernel while in the same PHP process
                exit($returnCode);
            }

            return $this->finishUpgrade();
        } catch (UpdateFailedException $exception) {
            $output->writeln(
                "\n\n<error>".$exception->getMessage().'</error>'
            );
        }

        return 1;
    }

    /**
     * @throws UpdateFailedException
     */
    private function startUpgrade(): int
    {
        if (!$this->input->getOption('force')) {
            /** @var SymfonyQuestionHelper $helper */
            $helper   = $this->getHelperSet()->get('question');
            $question = new ConfirmationQuestion($this->translator->trans('mautic.core.update.confirm_application_update').' ', false);

            if (!$helper->ask($this->input, $this->output, $question)) {
                throw new UpdateFailedException($this->translator->trans('mautic.core.update.aborted'));
            }
        }

        foreach ($this->stepProvider->getInitialSteps() as $step) {
            $step->execute($this->progressBar, $this->input, $this->output);
        }

        return 0;
    }

    /**
     * @throws UpdateFailedException
     */
    private function finishUpgrade(): int
    {
        foreach ($this->stepProvider->getFinalSteps() as $step) {
            $step->execute($this->progressBar, $this->input, $this->output);
        }

        return 0;
    }
}
