<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Symfony\Component\Routing\RouterInterface;

final class ServiceInStaticMethod
{
    public static function build(RouterInterface $router): string
    {
        return $router->generate('some_route');
    }
}
