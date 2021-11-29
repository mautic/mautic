<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Update\Step;

use Mautic\CoreBundle\Exception\UpdateFailedException;
use Mautic\CoreBundle\Helper\ComposerHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ComposerUpdateStep implements StepInterface
{
    private TranslatorInterface $translator;
    private LoggerInterface $logger;
    private ComposerHelper $composerHelper;

    public function __construct(TranslatorInterface $translator, LoggerInterface $logger, ComposerHelper $composerHelper)
    {
        $this->translator     = $translator;
        $this->logger         = $logger;
        $this->composerHelper = $composerHelper;
    }

    public function getOrder(): int
    {
        return 15;
    }

    public function shouldExecuteInFinalStage(): bool
    {
        return false;
    }

    public function execute(ProgressBar $progressBar, InputInterface $input, OutputInterface $output): void
    {
        $progressBar->setMessage($this->translator->trans('mautic.core.update.composer.update'));
        $progressBar->advance();

        $result = $this->composerHelper->update();

        if (0 !== $result->exitCode) {
            $this->logger->error('Error while running "composer update" in Mautic update: '.$result->output);
            throw new UpdateFailedException($this->translator->trans('mautic.core.update.error', ['%error%' => $this->translator->trans('mautic.core.update.composer.update.error')]));
        }
    }
}
