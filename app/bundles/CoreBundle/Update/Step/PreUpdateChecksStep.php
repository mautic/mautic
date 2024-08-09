<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Update\Step;

use Mautic\CoreBundle\Exception\UpdateFailedException;
use Mautic\CoreBundle\Helper\Update\PreUpdateChecks\PreUpdateCheckError;
use Mautic\CoreBundle\Helper\UpdateHelper;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PreUpdateChecksStep implements StepInterface
{
    public function __construct(
        private TranslatorInterface $translator,
        private UpdateHelper $updateHelper
    ) {
    }

    public function getOrder(): int
    {
        return 0;
    }

    public function shouldExecuteInFinalStage(): bool
    {
        return false;
    }

    /**
     * @throws UpdateFailedException
     */
    public function execute(ProgressBar $progressBar, InputInterface $input, OutputInterface $output): void
    {
        /**
         * We can't easily fetch data about an update package without unzipping it first, so for now
         * we skip the pre-update checks if the user manually provides an update package.
         */
        if (!empty($input->getOption('update-package'))) {
            return;
        }

        $results = $this->updateHelper->runPreUpdateChecks();
        $errors  = [];

        $progressBar->setMessage($this->translator->trans('mautic.core.command.update.step.checks'));
        $progressBar->advance();

        foreach ($results as $result) {
            if (!$result->success) {
                $errors = array_merge($errors, array_map(fn (PreUpdateCheckError $error) => $this->translator->trans($error->key, $error->parameters), $result->errors));
            }
        }

        if (!empty($errors)) {
            $errorString = '';

            foreach ($errors as $error) {
                $errorString .= "- $error\n";
            }

            throw new UpdateFailedException($this->translator->trans('mautic.core.update.check.error')."\n".$errorString);
        }
    }
}
