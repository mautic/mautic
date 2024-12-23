<?php

namespace Mautic\CoreBundle\Factory;

use Mautic\CoreBundle\Model\AbstractCommonModel;
use Mautic\CoreBundle\Model\MauticModelInterface;
use Psr\Container\ContainerInterface;

/**
 * @template M of object
 */
class ModelFactory
{
    public function __construct(
        private ContainerInterface $container,
    ) {
    }

    /**
     * @return AbstractCommonModel<M>
     */
    public function getModel(string $modelNameKey): MauticModelInterface
    {
        if (class_exists($modelNameKey) && $this->container->has($modelNameKey)) {
            return $this->container->get($modelNameKey);
        }

        // Shortcut for models with the same name as the bundle
        if (!str_contains($modelNameKey, '.')) {
            $modelNameKey = "$modelNameKey.$modelNameKey";
        }

        $parts = explode('.', $modelNameKey);

        if (2 !== count($parts)) {
            throw new \InvalidArgumentException($modelNameKey.' is not a valid model key.');
        }

        [$bundle, $name] = $parts;

        // The container is now case sensitive
        $containerKey = sprintf('mautic.%s.model.%s', $bundle, $name);

        if ($this->container->has($containerKey)) {
            return $this->container->get($containerKey);
        }

        throw new \InvalidArgumentException($containerKey.' is not a registered model container key.');
    }

    /**
     * Check if a model exists.
     */
    public function hasModel($modelNameKey): bool
    {
        try {
            $this->getModel($modelNameKey);

            return true;
        } catch (\InvalidArgumentException) {
            return false;
        }
    }
}
