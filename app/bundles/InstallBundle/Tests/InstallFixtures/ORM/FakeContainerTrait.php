<?php

declare(strict_types=1);

namespace Mautic\InstallBundle\Tests\InstallFixtures\ORM;

use Symfony\Component\DependencyInjection\ContainerInterface;

trait FakeContainerTrait
{
    public function getContainerFake(): ContainerInterface
    {
        return new class($this->tempContainer) implements ContainerInterface {
            /**
             * @var ContainerInterface
             */
            private $container;

            public function __construct(ContainerInterface $container)
            {
                $this->container = $container;
            }

            public function set($id, $service)
            {
                $this->container->set($id, $service);
            }

            public function get($id, $invalidBehavior = self::EXCEPTION_ON_INVALID_REFERENCE)
            {
                return $this->container->get($id, $invalidBehavior);
            }

            public function has($id)
            {
                return $this->container->has($id);
            }

            public function initialized($id)
            {
                return $this->container->initialized($id);
            }

            public function getParameter($name)
            {
                return $this->container->getParameter($name);
            }

            public function hasParameter($name)
            {
                return $this->container->hasParameter($name);
            }

            public function setParameter($name, $value)
            {
                $this->container->setParameter($name, $value);
            }
        };
    }
}
