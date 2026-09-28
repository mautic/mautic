<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Model\SearchCommandListInterface;
use Mautic\CoreBundle\Tests\Unit\Helper\SearchScopeProviderTestCase;
use Mautic\UserBundle\Helper\RoleSearchScopeProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

final class RoleSearchScopeProviderTest extends SearchScopeProviderTestCase
{
    protected function createProvider(): RoleSearchScopeProvider
    {
        $roleModel  = $this->createMock(SearchCommandListInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);

        $translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.core.searchcommand.name' => 'name',
                'mautic.user.user.searchcommand.isadmin' => 'is:admin',
                'mautic.core.searchcommand.ids' => 'ids',
                default => $key,
            });

        $roleModel->method('getCommandList')
            ->willReturn([
                'mautic.user.user.searchcommand.isadmin',
                'mautic.core.searchcommand.name',
                'mautic.core.searchcommand.ids',
            ]);

        return new RoleSearchScopeProvider($roleModel, $translator);
    }

    protected function expectedDynamicCommands(): array
    {
        return ['is:admin'];
    }
}
