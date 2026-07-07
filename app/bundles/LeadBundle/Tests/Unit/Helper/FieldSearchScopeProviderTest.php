<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Unit\Helper;

use Mautic\LeadBundle\Helper\FieldSearchScopeProvider;
use Mautic\LeadBundle\Model\FieldModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class FieldSearchScopeProviderTest extends TestCase
{
    private FieldModel&MockObject $fieldModel;

    private TranslatorInterface&MockObject $translator;

    private FieldSearchScopeProvider $provider;

    protected function setUp(): void
    {
        $this->fieldModel = $this->createMock(FieldModel::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.lead.field.searchcommand.isindexed' => 'is:indexed',
                'mautic.lead.field.searchcommand.type' => 'type',
                'mautic.core.searchcommand.ismine' => 'is:mine',
                default => $key,
            });

        $this->fieldModel->method('getCommandList')
            ->willReturn([
                'mautic.core.searchcommand.ispublished',
                'mautic.lead.field.searchcommand.isindexed',
                'mautic.lead.field.searchcommand.type',
                'mautic.core.searchcommand.ismine',
            ]);

        $this->provider = new FieldSearchScopeProvider(
            $this->fieldModel,
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

        $this->assertContains('is:indexed', $commands);
        $this->assertSame(count($commands), count(array_unique($commands)));
    }
}
