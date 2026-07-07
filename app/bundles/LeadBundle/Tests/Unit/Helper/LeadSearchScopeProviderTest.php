<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Unit\Helper;

use Mautic\LeadBundle\Helper\LeadSearchScopeProvider;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class LeadSearchScopeProviderTest extends TestCase
{
    private LeadModel&MockObject $leadModel;

    private FieldModel&MockObject $fieldModel;

    private TranslatorInterface&MockObject $translator;

    private LeadSearchScopeProvider $provider;

    protected function setUp(): void
    {
        $this->leadModel  = $this->createMock(LeadModel::class);
        $this->fieldModel = $this->createMock(FieldModel::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.lead.field.instagram' => 'Instagram',
                'mautic.lead.field.firstname' => 'First Name',
                'mautic.lead.lead.searchcommand.list' => 'segment',
                'mautic.core.searchcommand.ismine' => 'is:mine',
                default => $key,
            });

        $this->fieldModel->method('getFieldList')
            ->willReturn([
                'instagram' => 'Instagram',
                'custom_field' => 'My Custom Field',
            ]);

        $this->leadModel->method('getCommandList')
            ->willReturn([
                'mautic.lead.lead.searchcommand.list',
                'instagram',
                'custom_field',
                'mautic.core.searchcommand.ismine',
            ]);

        $this->provider = new LeadSearchScopeProvider(
            $this->leadModel,
            $this->fieldModel,
            $this->translator
        );
    }

    public function testGetScopesIncludesSocialAndCustomFields(): void
    {
        $scopes = $this->provider->getScopes();

        $commands = array_column($scopes, 'command');

        $this->assertContains('instagram', $commands);
        $this->assertContains('custom_field', $commands);
        $this->assertSame('', $scopes[0]['command']);
        $this->assertTrue($scopes[0]['default'] ?? false);
    }

    public function testGetScopesDoesNotDuplicatePinnedCommands(): void
    {
        $scopes = $this->provider->getScopes();

        $commands = array_column($scopes, 'command');

        $this->assertSame(count($commands), count(array_unique($commands)));
        $this->assertSame(1, count(array_filter($commands, static fn (string $command): bool => 'segment' === $command)));
    }
}
