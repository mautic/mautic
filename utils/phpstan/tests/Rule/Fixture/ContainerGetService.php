<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Psr\Container\ContainerInterface;

// both container fetches must be reported
class ContainerGetService
{
    public function viaLocalContainer(ContainerInterface $container): void
    {
        $container->get('mautic.helper.something');
    }

    public function viaThis(): void
    {
        $this->get('mautic.helper.something');
    }

    public function get(string $id): object
    {
        return new \stdClass();
    }
}
