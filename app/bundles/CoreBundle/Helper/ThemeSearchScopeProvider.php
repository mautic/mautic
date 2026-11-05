<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper;

final class ThemeSearchScopeProvider extends AbstractSearchScopeProvider
{
    protected function getPinnedScopes(): array
    {
        return [
            SearchScopePresets::standard(),
            SearchScopePresets::command('mautic.core.theme.searchcommand.feature'),
            SearchScopePresets::command('mautic.core.theme.searchcommand.builder'),
        ];
    }
}
