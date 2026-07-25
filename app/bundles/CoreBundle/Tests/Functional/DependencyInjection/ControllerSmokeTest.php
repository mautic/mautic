<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\DependencyInjection;

final class ControllerSmokeTest extends AbstractContainerSmokeTestCase
{
    /**
     * There are 157 controllers in the container, keep a small reserve for removed ones.
     */
    private const MINIMAL_CONTROLLER_COUNT = 154;

    public function testAllControllersCanBeCreated(): void
    {
        // vendor controllers are out of scope, and not all Mautic controllers extend the Symfony one
        $controllers = array_filter(
            $this->createAllServices(),
            static fn (object $service): bool => str_ends_with($service::class, 'Controller')
                && (str_starts_with($service::class, 'Mautic\\') || str_starts_with($service::class, 'MauticPlugin\\'))
        );

        $this->assertGreaterThanOrEqual(self::MINIMAL_CONTROLLER_COUNT, count($controllers));
    }
}
