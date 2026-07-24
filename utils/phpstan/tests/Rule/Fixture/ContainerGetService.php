<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Psr\Container\ContainerInterface;

// container fetch must be reported
class ContainerGetService
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public function viaProperty(): void
    {
        $this->container->get('mautic.helper.something');
    }

    public function viaLocalContainer(ContainerInterface $container): void
    {
        $container->get('mautic.helper.something');
    }
}
