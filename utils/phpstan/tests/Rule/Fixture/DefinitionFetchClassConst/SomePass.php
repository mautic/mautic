<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\DefinitionFetchClassConst;

use Symfony\Component\DependencyInjection\ContainerBuilder;

final class SomePass
{
    public function process(ContainerBuilder $container): void
    {
        // already a class constant, left alone
        $container->getDefinition(SomeHelper::class);

        $container->getDefinition('Utils\PHPStan\Tests\Rule\Fixture\DefinitionFetchClassConst\SomeHelper');

        // a plain service id, not a class, left alone
        $container->getDefinition('mautic.some.helper');
    }
}
