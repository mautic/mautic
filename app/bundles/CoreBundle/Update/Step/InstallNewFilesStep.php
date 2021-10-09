<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://www.mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Update\Step;

use Mautic\CoreBundle\Exception\UpdateFailedException;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\UpdateHelper;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\TranslatorInterface;

final class InstallNewFilesStep implements StepInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var UpdateHelper
     */
    private $updateHelper;

    /**
     * @var PathsHelper
     */
    private $pathsHelper;

    /**
     * @var ProgressBar
     */
    private $progressBar;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * InstallNewFilesStep constructor.
     */
    public function __construct(TranslatorInterface $translator, UpdateHelper $updateHelper, PathsHelper $pathsHelper)
    {
        $this->translator   = $translator;
        $this->updateHelper = $updateHelper;
        $this->pathsHelper  = $pathsHelper;
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

        if (isset($package['error']) && true === $package['error']) {
            throw new UpdateFailedException($this->translator->trans($package['message']));
        }

        return $this->pathsHelper->getCachePath().'/'.basename($update['package']);
    }

    /**
     * @param bool|string $opened
     *
     * @throws UpdateFailedException
     */
    private function validateArchive($opened): void
    {
        if (true === $opened) {
            return;
        }

        // Get the exact error
        switch ($opened) {
            case \ZipArchive::ER_EXISTS:
                $error = 'mautic.core.update.archive_file_exists';
                break;
            case \ZipArchive::ER_INCONS:
            case \ZipArchive::ER_INVAL:
            case \ZipArchive::ER_MEMORY:
                $error = 'mautic.core.update.archive_zip_corrupt';
                break;
            case \ZipArchive::ER_NOENT:
                $error = 'mautic.core.update.archive_no_such_file';
                break;
            case \ZipArchive::ER_NOZIP:
                $error = 'mautic.core.update.archive_not_valid_zip';
                break;
            case \ZipArchive::ER_READ:
            case \ZipArchive::ER_SEEK:
            case \ZipArchive::ER_OPEN:
            default:
                $error = 'mautic.core.update.archive_could_not_open';
                break;
        }

        throw new UpdateFailedException($this->translator->trans('mautic.core.update.error', ['%error%' => $this->translator->trans($error)]));
    }
}
