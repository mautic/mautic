<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional;

use Mautic\CoreBundle\Tests\Functional\DependencyInjection\TestKernel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Routing\RouterInterface;

/**
 * Regression guard for the resolved Mautic route map.
 *
 * It builds the full router collection and checks that every route recorded in
 * the committed fixture still exists with the same path, HTTP methods, defaults
 * and requirements. It is intentionally tolerant: brand-new routes are
 * allowed and do NOT require regenerating the fixture — only a removed, renamed
 * or altered existing route fails the test.
 *
 * Regenerate the fixture (e.g. after intentionally changing an existing route)
 * with:
 *   UPDATE_ROUTE_MAP=1 php bin/phpunit app/bundles/CoreBundle/Tests/Functional/RouteMapTest.php
 */
final class RouteMapTest extends TestCase
{
    private const string FIXTURE = __DIR__.'/route-map.json';

    public function testExistingRoutesAreUnchanged(): void
    {
        $actual = $this->buildRouteMap();

        if (false !== getenv('UPDATE_ROUTE_MAP')) {
            file_put_contents(self::FIXTURE, json_encode($actual, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).\PHP_EOL);
            $this->addToAssertionCount(1);

            return;
        }

        $this->assertFileExists(self::FIXTURE, 'Route map fixture is missing. Generate it with UPDATE_ROUTE_MAP=1.');
        /** @var array<string, array<string, mixed>> $expected */
        $expected = json_decode((string) file_get_contents(self::FIXTURE), true, 512, JSON_THROW_ON_ERROR);

        $removed = [];
        $changed = [];
        foreach ($expected as $name => $route) {
            if (!\array_key_exists($name, $actual)) {
                $removed[] = $name;
            } elseif ($actual[$name] !== $route) {
                $changed[] = $name;
            }
        }

        $this->assertSame([], $removed, 'These routes were removed or renamed: '.implode(', ', $removed));
        $this->assertSame(
            [],
            $changed,
            'These existing routes changed (path/methods/defaults/requirements): '.implode(', ', $changed)
            .'. If intentional, regenerate the fixture with UPDATE_ROUTE_MAP=1.'
        );
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function buildRouteMap(): array
    {
        $container = $this->buildContainer();

        // The RouterInterface alias is private; the public "router" service is the
        // only way to fetch it by a stable id from the built container.
        $router = $container->get('router');
        $this->assertInstanceOf(RouterInterface::class, $router);

        $map = [];
        foreach ($router->getRouteCollection() as $name => $route) {
            $controller = $route->getDefault('_controller');
            if (!\is_string($controller) || !str_contains($controller, 'Mautic\\')) {
                // Skip vendor routes (api-platform, FOSOAuth, LightSaml, ...) which
                // change independently of Mautic and would make this snapshot brittle.
                continue;
            }

            $defaults = $route->getDefaults();
            ksort($defaults);
            $requirements = $route->getRequirements();
            ksort($requirements);
            $methods = $route->getMethods();
            sort($methods);

            // Schemes are intentionally omitted: they are derived from the site_url
            // (forceSSL) at runtime, not from the route definition, so they differ
            // between environments and are not what this snapshot guards.
            $map[$name] = [
                'path'         => $route->getPath(),
                'methods'      => $methods,
                'defaults'     => $defaults,
                'requirements' => $requirements,
            ];
        }

        ksort($map);

        return $map;
    }

    private function buildContainer(): Container
    {
        $kernel = new TestKernel();
        $kernel->boot();

        /** @var Container $container */
        $container = $kernel->getContainer();

        return $container;
    }
}
