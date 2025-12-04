<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

/**
 * The main goal of this function is to save Twig's context into memory
 * from child templates, so that it can be restored by parent templates.
 * This is a workaround as Twig doesn't support passing back variables
 * from child to parent templates.
 */
class StorageExtension
{
    /**
     * @var array<string,mixed>
     */
    protected array $storage = [];

    /**
     * @param mixed $context
     */
    #[\Twig\Attribute\AsTwigFunction('save', needsContext: true)]
    public function save($context, string $name): void
    {
        $this->storage[$name] = $context;
    }

    /**
     * @param mixed $context
     */
    #[\Twig\Attribute\AsTwigFunction('restore', needsContext: true)]
    public function restore(&$context, string $name): void
    {
        $context = array_merge($context, $this->storage[$name]);
    }
}
