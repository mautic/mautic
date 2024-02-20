<?php

namespace Mautic\ConfigBundle\Model;

use Doctrine\DBAL\Connection;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Loader\ParameterLoader;
use Mautic\InstallBundle\Configurator\Step\CheckStep;
use Mautic\InstallBundle\Install\InstallService;
use Symfony\Contracts\Translation\TranslatorInterface;

class SysinfoModel
{
    /**
     * @var string|null
     */
    protected $phpInfo;

    /**
     * @var array<string,bool>|null
     */
    protected $folders;

    public function __construct(
        protected PathsHelper $pathsHelper,
        protected CoreParametersHelper $coreParametersHelper,
        private TranslatorInterface $translator,
        protected Connection $connection,
        private InstallService $installService,
        private CheckStep $checkStep
    ) {
    }

    /**
     * Method to get the PHP info.
     *
     * @return string
     */
    public function getPhpInfo()
    {
        if (!is_null($this->phpInfo)) {
            return $this->phpInfo;
        }

        if (function_exists('phpinfo') && 'cli' !== php_sapi_name()) {
            ob_start();
            $currentTz = date_default_timezone_get();
            date_default_timezone_set('UTC');
            phpinfo(INFO_GENERAL | INFO_CONFIGURATION | INFO_MODULES);
            $phpInfo = ob_get_contents();
            ob_end_clean();
            preg_match_all('#<body[^>]*>(.*)</body>#siU', $phpInfo, $output);
            $output        = preg_replace('#<table[^>]*>#', '<table class="table table-striped">', $output[1][0]);
            $output        = preg_replace('#(\w),(\w)#', '\1, \2', $output);
            $output        = preg_replace('#<hr />#', '', $output);
            $output        = str_replace('<div class="center">', '', $output);
            $output        = preg_replace('#<tr class="h">(.*)<\/tr>#', '<thead><tr class="h">$1</tr></thead><tbody>', $output);
            $output        = str_replace('</table>', '</tbody></table>', $output);
            $output        = str_replace('</div>', '', $output);
            $this->phpInfo = $output;
            // ensure TZ is set back to default
            date_default_timezone_set($currentTz);
        } elseif (function_exists('phpversion')) {
            $this->phpInfo = $this->translator->trans('mautic.sysinfo.phpinfo.phpversion', ['%phpversion%' => phpversion()]);
        } else {
            $this->phpInfo = $this->translator->trans('mautic.sysinfo.phpinfo.missing');
        }

        return $this->phpInfo;
    }

    /**
     * @return string[]
     */
    public function getRecommendations(): array
    {
        return $this->installService->checkOptionalSettings($this->checkStep);
    }

    /**
     * @return string[]
     */
    public function getRequirements(): array
    {
        return $this->installService->checkRequirements($this->checkStep);
    }

    /**
     * Method to get important folders with a writable flag.
     *
     * @return array
     */
    public function getFolders()
    {
        if (!is_null($this->folders)) {
            return $this->folders;
        }

        $importantFolders = [
            ParameterLoader::getLocalConfigFile($this->pathsHelper->getSystemPath('root').'/app'),
            $this->coreParametersHelper->get('cache_path'),
            $this->coreParametersHelper->get('log_path'),
            $this->coreParametersHelper->get('upload_dir'),
            $this->pathsHelper->getSystemPath('images', true),
            $this->pathsHelper->getSystemPath('translations', true),
        ];

        foreach ($importantFolders as $folder) {
            $folderPath = realpath($folder);
            $folderKey  = $folderPath ?: $folder;
            $isWritable = $folderPath && is_writable($folderPath);

            $this->folders[$folderKey] = $isWritable;
        }

        return $this->folders;
    }

    /**
     * Method to tail (a few last rows) of a file.
     *
     * @param int $lines
     */
    public function getLogTail($lines = 10): ?string
    {
        $log = $this->coreParametersHelper->get('log_path').'/mautic_'.MAUTIC_ENV.'-'.date('Y-m-d').'.php';

        if (!file_exists($log)) {
            return null;
        }

        return $this->tail($log, $lines);
    }

    public function getDbInfo(): array
    {
        return [
            'version'  => $this->connection->executeQuery('SELECT VERSION()')->fetchOne(),
            'driver'   => $this->connection->getParams()['driver'],
            'platform' => $this->connection->getDatabasePlatform()::class,
        ];
    }

    /**
     * Method to tail (a few last rows) of a file.
     *
     * @param int $lines
     * @param int $buffer
     */
    public function tail($filename, $lines = 10, $buffer = 4096): string
    {
        $f      = fopen($filename, 'rb');
        $output = '';

        fseek($f, -1, SEEK_END);

        if ("\n" != fread($f, 1)) {
            --$lines;
        }

        while (ftell($f) > 0 && $lines >= 0) {
            $seek = min(ftell($f), $buffer);
            fseek($f, -$seek, SEEK_CUR);
            $output = ($chunk = fread($f, $seek)).$output;
            fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);
            $lines -= substr_count($chunk, "\n");
        }

        while ($lines++ < 0) {
            $output = substr($output, strpos($output, "\n") + 1);
        }

        fclose($f);

        return $output;
    }
}
