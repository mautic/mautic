<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Unit\Helper;

use Mautic\EmailBundle\Helper\EmailSearchScopeProvider;
use Mautic\EmailBundle\Model\EmailModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class EmailSearchScopeProviderTest extends TestCase
{
    private EmailModel&MockObject $emailModel;

    private TranslatorInterface&MockObject $translator;

    private EmailSearchScopeProvider $provider;

    protected function setUp(): void
    {
        $this->emailModel = $this->createMock(EmailModel::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.email.email.searchcommand.subject' => 'subject',
                'mautic.email.email.searchcommand.subject.label' => 'Subject',
                'mautic.core.searchcommand.ids' => 'ids',
                'mautic.core.searchcommand.ismine' => 'is:mine',
                default => $key,
            });

        $this->emailModel->method('getCommandList')
            ->willReturn([
                'mautic.email.email.searchcommand.subject',
                'mautic.core.searchcommand.ids',
                'mautic.core.searchcommand.ismine',
            ]);

        $this->provider = new EmailSearchScopeProvider(
            $this->emailModel,
            $this->translator
        );
    }

    public function testGetScopesIncludesStandardLabel(): void
    {
        $scopes = $this->provider->getScopes();

        $this->assertSame('mautic.core.search.scope.standard', $scopes[0]['label']);
        $this->assertTrue($scopes[0]['translate'] ?? true);
    }

    public function testGetScopesIncludesAllCommands(): void
    {
        $scopes = $this->provider->getScopes();

        $commands = array_column($scopes, 'command');

        $this->assertContains('ids', $commands);
        $this->assertSame('', $scopes[0]['command']);
        $this->assertTrue($scopes[0]['default'] ?? false);
    }

    public function testGetScopesUsesFriendlyLabelWhenAvailable(): void
    {
        $scopes = $this->provider->getScopes();

        $subjectScope = array_values(array_filter($scopes, static fn (array $scope): bool => 'subject' === $scope['command']))[0];

        $this->assertSame('mautic.email.email.searchcommand.subject', $subjectScope['label']);
    }

    public function testGetScopesDoesNotDuplicatePinnedCommands(): void
    {
        $scopes = $this->provider->getScopes();

        $commands = array_column($scopes, 'command');

        $this->assertSame(count($commands), count(array_unique($commands)));
        $this->assertSame(1, count(array_filter($commands, static fn (string $command): bool => 'subject' === $command)));
    }
}
