<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\AbstractSearchScopeProvider;
use Mautic\CoreBundle\Tests\Unit\Helper\SearchScopeProviderTestCase;
use Mautic\FormBundle\Helper\FormSearchScopeProvider;
use Mautic\FormBundle\Model\FormModel;
use Symfony\Contracts\Translation\TranslatorInterface;

final class FormSearchScopeProviderTest extends SearchScopeProviderTestCase
{
    protected function createProvider(): AbstractSearchScopeProvider
    {
        $formModel  = $this->createMock(FormModel::class);
        $translator = $this->createMock(TranslatorInterface::class);

        $translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.core.searchcommand.name' => 'name',
                'mautic.form.form.searchcommand.hasresults' => 'has:results',
                'mautic.core.searchcommand.ismine' => 'is:mine',
                default => $key,
            });

        $formModel->method('getCommandList')
            ->willReturn([
                'mautic.core.searchcommand.name',
                'mautic.form.form.searchcommand.hasresults',
                'mautic.core.searchcommand.ismine',
            ]);

        return new FormSearchScopeProvider($formModel, $translator);
    }

    protected function expectedDynamicCommands(): array
    {
        return ['has:results'];
    }
}
