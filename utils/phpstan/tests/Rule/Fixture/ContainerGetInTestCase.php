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

    public function viaAllowedServiceName(): void
    {
        $this->container->get('test.service_container');
        $this->container->get('test.private_services_locator');
        $this->container->get('router');
        $this->container->get('fm_elfinder.configurator');
        $this->container->get('doctrine.debug_data_holder');
        $this->container->get('translator.default');
        $this->container->get('security.untracked_token_storage');
    }
}
