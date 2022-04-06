<?php

namespace Mautic\CoreBundle\Update\Step;

use Mautic\CoreBundle\Helper\CacheHelper;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\TranslatorInterface;

final class DeleteCacheStep implements StepInterface
{
    /**
     * @var CacheHelper
     */
    private $cacheHelper;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(CacheHelper $cacheHelper, TranslatorInterface $translator)
    {
        $this->cacheHelper = $cacheHelper;
        $this->translator  = $translator;
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
