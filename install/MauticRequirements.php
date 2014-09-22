<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 *
 * Based on Sensio\DistributionBundle
 */

/**
 * Represents a single PHP requirement, e.g. an installed extension.
 * It can be a mandatory requirement or an optional recommendation.
 * There is a special subclass, named PhpIniRequirement, to check a php.ini configuration.
 *
 * @author Tobias Schultze <http://tobion.de>
 */
class Requirement
{
    private $fulfilled;
    private $testMessage;
    private $helpText;
    private $helpHtml;
    private $optional;

    /**
     * Constructor that initializes the requirement.
     *
     * @param boolean     $fulfilled   Whether the requirement is fulfilled
     * @param string      $testMessage The message for testing the requirement
     * @param string      $helpHtml    The help text formatted in HTML for resolving the problem
     * @param string|null $helpText    The help text (when null, it will be inferred from $helpHtml, i.e. stripped from HTML tags)
     * @param boolean     $optional    Whether this is only an optional recommendation not a mandatory requirement
     */
    public function __construct($fulfilled, $testMessage, $helpHtml, $helpText = null, $optional = false)
    {
        $this->fulfilled = (boolean) $fulfilled;
        $this->testMessage = (string) $testMessage;
        $this->helpHtml = (string) $helpHtml;
        $this->helpText = null === $helpText ? strip_tags($this->helpHtml) : (string) $helpText;
        $this->optional = (boolean) $optional;
    }

    /**
     * Returns whether the requirement is fulfilled.
     *
     * @return boolean true if fulfilled, otherwise false
     */
    public function isFulfilled()
    {
        return $this->fulfilled;
    }

    /**
     * Returns the message for testing the requirement.
     *
     * @return string The test message
     */
    public function getTestMessage()
    {
        return $this->testMessage;
    }

    /**
     * Returns the help text for resolving the problem
     *
     * @return string The help text
     */
    public function getHelpText()
    {
        return $this->helpText;
    }

    /**
     * Returns the help text formatted in HTML.
     *
     * @return string The HTML help
     */
    public function getHelpHtml()
    {
        return $this->helpHtml;
    }

    /**
     * Returns whether this is only an optional recommendation and not a mandatory requirement.
     *
     * @return boolean true if optional, false if mandatory
     */
    public function isOptional()
    {
        return $this->optional;
    }
}

/**
 * Represents a PHP requirement in form of a php.ini configuration.
 *
 * @author Tobias Schultze <http://tobion.de>
 */
class PhpIniRequirement extends Requirement
{
    /**
     * Constructor that initializes the requirement.
     *
     * @param string           $cfgName           The configuration name used for ini_get()
     * @param boolean|callback $evaluation        Either a boolean indicating whether the configuration should evaluate to true or false,
     *                                            or a callback function receiving the configuration value as parameter to determine the fulfillment of the requirement
     * @param boolean          $approveCfgAbsence If true the Requirement will be fulfilled even if the configuration option does not exist, i.e. ini_get() returns false.
     *                                            This is helpful for abandoned configs in later PHP versions or configs of an optional extension, like Suhosin.
     *                                            Example: You require a config to be true but PHP later removes this config and defaults it to true internally.
     * @param string|null      $testMessage       The message for testing the requirement (when null and $evaluation is a boolean a default message is derived)
     * @param string|null      $helpHtml          The help text formatted in HTML for resolving the problem (when null and $evaluation is a boolean a default help is derived)
     * @param string|null      $helpText          The help text (when null, it will be inferred from $helpHtml, i.e. stripped from HTML tags)
     * @param boolean          $optional          Whether this is only an optional recommendation not a mandatory requirement
     *
     * @throws InvalidArgumentException
     */
    public function __construct($cfgName, $evaluation, $approveCfgAbsence = false, $testMessage = null, $helpHtml = null, $helpText = null, $optional = false)
    {
        $cfgValue = ini_get($cfgName);

        if (is_callable($evaluation)) {
            if (null === $testMessage || null === $helpHtml) {
                throw new InvalidArgumentException('You must provide the parameters testMessage and helpHtml for a callback evaluation.');
            }

            $fulfilled = call_user_func($evaluation, $cfgValue);
        } else {
            if (null === $testMessage) {
                $testMessage = sprintf('%s %s be %s in php.ini',
                    $cfgName,
                    $optional ? 'should' : 'must',
                    $evaluation ? 'enabled' : 'disabled'
                );
            }

            if (null === $helpHtml) {
                $helpHtml = sprintf('Set <strong>%s</strong> to <strong>%s</strong> in php.ini<a href="#phpini">*</a>.',
                    $cfgName,
                    $evaluation ? 'on' : 'off'
                );
            }

            $fulfilled = $evaluation == $cfgValue;
        }

        parent::__construct($fulfilled || ($approveCfgAbsence && false === $cfgValue), $testMessage, $helpHtml, $helpText, $optional);
    }
}

