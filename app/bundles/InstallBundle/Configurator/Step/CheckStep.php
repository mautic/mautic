<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\InstallBundle\Configurator\Step;

use Mautic\InstallBundle\Configurator\Form\CheckStepType;

/**
 * Check Step.
 */
class CheckStep implements StepInterface
{
    private $configIsWritable;
    private $kernelRoot;

    /**
     * Constructor
     *
     * @param array   $parameters       Existing parameters in local configuration
     * @param boolean $configIsWritable Flag if the configuration file is writable
     * @param string  $kernelRoot       Kernel root path
     */
    public function __construct(array $parameters, $configIsWritable, $kernelRoot)
    {
        $this->configIsWritable = $configIsWritable;
        $this->kernelRoot       = $kernelRoot;
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
        $messages = array();

        if (version_compare(PHP_VERSION, '5.3.7', '<')) {
            $messages[] = 'mautic.install.minimum.php.version';
        }

        if (version_compare(PHP_VERSION, '5.3.16', '==')) {
            $messages[] = 'mautic.install.buggy.php.version';
        }

        if (!is_dir(dirname($this->kernelRoot) . '/vendor/composer')) {
            $messages[] = 'mautic.install.composer.dependencies';
        }

        if (!$this->configIsWritable) {
            $messages[] = 'mautic.install.config.unwritable';
        }

        if (!is_writable($this->kernelRoot . '/cache')) {
            $messages[] = 'mautic.install.cache.unwritable';
        }

        if (!is_writable($this->kernelRoot . '/logs')) {
            $messages[] = 'mautic.install.logs.unwritable';
        }

        if (!ini_get('date.timezone')) {
            $messages[] = 'mautic.install.date.timezone';
        }

        if (version_compare(PHP_VERSION, '5.3.7', '>=')) {
            $timezones = array();
            foreach (\DateTimeZone::listAbbreviations() as $abbreviations) {
                foreach ($abbreviations as $abbreviation) {
                    $timezones[$abbreviation['timezone_id']] = true;
                }
            }

            if (!isset($timezones[date_default_timezone_get()])) {
                $messages[] = 'mautic.install.timezone.not.supported';
            }
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

        if (function_exists('apc_store') && ini_get('apc.enabled')) {
            $minimumAPCversion = version_compare(PHP_VERSION, '5.4.0', '>=') ? '3.1.13' : '3.0.17';

            if (!version_compare(phpversion('apc'), $minimumAPCversion, '>=')) {
                $messages[] = 'mautic.install.apc.version';
            }
        }

        $unicodeIni = version_compare(PHP_VERSION, '5.4.0', '>=') ? 'zend.detect_unicode' : 'detect_unicode';

        // Commented for now, no idea what this check was actually supposed to be doing in the distro bundle
        /*if (ini_get($unicodeIni)) {
            $messages[] = 'mautic.install.detect.unicode';
        }*/

        if (extension_loaded('suhosin')) {
            $cfgValue = ini_get('suhosin.executor.include.whitelist');

            if (!call_user_func(create_function('$cfgValue', 'return false !== stripos($cfgValue, "phar");'), $cfgValue)) {
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
        $messages = array();

        if (version_compare(PHP_VERSION, '5.3.8', '<')) {
            $messages[] = 'mautic.install.php.version.annotations';
        }

        if (version_compare(PHP_VERSION, '5.4.0', '=')) {
            $messages[] = 'mautic.install.php.version.dump';
        }

        if ((PHP_MINOR_VERSION == 3 && PHP_RELEASE_VERSION < 18) || (PHP_MINOR_VERSION == 4 && PHP_RELEASE_VERSION < 8)) {
            $messages[] = 'mautic.install.php.version.pretty.error';
        }

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

        if (!class_exists('\\DomDocument')) {
            $messages[] = 'mautic.install.module.phpxml';
        }

        if (!function_exists('mb_strlen')) {
            $messages[] = 'mautic.install.function.mbstring';
        }

        if (!function_exists('iconv')) {
            $messages[] = 'mautic.install.function.iconv';
        }

        if (!function_exists('utf8_decode')) {
            $messages[] = 'mautic.install.function.xml';
        }

        if (!defined('PHP_WINDOWS_VERSION_BUILD')) {
            if (!function_exists('posix_isatty')) {
                $messages[] = 'mautic.install.function.posix';
            }
        }

        if (!class_exists('\\Locale')) {
            $messages[] = 'mautic.install.module.intl';
        }

        if (class_exists('\\Collator')) {
            if (is_null(new \Collator('fr_FR'))) {
                $messages[] = 'mautic.install.intl.config';
            }
        }

        if (class_exists('\\Locale')) {
            if (defined('INTL_ICU_VERSION')) {
                $version = INTL_ICU_VERSION;
            } else {
                $reflector = new \ReflectionExtension('intl');

                ob_start();
                $reflector->info();
                $output = strip_tags(ob_get_clean());

                preg_match('/^ICU version +(?:=> )?(.*)$/m', $output, $matches);
                $version = $matches[1];
            }

            if (version_compare($version, '4.0', '<')) {
                $messages[] = 'mautic.install.intl.icu.version';
            }
        }

        $accelerator =
            (extension_loaded('eaccelerator') && ini_get('eaccelerator.enable'))
            ||
            (extension_loaded('apc') && ini_get('apc.enabled'))
            ||
            (extension_loaded('Zend OPcache') && ini_get('opcache.enable'))
            ||
            (extension_loaded('xcache') && ini_get('xcache.cacher'))
            ||
            (extension_loaded('wincache') && ini_get('wincache.ocenabled'))
        ;

        if (!$accelerator) {
            $messages[] = 'mautic.install.accelerator';
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
        return array();
    }
}
