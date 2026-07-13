<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Helper;

use Mautic\CoreBundle\Helper\AbstractSearchScopeProvider;
use Mautic\CoreBundle\Helper\SearchScopePresets;
use Mautic\PageBundle\Model\PageModel;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PageSearchScopeProvider extends AbstractSearchScopeProvider
{
    public function __construct(
        private readonly PageModel $pageModel,
        TranslatorInterface $translator,
    ) {
        parent::__construct($translator);
    }

    protected function getPinnedScopes(): array
    {
        return [
            SearchScopePresets::standard(),
            SearchScopePresets::lang(),
            SearchScopePresets::category(),
            SearchScopePresets::ids(),
            SearchScopePresets::isPublished(),
            SearchScopePresets::isUnpublished(),
            SearchScopePresets::isUncategorized(),
            SearchScopePresets::isMine(),
            SearchScopePresets::command('mautic.page.searchcommand.isprefcenter'),
            SearchScopePresets::project(),
        ];
    }

    protected function getAdditionalCommandKeys(): array
    {
        $excluded = [
            'mautic.page.searchcommand.isexpired',
            'mautic.page.searchcommand.ispending',
        ];

        return array_values(array_filter(
            $this->pageModel->getCommandList(),
            static fn (string $key): bool => !in_array($key, $excluded, true)
        ));
    }
}
