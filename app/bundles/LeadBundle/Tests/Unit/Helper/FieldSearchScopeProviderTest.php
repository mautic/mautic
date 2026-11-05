<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Tests\Unit\Helper\SearchScopeProviderTestCase;
use Mautic\LeadBundle\Helper\FieldSearchScopeProvider;
use Mautic\LeadBundle\Model\FieldModel;
use Symfony\Contracts\Translation\TranslatorInterface;

final class FieldSearchScopeProviderTest extends SearchScopeProviderTestCase
{
    protected function createProvider(): FieldSearchScopeProvider
    {
        $fieldModel = $this->createMock(FieldModel::class);
        $translator = $this->createMock(TranslatorInterface::class);

        $translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.lead.field.searchcommand.isindexed' => 'is:indexed',
                'mautic.lead.field.searchcommand.type' => 'type',
                'mautic.core.searchcommand.ismine' => 'is:mine',
                default => $key,
            });

        $fieldModel->method('getCommandList')
            ->willReturn([
                'mautic.core.searchcommand.ispublished',
                'mautic.lead.field.searchcommand.isindexed',
                'mautic.lead.field.searchcommand.type',
                'mautic.core.searchcommand.ismine',
            ]);

        return new FieldSearchScopeProvider($fieldModel, $translator);
    }

    protected function expectedDynamicCommands(): array
    {
        return ['is:indexed'];
    }
}