/**
 * A RequirementCollection represents a set of Requirement instances.
 *
 * @author Tobias Schultze <http://tobion.de>
 */
class RequirementCollection implements IteratorAggregate
{
    private $requirements = array();

    /**
     * Gets the current RequirementCollection as an Iterator.
     *
     * @return Traversable A Traversable interface
     */
    public function getIterator()
    {
        return new ArrayIterator($this->requirements);
    }

    /**
     * Adds a Requirement.
     *
     * @param Requirement $requirement A Requirement instance
     */
    public function add(Requirement $requirement)
    {
        $this->requirements[] = $requirement;
    }

    /**
     * Adds a mandatory requirement.
     *
     * @param boolean     $fulfilled   Whether the requirement is fulfilled
     * @param string      $testMessage The message for testing the requirement
     * @param string      $helpHtml    The help text formatted in HTML for resolving the problem
     * @param string|null $helpText    The help text (when null, it will be inferred from $helpHtml, i.e. stripped from HTML tags)
     */
    public function addRequirement($fulfilled, $testMessage, $helpHtml, $helpText = null)
    {
        $this->add(new Requirement($fulfilled, $testMessage, $helpHtml, $helpText, false));
    }

    /**
     * Adds an optional recommendation.
     *
     * @param boolean     $fulfilled   Whether the recommendation is fulfilled
     * @param string      $testMessage The message for testing the recommendation
     * @param string      $helpHtml    The help text formatted in HTML for resolving the problem
     * @param string|null $helpText    The help text (when null, it will be inferred from $helpHtml, i.e. stripped from HTML tags)
     */
    public function addRecommendation($fulfilled, $testMessage, $helpHtml, $helpText = null)
    {
        $this->add(new Requirement($fulfilled, $testMessage, $helpHtml, $helpText, true));
    }

    /**
     * Adds a mandatory requirement in form of a php.ini configuration.
     *
     * @param string           $cfgName    The configuration name used for ini_get()
     * @param boolean|callback $evaluation Either a boolean indicating whether the configuration should evaluate to true or false,
                                                    or a callback function receiving the configuration value as parameter to determine the fulfillment of the requirement
     * @param boolean $approveCfgAbsence If true the Requirement will be fulfilled even if the configuration option does not exist, i.e. ini_get() returns false.
                                                    This is helpful for abandoned configs in later PHP versions or configs of an optional extension, like Suhosin.
                                                    Example: You require a config to be true but PHP later removes this config and defaults it to true internally.
     * @param string      $testMessage The message for testing the requirement (when null and $evaluation is a boolean a default message is derived)
     * @param string      $helpHtml    The help text formatted in HTML for resolving the problem (when null and $evaluation is a boolean a default help is derived)
     * @param string|null $helpText    The help text (when null, it will be inferred from $helpHtml, i.e. stripped from HTML tags)
     */
    public function addPhpIniRequirement($cfgName, $evaluation, $approveCfgAbsence = false, $testMessage = null, $helpHtml = null, $helpText = null)
    {
        $this->add(new PhpIniRequirement($cfgName, $evaluation, $approveCfgAbsence, $testMessage, $helpHtml, $helpText, false));
    }

    /**
     * Adds an optional recommendation in form of a php.ini configuration.
     *
     * @param string           $cfgName    The configuration name used for ini_get()
     * @param boolean|callback $evaluation Either a boolean indicating whether the configuration should evaluate to true or false,
                                                    or a callback function receiving the configuration value as parameter to determine the fulfillment of the requirement
     * @param boolean $approveCfgAbsence If true the Requirement will be fulfilled even if the configuration option does not exist, i.e. ini_get() returns false.
                                                    This is helpful for abandoned configs in later PHP versions or configs of an optional extension, like Suhosin.
                                                    Example: You require a config to be true but PHP later removes this config and defaults it to true internally.
     * @param string      $testMessage The message for testing the requirement (when null and $evaluation is a boolean a default message is derived)
     * @param string      $helpHtml    The help text formatted in HTML for resolving the problem (when null and $evaluation is a boolean a default help is derived)
     * @param string|null $helpText    The help text (when null, it will be inferred from $helpHtml, i.e. stripped from HTML tags)
     */
    public function addPhpIniRecommendation($cfgName, $evaluation, $approveCfgAbsence = false, $testMessage = null, $helpHtml = null, $helpText = null)
    {
        $this->add(new PhpIniRequirement($cfgName, $evaluation, $approveCfgAbsence, $testMessage, $helpHtml, $helpText, true));
    }

