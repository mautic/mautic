<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\AliasBundle;

use Psr\Container\ContainerInterface;

final class AliasHelperFetcher
{
    public function __construct(
        private ContainerInterface $container,
    ) {
    }

    public function fetchByInterpolatedName(string $name): object
    {
        return $this->container->get("mautic.alias.integration.{$name}");
    }
}
