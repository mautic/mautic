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
}
