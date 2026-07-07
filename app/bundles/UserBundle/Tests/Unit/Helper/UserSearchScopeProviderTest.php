<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Unit\Helper;

use Mautic\UserBundle\Helper\UserSearchScopeProvider;
use Mautic\UserBundle\Model\UserModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class UserSearchScopeProviderTest extends TestCase
{
    private UserModel&MockObject $userModel;

    private TranslatorInterface&MockObject $translator;

    private UserSearchScopeProvider $provider;

    protected function setUp(): void
    {
        $this->userModel  = $this->createMock(UserModel::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.core.searchcommand.name' => 'name',
                'mautic.user.user.searchcommand.isadmin' => 'is:admin',
                'mautic.core.searchcommand.email' => 'email',
                default => $key,
            });

        $this->userModel->method('getCommandList')
            ->willReturn([
                'mautic.core.searchcommand.email',
                'mautic.user.user.searchcommand.isadmin',
                'mautic.core.searchcommand.name',
            ]);

        $this->provider = new UserSearchScopeProvider(
            $this->userModel,
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
