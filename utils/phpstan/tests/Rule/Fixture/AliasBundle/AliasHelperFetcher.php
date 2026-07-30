<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\AliasBundle;

final class AliasHelperFetcher
{
    public function __construct(
        private \Psr\Container\ContainerInterface $container,
    ) {
    }

    public function fetchByInterpolatedName(string $name): object
    {
        return $this->container->get("mautic.alias.integration.{$name}");
    }

    /**
     * The model key is what a model container id is built of at runtime,
     * "alias.used_model" stands for "mautic.alias.model.used_model".
     */
    public function fetchModelName(): string
    {
        return 'alias.used_model';
    }

    /**
     * A bundle named in camel case brings a camel case model key along.
     */
    public function fetchCamelModelName(): string
    {
        return 'camelAlias';
    }
}