    /**
     * Adds a requirement collection to the current set of requirements.
     *
     * @param RequirementCollection $collection A RequirementCollection instance
     */
    public function addCollection(RequirementCollection $collection)
    {
        $this->requirements = array_merge($this->requirements, $collection->all());
    }

    /**
     * Returns both requirements and recommendations.
     *
     * @return array Array of Requirement instances
     */
    public function all()
    {
        return $this->requirements;
    }

    /**
     * Returns all mandatory requirements.
     *
     * @return array Array of Requirement instances
     */
    public function getRequirements()
    {
        $array = array();
        foreach ($this->requirements as $req) {
            if (!$req->isOptional()) {
                $array[] = $req;
            }
        }

        return $array;
    }

    /**
     * Returns the mandatory requirements that were not met.
     *
     * @return array Array of Requirement instances
     */
    public function getFailedRequirements()
    {
        $array = array();
        foreach ($this->requirements as $req) {
            if (!$req->isFulfilled() && !$req->isOptional()) {
                $array[] = $req;
            }
        }

        return $array;
    }

    /**
     * Returns all optional recommendations.
     *
     * @return array Array of Requirement instances
     */
    public function getRecommendations()
    {
        $array = array();
        foreach ($this->requirements as $req) {
            if ($req->isOptional()) {
                $array[] = $req;
            }
        }

        return $array;
    }

    /**
     * Returns the recommendations that were not met.
     *
     * @return array Array of Requirement instances
     */
    public function getFailedRecommendations()
    {
        $array = array();
        foreach ($this->requirements as $req) {
            if (!$req->isFulfilled() && $req->isOptional()) {
                $array[] = $req;
            }
        }

        return $array;
    }

