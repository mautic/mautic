<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Psr\Container\ContainerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

// in a test only a string service name must be reported
class ContainerGetInTestCase
{
    public function __construct(
        private ContainerInterface $container,
    ) {
    }

    public function viaStringName(): void
    {
        $this->container->get('translator');
    }

    public function viaClassConstant(): void
    {
        $this->container->get(TranslatorInterface::class);
    }
}
