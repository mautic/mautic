<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Unit\Helper;

use Mautic\FormBundle\Helper\FormSearchScopeProvider;
use Mautic\FormBundle\Model\FormModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class FormSearchScopeProviderTest extends TestCase
{
    private FormModel&MockObject $formModel;

    private TranslatorInterface&MockObject $translator;

    private FormSearchScopeProvider $provider;

    protected function setUp(): void
    {
        $this->formModel  = $this->createMock(FormModel::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.core.searchcommand.name' => 'name',
                'mautic.form.form.searchcommand.hasresults' => 'has:results',
                'mautic.core.searchcommand.ismine' => 'is:mine',
                default => $key,
            });

        $this->formModel->method('getCommandList')
            ->willReturn([
                'mautic.core.searchcommand.name',
                'mautic.form.form.searchcommand.hasresults',
                'mautic.core.searchcommand.ismine',
            ]);

        $this->provider = new FormSearchScopeProvider(
            $this->formModel,
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

        $this->assertContains('has:results', $commands);
        $this->assertCount(count(array_unique($commands)), $commands);
    }
}
