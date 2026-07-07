<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\AbstractSearchScopeProvider;
use Mautic\CoreBundle\Tests\Unit\Helper\SearchScopeProviderTestCase;
use Mautic\UserBundle\Helper\UserSearchScopeProvider;
use Mautic\UserBundle\Model\UserModel;
use Symfony\Contracts\Translation\TranslatorInterface;

final class UserSearchScopeProviderTest extends SearchScopeProviderTestCase
{
    protected function createProvider(): AbstractSearchScopeProvider
    {
        $userModel  = $this->createMock(UserModel::class);
        $translator = $this->createMock(TranslatorInterface::class);

        $translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.core.searchcommand.name' => 'name',
                'mautic.user.user.searchcommand.isadmin' => 'is:admin',
                'mautic.core.searchcommand.email' => 'email',
                default => $key,
            });

        $userModel->method('getCommandList')
            ->willReturn([
                'mautic.core.searchcommand.email',
                'mautic.user.user.searchcommand.isadmin',
                'mautic.core.searchcommand.name',
            ]);

        return new UserSearchScopeProvider($userModel, $translator);
    }

    protected function expectedDynamicCommands(): array
    {
        return ['is:admin'];
    }
}
