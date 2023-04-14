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

    /**
     * @var string
     */
    public const ROOT_PATH = __DIR__.'/../../..';

    /** @var array<mixed> */
    private array $envParameters = [];

    public function __construct()
    {
        $this->envParameters = $this->load();
        $this->cleanCache();
    }

    /**
     * @return array<mixed>
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

        return array_merge(self::loadDefaultEnvironmentSetup(), $envParameters);
    }

    /**
     * @return array<mixed>
     *
     * Returns the default environment setup
     */
    private static function loadDefaultEnvironmentSetup(): array
    {
        return [
            'DEBUG'            => self::DEBUG,
            'ENV'              => self::ENV_PROD,
            'DEV_IP_WHITELIST' => self::DEV_IPS_WHITELIST,
        ];
    }

    /*
     * Clean the cache if the environment is set to dev and the IP is whitelisted.
     */
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

    /**
     * @return array<mixed>
     *                      Returns the environment parameters.
     *                      The environment parameters are the parameters defined in the environment.php file.
     */
    public function getEnvParameters(): array
    {
        return $this->envParameters;
    }
}
