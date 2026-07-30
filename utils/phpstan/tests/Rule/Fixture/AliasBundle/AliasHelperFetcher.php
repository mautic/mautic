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
     * A model key reaches the model factory as a literal, "alias.used_model" stands for
     * "mautic.alias.model.used_model".
     */
    public function fetchUsedModel(): object
    {
        return $this->container->getModel('alias.used_model');
    }

    /**
     * A bundle named in camel case brings a camel case model key along.
     */
    public function getModelName(): string
    {
        return 'camelAlias';
    }

    /**
     * A lookup form type names its model by the "model" option.
     *
     * @return array<string, string>
     */
    public function resolveLookupOptions(): array
    {
        return ['model' => 'alias.option_model'];
    }

    /**
     * A mail stat source looks like a model key, yet it never is one.
     *
     * @return array<string, string>
     */
    public function resolveStatSource(): array
    {
        return ['source' => 'alias.not_a_model'];
    }
}
