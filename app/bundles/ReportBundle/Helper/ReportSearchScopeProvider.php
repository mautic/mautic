<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Helper;

use Mautic\CoreBundle\Helper\AbstractSearchScopeProvider;
use Mautic\CoreBundle\Helper\SearchScopePresets;
use Mautic\ReportBundle\Model\ReportModel;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ReportSearchScopeProvider extends AbstractSearchScopeProvider
{
    public function __construct(
        private readonly ReportModel $reportModel,
        TranslatorInterface $translator,
    ) {
        parent::__construct($translator);
    }

    protected function getPinnedScopes(): array
    {
        return [
            SearchScopePresets::standard(),
            SearchScopePresets::ids(),
            SearchScopePresets::isPublished(),
            SearchScopePresets::isUnpublished(),
            SearchScopePresets::isMine(),
        ];
    }

    protected function getAdditionalCommandKeys(): array
    {
        return $this->reportModel->getCommandList();
    }
}
