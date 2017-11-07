<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\InstallBundle\Configurator\Step;

use Mautic\CoreBundle\Configurator\Configurator;
use Mautic\CoreBundle\Configurator\Step\StepInterface;
use Mautic\InstallBundle\Configurator\Form\CheckStepType;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Check Step.
 */
class CheckStep implements StepInterface
{
    /**
     * Flag if the configuration file is writable.
     *
     * @var bool
     */
    private $configIsWritable;

    /**
     * Path to the kernel root.
     *
     * @var string
     */
    private $kernelRoot;

    /**
     * Absolute path to cache directory.
     *
     * @var string
     */
    public $cache_path = '%kernel.root_dir%/cache';

    /**
     * Absolute path to log directory.
     *
     * @var string
     */
    public $log_path = '%kernel.root_dir%/logs';

    /**
     * Set the domain URL for use in getting the absolute URL for cli/cronjob generated URLs.
     *
     * @var string
     */
    public $site_url;

    /**
     * Set the name of the source that installed Mautic.
     *
     * @var string
     */
    public $install_source = 'Mautic';

    /**
     * Constructor.
     *
     * @param Configurator $configurator Configurator service
     * @param string       $kernelRoot   Kernel root path
     * @param RequestStack $requestStack Request stack
     */
    public function __construct(Configurator $configurator, $kernelRoot, RequestStack $requestStack)
    {
        $request = $requestStack->getCurrentRequest();

        $this->configIsWritable = $configurator->isFileWritable();
        $this->kernelRoot       = $kernelRoot;
        $this->site_url         = $request->getSchemeAndHttpHost().$request->getBasePath();
    }

    /**
     * {@inheritdoc}
     */
    public function getFormType()
    {
        return new CheckStepType();
    }

    /**
     * {@inheritdoc}
     */
    public function checkRequirements()
    {
        $messages = [];

        if (version_compare(PHP_VERSION, '5.6.19', '<')) {
            $messages[] = 'mautic.install.php.version.not.supported';
        }

        if (!is_dir(dirname($this->kernelRoot).'/vendor/composer')) {
            $messages[] = 'mautic.install.composer.dependencies';
        }

        if (!$this->configIsWritable) {
            $messages[] = 'mautic.install.config.unwritable';
        }

        if (!is_writable($this->kernelRoot.'/cache')) {
            $messages[] = 'mautic.install.cache.unwritable';
        }

        if (!is_writable($this->kernelRoot.'/logs')) {
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

        if (get_magic_quotes_gpc()) {
            $messages[] = 'mautic.install.magic_quotes_enabled';
        }

        if (
            version_compare(PHP_VERSION, '5.6.0', '>=')
            &&
            version_compare(PHP_VERSION, '7', '<')
            &&
            ini_get('always_populate_raw_post_data') != -1
        ) {
            $messages[] = 'mautic.install.always_populate_raw_post_data_enabled';
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

        if (!extension_loaded('mcrypt')) {
            $messages[] = 'mautic.install.extension.mcrypt';
        }

        if (!extension_loaded('openssl')) {
            $messages[] = 'mautic.install.extension.openssl';
        }

        if (!function_exists('finfo_open')) {
            $messages[] = 'mautic.install.extension.fileinfo';
        }

        if (!function_exists('mb_strtolower')) {
            $messages[] = 'mautic.install.extension.mbstring';
        }

        if (extension_loaded('eaccelerator') && ini_get('eaccelerator.enable')) {
            $messages[] = 'mautic.install.extension.eaccelerator';
        }

        if (function_exists('apc_store') && ini_get('apc.enabled')) {
            if (!version_compare(phpversion('apc'), '3.1.13', '>=')) {
                $messages[] = 'mautic.install.apc.version';
            }
        }

        if (extension_loaded('suhosin')) {
            if (stripos(ini_get('suhosin.executor.include.whitelist'), 'phar')) {
                $messages[] = 'mautic.install.suhosin.whitelist';
            }
        }

        if (extension_loaded('xdebug')) {
            if (ini_get('xdebug.show_exception_trace')) {
                $messages[] = 'mautic.install.xdebug.exception.trace';
            }

            if (ini_get('xdebug.scream')) {
                $messages[] = 'mautic.install.xdebug.scream';
            }
        }

        $pcreVersion = defined('PCRE_VERSION') ? (float) PCRE_VERSION : null;

        if (is_null($pcreVersion)) {
            $messages[] = 'mautic.install.function.pcre';
        }

        return $messages;
    }

    /**
     * {@inheritdoc}
     */
    public function checkOptionalSettings()
    {
        $messages = [];

        $pcreVersion = defined('PCRE_VERSION') ? (float) PCRE_VERSION : null;

        if (!is_null($pcreVersion)) {
            if (version_compare($pcreVersion, '8.0', '<')) {
                $messages[] = 'mautic.install.pcre.version';
            }
        }

        if (extension_loaded('xdebug')) {
            $cfgValue = ini_get('xdebug.max_nesting_level');

            if (!call_user_func(create_function('$cfgValue', 'return $cfgValue > 100;'), $cfgValue)) {
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

        if (!defined('PHP_WINDOWS_VERSION_BUILD')) {
            if (!function_exists('posix_isatty')) {
                $messages[] = 'mautic.install.function.posix';
            }
        }

        $memoryLimit    = $this->toBytes(ini_get('memory_limit'));
        $suggestedLimit = 128 * 1024 * 1024;

        if ($memoryLimit < $suggestedLimit) {
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
            } catch (\Exception $exception) {
                $messages[] = 'mautic.install.intl.config';
            }
        }

        if (class_exists('\\Locale')) {
            if (defined('INTL_ICU_VERSION')) {
                $version = INTL_ICU_VERSION;
            } else {
                try {
                    $reflector = new \ReflectionExtension('intl');

                    ob_start();
                    $reflector->info();
                    $output = strip_tags(ob_get_clean());

                    preg_match('/^ICU version +(?:=> )?(.*)$/m', $output, $matches);
                    $version = $matches[1];
                } catch (\ReflectionException $exception) {
                    $messages[] = 'mautic.install.module.intl';

                    // Fake the version here for the next check
                    $version = '4.0';
                }
            }

            if (version_compare($version, '4.0', '<')) {
                $messages[] = 'mautic.install.intl.icu.version';
            }
        }

        return $messages;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return 'MauticInstallBundle:Install:check.html.php';
    }

    /**
     * {@inheritdoc}
     */
    public function update(StepInterface $data)
    {
        $parameters = [];

        foreach ($data as $key => $value) {
            // Exclude keys from the config
            if (!in_array($key, ['configIsWritable', 'kernelRoot'])) {
                $parameters[$key] = $value;
            }
        }

        return $parameters;
    }

    /**
     * Takes the memory limit string form php.ini and returns numeric value in bytes.
     *
     * @param string $val
     *
     * @return int
     */
    public function toBytes($val)
    {
        $val = trim($val);

        if ($val == -1) {
            return PHP_INT_MAX;
        }

        $last = strtolower($val[strlen($val) - 1]);
        switch ($last) {
            // The 'G' modifier is available since PHP 5.1.0
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }

        return $val;
    }
}
