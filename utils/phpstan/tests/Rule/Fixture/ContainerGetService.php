<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Psr\Container\ContainerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

// container fetch must be reported
class ContainerGetService
{
    public function __construct(
        private ContainerInterface $container,
    ) {
    }

    public function viaProperty(): void
    {
        $this->container->get('mautic.helper.something');
    }

    public function viaLocalContainer(ContainerInterface $container): void
    {
        $container->get('mautic.helper.something');
    }

    public function viaClassConstant(): void
    {
        $this->container->get(TranslatorInterface::class);
    }

    public function viaContainerGetter(): void
    {
        $this->getContainer()->get('translator');
    }

    // a runtime service name has nothing to inject
    public function viaVariable(string $serviceName): void
    {
        $this->container->get($serviceName);
    }

    // monolog.logger.mautic cannot be injected as a typed property, so it is allowed
    public function viaAllowedServiceName(): void
    {
        $this->container->get('monolog.logger.mautic');
    }

    private function getContainer(): ContainerInterface
    {
        return $this->container;
    }
}
