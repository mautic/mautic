<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Test;

use Symfony\Component\Dotenv\Dotenv;

final class EnvLoader
{
    /**
     * Loads the env variables from .env.dist or .env file for PHPUNIT tests.
     */
    public static function load(): void
    {
        $root    = __DIR__.'/../../../../';
        $envFile = file_exists($root.'.env') ? $root.'.env' : $root.'.env.dist';
        $dotenv  = new Dotenv();
        $dotenv->load($envFile);
    }
}
