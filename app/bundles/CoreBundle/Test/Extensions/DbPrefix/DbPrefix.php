<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Test\Extensions\DbPrefix;

use Mautic\CoreBundle\Test\EnvLoader;
use Mautic\CoreBundle\Test\Extensions\DbPrefix\Subscriber\ExecutionStartedSubscriber;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

class DbPrefix implements Extension
{
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $this->registerSubscribers($facade);
    }

    public function defineDbPrefix(): void
    {
        EnvLoader::load();
        $prefix = false === getenv('MAUTIC_DB_PREFIX') ? 'test_' : getenv('MAUTIC_DB_PREFIX');
        define('MAUTIC_TABLE_PREFIX', $prefix);
        echo 'using db prefix "'.$prefix.'"'.PHP_EOL;
    }

    private function registerSubscribers(Facade $facade): void
    {
        $facade->registerSubscribers(
            new ExecutionStartedSubscriber($this),
        );
    }
}
