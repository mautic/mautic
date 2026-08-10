<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Security\Permissions;

use Mautic\ApiBundle\Security\Permissions\ApiPermissions;
use Mautic\AssetBundle\Security\Permissions\AssetPermissions;
use Mautic\CampaignBundle\Security\Permissions\CampaignPermissions;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use MauticPlugin\MauticFocusBundle\Security\Permissions\FocusPermissions;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CorePermissionsTest extends \PHPUnit\Framework\TestCase
{
    private CorePermissions $corePermissions;

    /**
     * @var MockObject&CoreParametersHelper
     */
    private MockObject $coreParametersHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->corePermissions      = new CorePermissions(
            $this->createStub(UserHelper::class),
            $this->createStub(TranslatorInterface::class),
            $this->coreParametersHelper,
            [
                $this->mockBundleArray(AssetPermissions::class),
                $this->mockBundleArray(CampaignPermissions::class),
            ],
            [
                $this->mockBundleArray(FocusPermissions::class),
            ]
        );
    }

    public function testSettingPermissionObject(): void
    {
        $this->coreParametersHelper->method('all')
            ->willReturn(['parameter_a' => 'value_a']);

        $assetPermissions    = new AssetPermissions($this->coreParametersHelper);
        $campaignPermissions = new CampaignPermissions($this->coreParametersHelper);
        $focusPermissions    = new FocusPermissions($this->coreParametersHelper);

        $this->corePermissions->setPermissionObject($assetPermissions);
        $this->corePermissions->setPermissionObject($campaignPermissions);
        $this->corePermissions->setPermissionObject($focusPermissions);

        // Only the permission objects registered as services are available.
        $permissionObjects = $this->corePermissions->getPermissionObjects();
        $this->assertCount(3, $permissionObjects);

        $this->assertSame($assetPermissions, $this->corePermissions->getPermissionObject('asset'));
        $this->assertSame($assetPermissions, $this->corePermissions->getPermissionObject(AssetPermissions::class));
        $this->assertSame($campaignPermissions, $this->corePermissions->getPermissionObject(CampaignPermissions::class));
        $this->assertSame($focusPermissions, $this->corePermissions->getPermissionObject(FocusPermissions::class));
    }

    public function testGetPermissionObjectThrowsForUnregisteredPermissionClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Permission class not found for '.ApiPermissions::class.' in permissions classes');

        $this->corePermissions->getPermissionObject(ApiPermissions::class);
    }

    public function testGetPermissionObjectReturnsFalseForUnregisteredPermissionClass(): void
    {
        $this->assertFalse($this->corePermissions->getPermissionObject(ApiPermissions::class, false));
    }

    /**
     * @return array{permissionClasses: array<class-string, class-string>}
     */
    private function mockBundleArray(string $permissionClass): array
    {
        return ['permissionClasses' => [$permissionClass => $permissionClass]];
    }
}
