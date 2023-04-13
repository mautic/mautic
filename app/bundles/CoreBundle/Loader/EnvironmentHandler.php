<?php

namespace Mautic\CoreBundle\Loader;

/**
 * Class EnvironmentHandler.
 *
 * Responsible for handling the environment.php file.
 */
class EnvironmentHandler
{
    /**
     * @var string
     */
    public const ENV_DEV = 'dev';

    /**
     * @var string
     */
    public const ENV_PROD = 'prod';

    /*
     * @var bool
     */
    public const DEBUG = false;

    /*
     * @var array
     */
    public const DEV_IPS_WHITELIST = [
        '127.0.0.1',
        '::1',
        '172.17.0.1',
    ];

    public const ROOT_PATH = __DIR__.'/../../..';

    private array $envParameters = [];

    public function __construct()
    {
        $this->envParameters = $this->load();
        $this->cleanCache();
    }

    /**
     * @return array
     *
     * Responsible for loading the environment.php file and merging it with the default environment setup.
     * If the environment.php file is not present, the default environment setup will be used.
     * If the environment.php file is present but empty, the default environment setup will be used.
     *
     * @throws \Exception
     */
    private function load(): array
    {
        if (!file_exists(self::ROOT_PATH.'/config/environment.php')) {
            return self::loadDefaultEnvironmentSetup();
        }

        $envParameters = include self::ROOT_PATH.'/config/environment.php';

        if (empty($envParameters)) {
            return self::loadDefaultEnvironmentSetup();
        }
        dump(self::loadDefaultEnvironmentSetup(), $envParameters);

        return array_merge_recursive(self::loadDefaultEnvironmentSetup(), $envParameters);
    }

    private static function loadDefaultEnvironmentSetup(): array
    {
        return [
            'DEBUG'            => self::DEBUG,
            'ENV'              => self::ENV_PROD,
            'DEV_IP_WHITELIST' => self::DEV_IPS_WHITELIST,
        ];
    }

    private function cleanCache(): void
    {
        if (
            self::ENV_DEV === strtolower($this->envParameters['ENV'])
            && extension_loaded('apcu')
            && in_array(@$_SERVER['REMOTE_ADDR'], $this->envParameters['DEV_IPS_WHITELIST'])
        ) {
            @apcu_clear_cache();
        }
    }

    public function getEnvParameters(): array
    {
        return $this->envParameters;
    }
}
