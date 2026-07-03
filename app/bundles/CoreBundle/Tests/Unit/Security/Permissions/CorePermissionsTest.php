<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Security\Permissions;

use Mautic\ApiBundle\Security\Permissions\ApiPermissions;
use Mautic\AssetBundle\Security\Permissions\AssetPermissions;
use Mautic\CampaignBundle\Security\Permissions\CampaignPermissions;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\UserRepository;
use MauticPlugin\MauticFocusBundle\Security\Permissions\FocusPermissions;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CorePermissionsTest extends \PHPUnit\Framework\TestCase
{
    private CorePermissions $corePermissions;

    private CoreParametersHelper&MockObject $coreParametersHelper;

    private UserHelper&MockObject $userHelper;

    private TranslatorInterface&MockObject $translator;

    private UserRepository&MockObject $userRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userHelper           = $this->createMock(UserHelper::class);
        $this->translator           = $this->createMock(TranslatorInterface::class);
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->userRepository       = $this->createMock(UserRepository::class);
        $this->corePermissions      = new CorePermissions(
            $this->userHelper,
            $this->translator,
            $this->coreParametersHelper,
            [
                $this->mockBundleArray(ApiPermissions::class),
                $this->mockBundleArray(AssetPermissions::class),
                $this->mockBundleArray(CampaignPermissions::class),
            ],
            [
                $this->mockBundleArray(FocusPermissions::class),
            ],
            $this->userRepository
        );
    }

    public function testSettingPermissionObject(): void
    {
        $this->coreParametersHelper->method('all')
            ->willReturn(['parameter_a' => 'value_a']);

        $assetPermissions = new AssetPermissions($this->coreParametersHelper);
        $this->corePermissions->setPermissionObject($assetPermissions);
        $permissionObjects = $this->corePermissions->getPermissionObjects();

        // Even though the AssetPermissions object was set upfront there are
        // still 4 objects available.
        // The other three were instantiated to keep BC.
        $this->assertCount(4, $permissionObjects);

        $this->assertSame($assetPermissions, $this->corePermissions->getPermissionObject('asset'));
        $this->assertSame($assetPermissions, $this->corePermissions->getPermissionObject(AssetPermissions::class));
        $this->assertSame($permissionObjects['campaign'], $this->corePermissions->getPermissionObject(CampaignPermissions::class));
    }

    public function testHasEntityAccessAllowsSameRoleOwner(): void
    {
        $corePermissions = $this->createCorePermissions();
        $role            = $this->createRole(3);
        $currentUser     = $this->createUser(10, $role, ['lead' => ['leads' => 2048]]);

        $corePermissions->setPermissionObject($this->createLeadPermissions());
        $this->userHelper->method('getUser')->willReturn($currentUser);
        $this->userRepository->expects($this->once())
            ->method('findUserIdsByRole')
            ->with(3)
            ->willReturn([10, 20]);

        $this->assertTrue($corePermissions->hasEntityAccess('lead:leads:viewown', 'lead:leads:viewother', 20));
    }

    public function testHasEntityAccessDeniesDifferentRoleOwner(): void
    {
        $corePermissions = $this->createCorePermissions();
        $currentUser     = $this->createUser(10, $this->createRole(3), ['lead' => ['leads' => 2048]]);

        $corePermissions->setPermissionObject($this->createLeadPermissions());
        $this->userHelper->method('getUser')->willReturn($currentUser);
        $this->userRepository->expects($this->once())
            ->method('findUserIdsByRole')
            ->with(3)
            ->willReturn([10]);

        $this->assertFalse($corePermissions->hasEntityAccess('lead:leads:viewown', 'lead:leads:viewother', 20));
    }

    public function testHasEntityAccessUsesOwnerObjectForSameRoleCheck(): void
    {
        $corePermissions = $this->createCorePermissions();
        $role            = $this->createRole(3);
        $currentUser     = $this->createUser(10, $role, ['lead' => ['leads' => 2048]]);
        $ownerUser       = $this->createUser(20, $role);

        $corePermissions->setPermissionObject($this->createLeadPermissions());
        $this->userHelper->method('getUser')->willReturn($currentUser);
        $this->userRepository->expects($this->never())->method('find');

        $this->assertTrue($corePermissions->hasEntityAccess('lead:leads:viewown', 'lead:leads:viewother', $ownerUser));
    }

    public function testHasEntityAccessAllowsExplicitSameRolePermissionBoolean(): void
    {
        $corePermissions = $this->createCorePermissions();
        $role            = $this->createRole(3);
        $currentUser     = $this->createUser(10, $role);
        $ownerUser       = $this->createUser(20, $role);

        $this->userHelper->method('getUser')->willReturn($currentUser);
        $this->userRepository->expects($this->never())->method('find');

        $this->assertTrue($corePermissions->hasEntityAccess(false, false, $ownerUser, true));
    }

    public function testResetClearsSameRoleUserIdCache(): void
    {
        $corePermissions = $this->createCorePermissions();
        $currentUser     = $this->createUser(10, $this->createRole(3));

        $this->userHelper->method('getUser')->willReturn($currentUser);
        $this->userRepository->expects($this->exactly(2))
            ->method('findUserIdsByRole')
            ->with(3)
            ->willReturn([10, 20]);

        $this->assertTrue($corePermissions->hasEntityAccess(false, false, 20, true));
        $corePermissions->reset();
        $this->assertTrue($corePermissions->hasEntityAccess(false, false, 20, true));
    }

    public function testSameRolePermissionsAddRequiredPermissions(): void
    {
        $permissions = [
            'leads' => [
                'deletesamerole',
                'publishsamerole',
            ],
        ];

        $this->createLeadPermissions()->analyzePermissions($permissions, []);

        $this->assertContains('editsamerole', $permissions['leads']);
        $this->assertContains('viewsamerole', $permissions['leads']);
        $this->assertContains('viewown', $permissions['leads']);
    }

    /**
     * @return array{permissionClasses: array<class-string, class-string>}
     */
    private function mockBundleArray(string $permissionClass): array
    {
        return ['permissionClasses' => [$permissionClass => $permissionClass]];
    }

    private function createCorePermissions(): CorePermissions
    {
        return new CorePermissions(
            $this->userHelper,
            $this->translator,
            $this->coreParametersHelper,
            [],
            [],
            $this->userRepository
        );
    }

    private function createLeadPermissions(): AbstractPermissions
    {
        $permissions = new class([]) extends AbstractPermissions {
            public function definePermissions(): void
            {
                $this->addExtendedPermissions('leads');
            }

            public function getName(): string
            {
                return 'lead';
            }
        };
        $permissions->definePermissions();

        return $permissions;
    }

    /**
     * @param array<string, array<string, int>> $activePermissions
     */
    private function createUser(int $id, Role $role, array $activePermissions = []): User
    {
        $user = new class($id) extends User {
            public function __construct(private int $testId)
            {
                parent::__construct();
            }

            public function getId(): int
            {
                return $this->testId;
            }
        };
        $user->setRole($role);
        $user->setActivePermissions($activePermissions);

        return $user;
    }

    private function createRole(int $id): Role
    {
        return new class($id) extends Role {
            public function __construct(private int $testId)
            {
                parent::__construct();
            }

            public function getId(): int
            {
                return $this->testId;
            }
        };
    }
}