    /**
     * Returns whether a php.ini configuration is not correct.
     *
     * @return boolean php.ini configuration problem?
     */
    public function hasPhpIniConfigIssue()
    {
        foreach ($this->requirements as $req) {
            if (!$req->isFulfilled() && $req instanceof PhpIniRequirement) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the PHP configuration file (php.ini) path.
     *
     * @return string|false php.ini file path
     */
    public function getPhpIniConfigPath()
    {
        return get_cfg_var('cfg_file_path');
    }
}

/**
 * This class specifies all requirements and optional recommendations that
 * are necessary to run Mautic.
 *
 * @author Tobias Schultze <http://tobion.de>
 * @author Fabien Potencier <fabien@symfony.com>
 */
class MauticRequirements extends RequirementCollection
{
    const REQUIRED_PHP_VERSION = '5.3.7';

    /**
     * Constructor that initializes the requirements.
     */
    public function __construct()
    {
        /* mandatory requirements follow */

        $installedPhpVersion = phpversion();

        $this->addRequirement(
            version_compare($installedPhpVersion, self::REQUIRED_PHP_VERSION, '>='),
            sprintf('PHP version must be at least %s (%s installed)', self::REQUIRED_PHP_VERSION, $installedPhpVersion),
            sprintf('You are running PHP version "<strong>%s</strong>", but Mautic needs at least PHP "<strong>%s</strong>" to run.
                Before using Mautic, upgrade your PHP installation, preferably to the latest version.',
                $installedPhpVersion, self::REQUIRED_PHP_VERSION),
            sprintf('Install PHP %s or newer (installed version is %s)', self::REQUIRED_PHP_VERSION, $installedPhpVersion)
        );

        $this->addRequirement(
            version_compare($installedPhpVersion, '5.3.16', '!='),
            'PHP version must not be 5.3.16 as Mautic\'s dependencies won\'t work properly with it',
            'Install PHP 5.3.17 or newer (or downgrade to an earlier PHP version)'
        );

        $this->addRequirement(
            is_dir(dirname(__DIR__) . '/vendor/composer'),
            'Vendor libraries must be installed',
            'Vendor libraries are missing. Install composer following instructions from <a href="http://getcomposer.org/">http://getcomposer.org/</a>. ' .
                'Then run "<strong>php composer.phar install</strong>" to install them.'
        );

        $baseDir = basename(dirname(__DIR__) . '/app');

        $this->addRequirement(
            is_writable(dirname(__DIR__) . '/app/cache'),
            "$baseDir/cache/ directory must be writable",
            "Change the permissions of the \"<strong>$baseDir/cache/</strong>\" directory so that the web server can write into it."
        );

        $this->addRequirement(
            is_writable(dirname(__DIR__) . '/app/logs'),
            "$baseDir/logs/ directory must be writable",
            "Change the permissions of the \"<strong>$baseDir/logs/</strong>\" directory so that the web server can write into it."
        );

        $this->addPhpIniRequirement(
            'date.timezone', true, false,
            'date.timezone setting must be set',
            'Set the "<strong>date.timezone</strong>" setting in php.ini<a href="#phpini">*</a> (like Europe/Paris).'
        );

        if (version_compare($installedPhpVersion, self::REQUIRED_PHP_VERSION, '>=')) {
            $timezones = array();
            foreach (DateTimeZone::listAbbreviations() as $abbreviations) {
                foreach ($abbreviations as $abbreviation) {
                    $timezones[$abbreviation['timezone_id']] = true;
                }
            }

            $this->addRequirement(
                isset($timezones[date_default_timezone_get()]),
                sprintf('Configured default timezone "%s" must be supported by your installation of PHP', date_default_timezone_get()),
                'Your default timezone is not supported by PHP. Check for typos in your <strong>php.ini</strong> file and have a look at the list of deprecated timezones at <a href="http://php.net/manual/en/timezones.others.php">http://php.net/manual/en/timezones.others.php</a>.'
            );
        }

        $this->addRequirement(
            function_exists('json_encode'),
            'json_encode() must be available',
            'Install and enable the <strong>JSON</strong> extension.'
        );

        $this->addRequirement(
            function_exists('session_start'),
            'session_start() must be available',
            'Install and enable the <strong>session</strong> extension.'
        );

        $this->addRequirement(
            function_exists('ctype_alpha'),
            'ctype_alpha() must be available',
            'Install and enable the <strong>ctype</strong> extension.'
        );

        $this->addRequirement(
            function_exists('token_get_all'),
            'token_get_all() must be available',
            'Install and enable the <strong>Tokenizer</strong> extension.'
        );

        $this->addRequirement(
            function_exists('simplexml_import_dom'),
            'simplexml_import_dom() must be available',
            'Install and enable the <strong>SimpleXML</strong> extension.'
        );

        if (function_exists('apc_store') && ini_get('apc.enabled')) {
            if (version_compare($installedPhpVersion, '5.4.0', '>=')) {
                $this->addRequirement(
                    version_compare(phpversion('apc'), '3.1.13', '>='),
                    'APC version must be at least 3.1.13 when using PHP 5.4',
                    'Upgrade your <strong>APC</strong> extension (3.1.13+).'
                );
            } else {
                $this->addRequirement(
                    version_compare(phpversion('apc'), '3.0.17', '>='),
                    'APC version must be at least 3.0.17',
                    'Upgrade your <strong>APC</strong> extension (3.0.17+).'
                );
            }
        }

        $this->addPhpIniRequirement('detect_unicode', false);

        if (extension_loaded('suhosin')) {
            $this->addPhpIniRequirement(
                'suhosin.executor.include.whitelist',
                create_function('$cfgValue', 'return false !== stripos($cfgValue, "phar");'),
                false,
                'suhosin.executor.include.whitelist must be configured correctly in php.ini',
                'Add "<strong>phar</strong>" to <strong>suhosin.executor.include.whitelist</strong> in php.ini<a href="#phpini">*</a>.'
            );
        }

        if (extension_loaded('xdebug')) {
            $this->addPhpIniRequirement(
                'xdebug.show_exception_trace', false, true
            );

            $this->addPhpIniRequirement(
                'xdebug.scream', false, true
            );

            $this->addPhpIniRecommendation(
                'xdebug.max_nesting_level',
                create_function('$cfgValue', 'return $cfgValue > 100;'),
                true,
                'xdebug.max_nesting_level should be above 100 in php.ini',
                'Set "<strong>xdebug.max_nesting_level</strong>" to e.g. "<strong>250</strong>" in php.ini<a href="#phpini">*</a> to stop Xdebug\'s infinite recursion protection erroneously throwing a fatal error in your project.'
            );
        }

        $pcreVersion = defined('PCRE_VERSION') ? (float) PCRE_VERSION : null;

        $this->addRequirement(
            null !== $pcreVersion,
            'PCRE extension must be available',
            'Install the <strong>PCRE</strong> extension (version 8.0+).'
        );

        /* optional recommendations follow */

        $this->addRecommendation(
            version_compare($installedPhpVersion, '5.3.8', '>='),
            'When using annotations you should have at least PHP 5.3.8 due to PHP bug #55156',
            'Install PHP 5.3.8 or newer if your project uses annotations.'
        );

        $this->addRecommendation(
            version_compare($installedPhpVersion, '5.4.0', '!='),
            'You should not use PHP 5.4.0 due to the PHP bug #61453',
            'Your project might not work properly due to the PHP bug #61453 ("Cannot dump definitions which have method calls"). Install PHP 5.4.1 or newer.'
        );

        $this->addRecommendation(
            version_compare($installedPhpVersion, '5.4.11', '>='),
            'When using the logout handler from the Symfony Security Component, you should have at least PHP 5.4.11 due to PHP bug #63379 (as a workaround, you can also set invalidate_session to false in the security logout handler configuration)',
            'Install PHP 5.4.11 or newer if your project uses the logout handler from the Symfony Security Component.'
        );

        $this->addRecommendation(
            (version_compare($installedPhpVersion, '5.3.18', '>=') && version_compare($installedPhpVersion, '5.4.0', '<'))
            ||
            version_compare($installedPhpVersion, '5.4.8', '>='),
            'You should use PHP 5.3.18+ or PHP 5.4.8+ to always get nice error messages for fatal errors in the development environment due to PHP bug #61767/#60909',
            'Install PHP 5.3.18+ or PHP 5.4.8+ if you want nice error messages for all fatal errors in the development environment.'
        );

        if (null !== $pcreVersion) {
            $this->addRecommendation(
                $pcreVersion >= 8.0,
                sprintf('PCRE extension should be at least version 8.0 (%s installed)', $pcreVersion),
                '<strong>PCRE 8.0+</strong> is preconfigured in PHP since 5.3.2 but you are using an outdated version of it. Mautic probably works anyway but it is recommended to upgrade your PCRE extension.'
            );
        }

        $this->addRecommendation(
            class_exists('DomDocument'),
            'PHP-XML module should be installed',
            'Install and enable the <strong>PHP-XML</strong> module.'
        );

        $this->addRecommendation(
            function_exists('mb_strlen'),
            'mb_strlen() should be available',
            'Install and enable the <strong>mbstring</strong> extension.'
        );

        $this->addRecommendation(
            function_exists('iconv'),
            'iconv() should be available',
            'Install and enable the <strong>iconv</strong> extension.'
        );

        $this->addRecommendation(
            function_exists('utf8_decode'),
            'utf8_decode() should be available',
            'Install and enable the <strong>XML</strong> extension.'
        );

        if (!defined('PHP_WINDOWS_VERSION_BUILD')) {
            $this->addRecommendation(
                function_exists('posix_isatty'),
                'posix_isatty() should be available',
                'Install and enable the <strong>php_posix</strong> extension (used to colorize the CLI output).'
            );
        }

        $this->addRecommendation(
            class_exists('Locale'),
            'intl extension should be available',
            'Install and enable the <strong>intl</strong> extension (used for validators).'
        );

        if (class_exists('Collator')) {
            $this->addRecommendation(
                null !== new Collator('fr_FR'),
                'intl extension should be correctly configured',
                'The intl extension does not behave properly. This problem is typical on PHP 5.3.X x64 WIN builds.'
            );
        }

        if (class_exists('Locale')) {
            if (defined('INTL_ICU_VERSION')) {
                $version = INTL_ICU_VERSION;
            } else {
                $reflector = new ReflectionExtension('intl');

                ob_start();
                $reflector->info();
                $output = strip_tags(ob_get_clean());

                preg_match('/^ICU version +(?:=> )?(.*)$/m', $output, $matches);
                $version = $matches[1];
            }

            $this->addRecommendation(
                version_compare($version, '4.0', '>='),
                'intl ICU version should be at least 4+',
                'Upgrade your <strong>intl</strong> extension with a newer ICU version (4+).'
            );
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

        $this->addRecommendation(
            $accelerator,
            'a PHP accelerator should be installed',
            'Install and enable a <strong>PHP accelerator</strong> like APC (highly recommended).'
        );

        $this->addPhpIniRecommendation('short_open_tag', false);

        $this->addPhpIniRecommendation('magic_quotes_gpc', false, true);

        $this->addPhpIniRecommendation('register_globals', false, true);

        $this->addPhpIniRecommendation('session.auto_start', false);

        $this->addRecommendation(
            class_exists('PDO'),
            'PDO should be installed',
            'Install <strong>PDO</strong> (mandatory for Doctrine).'
        );

        if (class_exists('PDO')) {
            $drivers = PDO::getAvailableDrivers();
            $this->addRecommendation(
                count($drivers),
                sprintf('PDO should have some drivers installed (currently available: %s)', count($drivers) ? implode(', ', $drivers) : 'none'),
                'Install <strong>PDO drivers</strong> (mandatory for Doctrine).'
            );
        }
    }
}
