<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Unit\Helper;

use Mautic\UserBundle\Helper\RoleSearchScopeProvider;
use Mautic\UserBundle\Model\RoleModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class RoleSearchScopeProviderTest extends TestCase
{
    private RoleModel&MockObject $roleModel;

    private TranslatorInterface&MockObject $translator;

    private RoleSearchScopeProvider $provider;

    protected function setUp(): void
    {
        $this->roleModel  = $this->createMock(RoleModel::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.core.searchcommand.name' => 'name',
                'mautic.user.user.searchcommand.isadmin' => 'is:admin',
                'mautic.core.searchcommand.ids' => 'ids',
                default => $key,
            });

        $this->roleModel->method('getCommandList')
            ->willReturn([
                'mautic.user.user.searchcommand.isadmin',
                'mautic.core.searchcommand.name',
                'mautic.core.searchcommand.ids',
            ]);

        $this->provider = new RoleSearchScopeProvider(
            $this->roleModel,
            $this->translator
        );
    }

    public function testGetScopesIncludesStandardFirst(): void
    {
        $scopes = $this->provider->getScopes();

        $this->assertSame('', $scopes[0]['command']);
        $this->assertSame('mautic.core.search.scope.standard', $scopes[0]['label']);
        $this->assertTrue($scopes[0]['default'] ?? false);
    }

    public function testGetScopesDoesNotDuplicatePinnedCommands(): void
    {
        $scopes = $this->provider->getScopes();

        $commands = array_column($scopes, 'command');

        $this->assertContains('is:admin', $commands);
        $this->assertCount(count(array_unique($commands)), $commands);
    }
}
