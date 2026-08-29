<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Model;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Model\RoleModel;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class RoleModelTest extends TestCase
{
    public function testCloneEntityWithPermissions(): void
    {
        $sourceRole = new Role();
        $sourceRole->setName('Test Role');
        $sourceRole->setDescription('Test Description');
        $sourceRole->setIsAdmin(false);
        $sourceRole->setRawPermissions([
            'user:users' => ['viewown', 'editown'],
            'lead:leads' => ['view', 'edit'],
        ]);

        $mockTranslator = $this->createMock(Translator::class);
        $mockTranslator->expects($this->once())->method('trans')
            ->with('mautic.user.role.clone.prefix', ['%name%' => 'Test Role'], 'messages')
            ->willReturn('Clone of Test Role');

        $roleModel = $this->createRoleModel($this->createStub(CorePermissions::class), $mockTranslator);

        $clonedRole = $roleModel->cloneEntity($sourceRole);

        $this->assertSame('Clone of Test Role', $clonedRole->getName());
        $this->assertSame('Test Description', $clonedRole->getDescription());
        $this->assertFalse($clonedRole->isAdmin());
        $this->assertSame($sourceRole->getRawPermissions(), $clonedRole->getRawPermissions());
        $this->assertCount(0, $clonedRole->getPermissions());
    }

    public function testCloneEntityAdminRole(): void
    {
        $sourceRole = new Role();
        $sourceRole->setName('Admin Role');
        $sourceRole->setDescription('Admin Description');
        $sourceRole->setIsAdmin(true);
        $sourceRole->setRawPermissions([]);

        $mockTranslator = $this->createMock(Translator::class);
        $mockTranslator->expects($this->once())->method('trans')
            ->with('mautic.user.role.clone.prefix', ['%name%' => 'Admin Role'], 'messages')
            ->willReturn('Clone of Admin Role');

        $roleModel = $this->createRoleModel($this->createStub(CorePermissions::class), $mockTranslator);

        $clonedRole = $roleModel->cloneEntity($sourceRole);

        $this->assertSame('Clone of Admin Role', $clonedRole->getName());
        $this->assertSame('Admin Description', $clonedRole->getDescription());
        $this->assertTrue($clonedRole->isAdmin());
        $this->assertCount(0, $clonedRole->getPermissions());
    }

    private function createRoleModel(CorePermissions $security, ?Translator $translator = null): RoleModel
    {
        $translator ??= $this->createMock(Translator::class);

        return new RoleModel(
            $this->createStub(EntityManagerInterface::class),
            $security,
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(UrlGeneratorInterface::class),
            $translator,
            $this->createStub(UserHelper::class),
            $this->createStub(LoggerInterface::class),
            $this->createStub(CoreParametersHelper::class)
        );
    }
}
