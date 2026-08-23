<?php

namespace Mautic\CoreBundle\Factory;

use Mautic\CoreBundle\Model\MauticModelInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * @template M of object
 */
class ModelFactory
{
    public function __construct(
        #[AutowireLocator(MauticModelInterface::class, defaultIndexMethod: 'getName')]
        private readonly ServiceLocator $container,
    ) {
    }

    /**
     * @param non-empty-string $modelNameKey
     */
    public function getModel(string $modelNameKey): MauticModelInterface
    {
        // Shortcut for models with the same name as the bundle, e.g. "lead" => "lead.lead"
        if (!str_contains($modelNameKey, '.')) {
            $modelNameKey = "{$modelNameKey}.{$modelNameKey}";
        }

        // Each model is registered in the locator under the key returned by its static getName() method.
        if ($this->container->has($modelNameKey)) {
            return $this->container->get($modelNameKey);
        }

        throw new \InvalidArgumentException($modelNameKey.' is not a registered model key.');
    }

    public function hasModel(string $modelNameKey): bool
    {
        try {
            $this->getModel($modelNameKey);

            return true;
        } catch (\InvalidArgumentException) {
            return false;
        }
    }
}
