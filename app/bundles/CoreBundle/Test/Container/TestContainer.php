<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Test\Container;

use Symfony\Bundle\FrameworkBundle\Test\TestContainer as BaseTestContainer;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TestContainer extends BaseTestContainer
{
    private ContainerInterface $publicContainer;

    /**
     * @param ?object $service
     */
    public function set(string $id, $service): void
    {
        $closure = static function (ContainerInterface $container) use ($id, $service) {
            $container->services[$id] = $service; // @phpstan-ignore-line
            $container->privates[$id] = $service; // @phpstan-ignore-line
        };
        \Closure::bind($closure, null, $this->publicContainer)($this->publicContainer);
    }

    public function setPublicContainer(ContainerInterface $container): void
    {
        $this->publicContainer = $container;
    }
}
