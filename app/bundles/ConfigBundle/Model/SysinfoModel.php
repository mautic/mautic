<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ConfigBundle\Model;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class SysinfoModel.
 */
class SysinfoModel
{
    protected $phpInfo;
    protected $folders;

    /**
     * @var PathsHelper
     */
    protected $pathsHelper;

    /**
     * @var CoreParametersHelper
     */
    protected $coreParametersHelper;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * SysinfoModel constructor.
     *
     * @param PathsHelper          $pathsHelper
     * @param CoreParametersHelper $coreParametersHelper
     * @param TranslatorInterface  $translator
     */
    public function __construct(PathsHelper $pathsHelper, CoreParametersHelper $coreParametersHelper, TranslatorInterface $translator)
    {
        $this->pathsHelper          = $pathsHelper;
        $this->coreParametersHelper = $coreParametersHelper;
        $this->translator           = $translator;
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

        if (function_exists('phpinfo')) {
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
            //ensure TZ is set back to default
            date_default_timezone_set($currentTz);
        } elseif (function_exists('phpversion')) {
            $this->phpInfo = $this->translator->trans('mautic.sysinfo.phpinfo.phpversion', ['%phpversion%' => phpversion()]);
        } else {
            $this->phpInfo = $this->translator->trans('mautic.sysinfo.phpinfo.missing');
        }

        return $this->phpInfo;
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
            $this->pathsHelper->getSystemPath('local_config'),
            $this->coreParametersHelper->getParameter('cache_path'),
            $this->coreParametersHelper->getParameter('log_path'),
            $this->coreParametersHelper->getParameter('upload_dir'),
            $this->pathsHelper->getSystemPath('images', true),
            $this->pathsHelper->getSystemPath('translations', true),
        ];

        // Show the spool folder only if the email queue is configured
        if ($this->coreParametersHelper->getParameter('mailer_spool_type') == 'file') {
            $importantFolders[] = $this->coreParametersHelper->getParameter('mailer_spool_path');
        }

        foreach ($importantFolders as $folder) {
            $folderPath = realpath($folder);
            $folderKey  = ($folderPath) ? $folderPath : $folder;
            $isWritable = ($folderPath) ? is_writable($folderPath) : false;

            $this->folders[$folderKey] = $isWritable;
        }

        return $this->folders;
    }

    /**
     * Method to tail (a few last rows) of a file.
     *
     * @param int $lines
     *
     * @return string
     */
    public function getLogTail($lines = 10)
    {
        $log = $this->coreParametersHelper->getParameter('log_path').'/mautic_'.MAUTIC_ENV.'-'.date('Y-m-d').'.php';

        if (!file_exists($log)) {
            return null;
        }

        return $this->tail($log, $lines);
    }

    /**
     * Method to tail (a few last rows) of a file.
     *
     * @param     $filename
     * @param int $lines
     * @param int $buffer
     *
     * @return string
     */
    public function tail($filename, $lines = 10, $buffer = 4096)
    {
        $f      = fopen($filename, 'rb');
        $output = '';

        fseek($f, -1, SEEK_END);

        if (fread($f, 1) != "\n") {
            $lines -= 1;
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
