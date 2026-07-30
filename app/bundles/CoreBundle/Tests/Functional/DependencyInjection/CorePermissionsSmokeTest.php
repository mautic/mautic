<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\DependencyInjection;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;

final class CorePermissionsSmokeTest extends AbstractContainerSmokeTestCase
{
    /**
     * The permission objects registered as a service, collected by the "mautic.permissions" tag that
     * autoconfiguration puts on every AbstractPermissions child.
     *
     * @var string[]
     */
    private const EXPECTED_TAGGED_PERMISSION_CLASSES = [
        \Mautic\AssetBundle\Security\Permissions\AssetPermissions::class,
        \Mautic\MarketplaceBundle\Security\Permissions\MarketplacePermissions::class,
    ];

    /**
     * A permission object missing here grants nothing and denies nothing, the permissions it owns simply
     * stop being checked - no error says so.
     */
    public function testTaggedPermissionObjectsReachCorePermissions(): void
    {
        $corePermissions = $this->resolveCorePermissions();

        $permissionClasses = array_map(
            fn (AbstractPermissions $permissions): string => $permissions::class,
            $corePermissions->getPermissionObjects()
        );

        foreach (self::EXPECTED_TAGGED_PERMISSION_CLASSES as $expectedClass) {
            $this->assertContains($expectedClass, $permissionClasses);
        }
    }

    /**
     * Every bundle brings its own permission object, so the count only ever grows - a drop means one stopped
     * being registered.
     */
    public function testAllBundlePermissionObjectsAreKnown(): void
    {
        $permissionObjects = $this->resolveCorePermissions()->getPermissionObjects();

        $this->assertGreaterThanOrEqual(24, count($permissionObjects));
    }

    private function resolveCorePermissions(): CorePermissions
    {
        foreach ($this->createAllServices() as $service) {
            if ($service instanceof CorePermissions) {
                return $service;
            }
        }

        $this->fail('The CorePermissions is not in the container');
    }
}
