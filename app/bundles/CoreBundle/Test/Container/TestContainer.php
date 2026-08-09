<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Test\Container;

use Symfony\Bundle\FrameworkBundle\Test\TestContainer as BaseTestContainer;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class TestContainer extends BaseTestContainer
{
    private ContainerInterface $publicContainer;

    /**
     * @param ?object $service
     */
    public function set(string $id, $service): void
    {
        $closure = static function (ContainerInterface $container) use ($id, $service): void {
            $container->services[$id] = $service;
            $container->privates[$id] = $service;
        };
        \Closure::bind($closure, null, $this->publicContainer)($this->publicContainer);
    }

    public function setPublicContainer(ContainerInterface $container): void
    {
        $this->publicContainer = $container;
    }
}
