<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Helper;

use Mautic\CoreBundle\Helper\AbstractSearchScopeProvider;
use Mautic\CoreBundle\Helper\SearchScopePresets;
use Mautic\CoreBundle\Model\FormModel as CommonFormModel;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PointInsightSearchScopeProvider extends AbstractSearchScopeProvider
{
    /**
     * @param CommonFormModel<object> $insightModel
     */
    public function __construct(
        private readonly CommonFormModel $insightModel,
        TranslatorInterface $translator,
    ) {
        parent::__construct($translator);
    }

    protected function getPinnedScopes(): array
    {
        return [
            SearchScopePresets::standard(),
            SearchScopePresets::ids(),
        ];
    }

    protected function getAdditionalCommandKeys(): array
    {
        return $this->insightModel->getCommandList();
    }
}
