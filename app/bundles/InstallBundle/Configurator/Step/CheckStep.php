<?php

namespace Mautic\InstallBundle\Configurator\Step;

use Mautic\CoreBundle\Configurator\Configurator;
use Mautic\CoreBundle\Configurator\Step\StepInterface;
use Mautic\CoreBundle\Helper\FileHelper;
use Mautic\CoreBundle\Security\Cryptography\Cipher\Symmetric\OpenSSLCipher;
use Mautic\InstallBundle\Configurator\Form\CheckStepType;
use Symfony\Component\HttpFoundation\RequestStack;

class CheckStep implements StepInterface
{
    /**
     * Flag if the configuration file is writable.
     */
    private bool $configIsWritable;

    /**
     * Absolute path to cache directory.
     * Required in step.
     *
     * @var string
     */
    public $cache_path = '%kernel.project_dir%/var/cache';

    /**
     * Absolute path to log directory.
     * Required in step.
     *
     * @var string
     */
    public $log_path = '%kernel.project_dir%/var/logs';

    /**
     * Set the domain URL for use in getting the absolute URL for cli/cronjob generated URLs.
     *
     * @var string
     */
    public $site_url = '';

    /**
     * Recommended minimum memory limit for Mautic.
     *
     * @var string
     */
    public const RECOMMENDED_MEMORY_LIMIT = '512M';

    /**
     * @param Configurator $configurator Configurator service
     * @param string       $projectDir   Kernel root path
     * @param RequestStack $requestStack Request stack
     */
    public function __construct(
        Configurator $configurator,
        private string $projectDir,
        RequestStack $requestStack,
        private OpenSSLCipher $openSSLCipher
    ) {
        $request = $requestStack->getCurrentRequest();

        $this->configIsWritable = $configurator->isFileWritable();
        if (!empty($request)) {
            $this->site_url     = $request->getSchemeAndHttpHost().$request->getBasePath();
        }
    }

    public function getFormType(): string
    {
        return CheckStepType::class;
    }

    public function checkRequirements(): array
    {
        $messages = [];

        if (!is_dir($this->projectDir.'/vendor/composer')) {
            $messages[] = 'mautic.install.composer.dependencies';
        }

        if (!$this->configIsWritable) {
            $messages[] = 'mautic.install.config.unwritable';
        }

        if (!is_writable(str_replace('%kernel.project_dir%', $this->projectDir, $this->cache_path))) {
            $messages[] = 'mautic.install.cache.unwritable';
        }

        if (!is_writable(str_replace('%kernel.project_dir%', $this->projectDir, $this->log_path))) {
            $messages[] = 'mautic.install.logs.unwritable';
        }

        $timezones = [];

        foreach (\DateTimeZone::listAbbreviations() as $abbreviations) {
            foreach ($abbreviations as $abbreviation) {
                $timezones[$abbreviation['timezone_id']] = true;
            }
        }

        if (!isset($timezones[date_default_timezone_get()])) {
            $messages[] = 'mautic.install.timezone.not.supported';
        }

        if (!function_exists('json_encode')) {
            $messages[] = 'mautic.install.function.jsonencode';
        }

        if (!function_exists('session_start')) {
            $messages[] = 'mautic.install.function.sessionstart';
        }

        if (!function_exists('ctype_alpha')) {
            $messages[] = 'mautic.install.function.ctypealpha';
        }

        if (!function_exists('token_get_all')) {
            $messages[] = 'mautic.install.function.tokengetall';
        }

        if (!function_exists('simplexml_import_dom')) {
            $messages[] = 'mautic.install.function.simplexml';
        }

        if (false === $this->openSSLCipher->isSupported()) {
            $messages[] = 'mautic.install.extension.openssl';
        }

        if (!function_exists('curl_init')) {
            $messages[] = 'mautic.install.extension.curl';
        }

        if (!function_exists('finfo_open')) {
            $messages[] = 'mautic.install.extension.fileinfo';
        }

        if (!function_exists('mb_strtolower')) {
            $messages[] = 'mautic.install.extension.mbstring';
        }

        if (extension_loaded('xdebug')) {
            if (ini_get('xdebug.show_exception_trace')) {
                $messages[] = 'mautic.install.xdebug.exception.trace';
            }

            if (ini_get('xdebug.scream')) {
                $messages[] = 'mautic.install.xdebug.scream';
            }
        }

        return $messages;
    }

    public function checkOptionalSettings(): array
    {
        $messages = [];

        if (extension_loaded('xdebug')) {
            $cfgValue = ini_get('xdebug.max_nesting_level');

            if ($cfgValue <= 100) {
                $messages[] = 'mautic.install.xdebug.nesting';
            }
        }

        if (!extension_loaded('zip')) {
            $messages[] = 'mautic.install.extension.zip';
        }

        // We set a default timezone in the app bootstrap, but advise the user if their PHP config is missing it
        if (!ini_get('date.timezone')) {
            $messages[] = 'mautic.install.date.timezone.not.set';
        }

        if (!class_exists('\\DomDocument')) {
            $messages[] = 'mautic.install.module.phpxml';
        }

        if (!function_exists('iconv')) {
            $messages[] = 'mautic.install.function.iconv';
        }

        if (!function_exists('utf8_decode')) {
            $messages[] = 'mautic.install.function.xml';
        }

        if (!function_exists('imap_open')) {
            $messages[] = 'mautic.install.extension.imap';
        }

        if (!$this->site_url || !str_starts_with($this->site_url, 'https')) {
            $messages[] = 'mautic.install.ssl.certificate';
        }

        if (!defined('PHP_WINDOWS_VERSION_BUILD')) {
            if (!function_exists('posix_isatty')) {
                $messages[] = 'mautic.install.function.posix.enable';
            }
        }

        $memoryLimit    = FileHelper::convertPHPSizeToBytes(ini_get('memory_limit'));
        $suggestedLimit = FileHelper::convertPHPSizeToBytes(self::RECOMMENDED_MEMORY_LIMIT);
        if ($memoryLimit > -1 && $memoryLimit < $suggestedLimit) {
            $messages[] = 'mautic.install.memory.limit';
        }

        if (!class_exists('\\Locale')) {
            $messages[] = 'mautic.install.module.intl';
        }

        if (class_exists('\\Collator')) {
            try {
                if (is_null(new \Collator('fr_FR'))) {
                    $messages[] = 'mautic.install.intl.config';
                }
            } catch (\Exception) {
                $messages[] = 'mautic.install.intl.config';
            }
        }

        if (-1 !== (int) ini_get('zend.assertions')) {
            $messages[] = 'mautic.install.zend_assertions';
        }

        return $messages;
    }

    public function getTemplate(): string
    {
        return '@MauticInstall/Install/check.html.twig';
    }

    /**
     * @return mixed[]
     */
    public function update(StepInterface $data): array
    {
        $parameters = [];

        foreach ($data as $key => $value) {
            // Exclude keys from the config
            if (!in_array($key, ['configIsWritable', 'projectDir'])) {
                $parameters[$key] = $value;
            }
        }

        return $parameters;
    }
}
