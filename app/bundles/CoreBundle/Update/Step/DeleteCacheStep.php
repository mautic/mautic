<?php

namespace Mautic\CoreBundle\Update\Step;

use Mautic\CoreBundle\Helper\CacheHelper;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class DeleteCacheStep implements StepInterface
{
    public function __construct(
        private CacheHelper $cacheHelper,
        private TranslatorInterface $translator
    ) {
    }

    public function getOrder(): int
    {
        return 30;
    }

    public function shouldExecuteInFinalStage(): bool
    {
        return false;
    }

    public function execute(ProgressBar $progressBar, InputInterface $input, OutputInterface $output): void
    {
        // Clear the dev and prod cache instances to reset the system
        $progressBar->setMessage($this->translator->trans('mautic.core.update.clear.cache'));
        $progressBar->advance();

        $this->cacheHelper->nukeCache();
    }
}
