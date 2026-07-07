<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper;

use Symfony\Contracts\Translation\TranslatorInterface;

final class ThemeSearchScopeProvider extends AbstractSearchScopeProvider
{
    public function __construct(TranslatorInterface $translator)
    {
        parent::__construct($translator);
    }

    protected function getPinnedScopes(): array
    {
        return [
            SearchScopePresets::standard(),
            SearchScopePresets::command('mautic.core.theme.searchcommand.feature'),
            SearchScopePresets::command('mautic.core.theme.searchcommand.builder'),
        ];
    }
}
