<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Test;

use Symfony\Component\Dotenv\Dotenv;

final class EnvLoader
{
    /**
     * Loads the env variables from .env(.*) files for PHPUNIT tests.
     */
    public static function load(): void
    {
        (new Dotenv())->loadEnv(__DIR__.'/../../../../.env');
    }
}
