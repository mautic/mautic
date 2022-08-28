<?php

namespace Mautic\CoreBundle\Update\Step;

use Mautic\CoreBundle\Helper\AppVersion;
use Mautic\CoreBundle\Helper\PathsHelper;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\TranslatorInterface;

final class FinalizeUpdateStep implements StepInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var PathsHelper
     */
    private $pathsHelper;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var AppVersion
     */
    private $appVersion;

    public function __construct(TranslatorInterface $translator, PathsHelper $pathsHelper, Session $session, AppVersion $appVersion)
    {
        $this->translator  = $translator;
        $this->pathsHelper = $pathsHelper;
        $this->session     = $session;
        $this->appVersion  = $appVersion;
    }

    public function getOrder(): int
    {
        return 60;
    }

    public function shouldExecuteInFinalStage(): bool
    {
        return true;
    }

    public function execute(ProgressBar $progressBar, InputInterface $input, OutputInterface $output): void
    {
        $progressBar->setMessage($this->translator->trans('mautic.core.update.step.wrapping_up'));
        $progressBar->advance();

        // Clear the cached update data and the download package now that we've updated
        @unlink($this->pathsHelper->getRootPath().'/upgrade.php');
        @unlink($this->pathsHelper->getCachePath().'/lastUpdateCheck.txt');

        // Update successful
        $progressBar->setMessage(
            $this->translator->trans('mautic.core.update.update_successful', ['%version%' => $this->appVersion->getVersion()])."\n\n"
        );
        $progressBar->finish();

        // Check for a post install message from migrations
        if ($postMessage = $this->session->get('post_upgrade_message')) {
            $postMessage = strip_tags($postMessage);
            $this->session->remove('post_upgrade_message');
            $output->writeln("\n\n<info>$postMessage</info>");
        }
    }
}
