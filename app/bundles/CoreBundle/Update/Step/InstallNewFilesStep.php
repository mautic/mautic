<?php

namespace Mautic\CoreBundle\Update\Step;

use Mautic\CoreBundle\Exception\UpdateFailedException;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\UpdateHelper;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class InstallNewFilesStep implements StepInterface
{
    private ?ProgressBar $progressBar = null;

    private ?InputInterface $input = null;

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly UpdateHelper $updateHelper,
        private readonly PathsHelper $pathsHelper,
    ) {
    }

    public function getOrder(): int
    {
        return 10;
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
        $this->progressBar = $progressBar;
        $this->input       = $input;

        $zipFile = $this->getZipPackage();

        $progressBar->setMessage($this->translator->trans('mautic.core.command.update.step.validate_update_package'));
        $progressBar->advance();

        $zipper = new \ZipArchive();
        $opened = $zipper->open($zipFile);

        $this->validateArchive($opened);

        // Extract the archive file now in place
        $progressBar->setMessage($this->translator->trans('mautic.core.update.step.extracting.package'));
        $progressBar->advance();

        if (!$zipper->extractTo($this->pathsHelper->getRootPath())) {
            throw new UpdateFailedException($this->translator->trans('mautic.core.update.error', ['%error%' => $this->translator->trans('mautic.core.update.error_extracting_package')]));
        }

        $zipper->close();
        @unlink($zipFile);
    }

    /**
     * @throws UpdateFailedException
     */
    private function getZipPackage(): string
    {
        if ($package = $this->input->getOption('update-package')) {
            if (!file_exists($package)) {
                throw new UpdateFailedException($this->translator->trans('mautic.core.update.archive_no_such_file'));
            }

            $this->progressBar->setMessage($this->translator->trans('mautic.core.command.update.step.loading_package').'                  ');
            $this->progressBar->advance();

            return $package;
        }

        $this->progressBar->setMessage($this->translator->trans('mautic.core.command.update.step.loading_update_information').'                  ');
        $this->progressBar->advance();

        $update = $this->updateHelper->fetchData();

        if (!isset($update['package'])) {
            throw new UpdateFailedException($this->translator->trans('mautic.core.update.no_cache_data'));
        }

        $this->progressBar->setMessage($this->translator->trans('mautic.core.command.update.step.download_update_package').'                  ');
        $this->progressBar->advance();

        // Fetch the update package
        $package = $this->updateHelper->fetchPackage($update['package']);

        if (isset($package['error']) && $package['error']) {
            throw new UpdateFailedException($this->translator->trans($package['message']));
        }

        return $this->pathsHelper->getCachePath().'/'.basename($update['package']);
    }

    /**
     * @param bool|string $opened
     *
     * @throws UpdateFailedException
     */
    private function validateArchive(int|bool $opened): void
    {
        if (true === $opened) {
            return;
        }

        // Get the exact error
        $error = match ($opened) {
            \ZipArchive::ER_EXISTS => 'mautic.core.update.archive_file_exists',
            \ZipArchive::ER_INCONS, \ZipArchive::ER_INVAL, \ZipArchive::ER_MEMORY => 'mautic.core.update.archive_zip_corrupt',
            \ZipArchive::ER_NOENT => 'mautic.core.update.archive_no_such_file',
            \ZipArchive::ER_NOZIP => 'mautic.core.update.archive_not_valid_zip',
            default => 'mautic.core.update.archive_could_not_open',
        };

        throw new UpdateFailedException($this->translator->trans('mautic.core.update.error', ['%error%' => $this->translator->trans($error)]));
    }
}
