<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * The main goal of this function is to save Twig's context into memory
 * from child templates, so that it can be restored by parent templates.
 * This is a workaround as Twig doesn't support passing back variables
 * from child to parent templates.
 */
class StorageExtension extends AbstractExtension
{
    /**
     * @var array<string,mixed>
     */
    protected array $storage = [];

    public function getFunctions()
    {
        return [
            new TwigFunction('save', [$this, 'save'], ['needs_context' => true]),
            new TwigFunction('restore', [$this, 'restore'], ['needs_context' => true]),
        ];
    }

    /**
     * @param mixed $context
     */
    public function save($context, string $name): void
    {
        $this->storage[$name] = $context;
    }

    /**
     * @param mixed $context
     */
    public function restore(&$context, string $name): void
    {
        $context = array_merge($context, $this->storage[$name]);
    }
}
