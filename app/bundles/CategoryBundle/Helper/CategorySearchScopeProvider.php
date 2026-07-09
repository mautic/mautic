<?php

declare(strict_types=1);

namespace Mautic\CategoryBundle\Helper;

use Mautic\CategoryBundle\Model\CategoryModel;
use Mautic\CoreBundle\Helper\AbstractSearchScopeProvider;
use Mautic\CoreBundle\Helper\SearchScopePresets;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CategorySearchScopeProvider extends AbstractSearchScopeProvider
{
    public function __construct(
        private readonly CategoryModel $categoryModel,
        TranslatorInterface $translator,
    ) {
        parent::__construct($translator);
    }

    protected function getPinnedScopes(): array
    {
        return [
            SearchScopePresets::standard(),
            SearchScopePresets::isPublished(),
            SearchScopePresets::isUnpublished(),
            SearchScopePresets::ids(),
        ];
    }

    protected function getAdditionalCommandKeys(): array
    {
        return $this->categoryModel->getCommandList();
    }
}
