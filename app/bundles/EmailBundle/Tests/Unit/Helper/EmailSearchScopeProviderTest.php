<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Tests\Unit\Helper\SearchScopeProviderTestCase;
use Mautic\EmailBundle\Helper\EmailSearchScopeProvider;
use Mautic\EmailBundle\Model\EmailModel;
use Symfony\Contracts\Translation\TranslatorInterface;

final class EmailSearchScopeProviderTest extends SearchScopeProviderTestCase
{
    protected function createProvider(): EmailSearchScopeProvider
    {
        $emailModel = $this->createMock(EmailModel::class);
        $translator = $this->createMock(TranslatorInterface::class);

        $translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.email.email.searchcommand.subject' => 'subject',
                'mautic.email.email.searchcommand.subject.label' => 'Subject',
                'mautic.core.searchcommand.ids' => 'ids',
                'mautic.core.searchcommand.ismine' => 'is:mine',
                default => $key,
            });

        $emailModel->method('getCommandList')
            ->willReturn([
                'mautic.email.email.searchcommand.subject',
                'mautic.core.searchcommand.ids',
                'mautic.core.searchcommand.ismine',
            ]);

        return new EmailSearchScopeProvider($emailModel, $translator);
    }

    protected function expectedDynamicCommands(): array
    {
        return ['ids'];
    }

    public function testGetScopesUsesFriendlyLabelWhenAvailable(): void
    {
        $scopes       = $this->getScopes();
        $subjectScope = array_values(array_filter($scopes, static fn (array $scope): bool => 'subject' === $scope['command']))[0];

        self::assertSame('mautic.email.email.searchcommand.subject', $subjectScope['label']);
    }

    public function testSubjectCommandAppearsExactlyOnce(): void
    {
        $commands = array_column($this->getScopes(), 'command');

        self::assertCount(1, array_filter($commands, static fn (string $command): bool => 'subject' === $command));
    }
}
