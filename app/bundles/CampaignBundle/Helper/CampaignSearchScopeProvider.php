<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Helper;

use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Helper\AbstractSearchScopeProvider;
use Mautic\CoreBundle\Helper\SearchScopePresets;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CampaignSearchScopeProvider extends AbstractSearchScopeProvider
{
    public function __construct(
        private readonly CampaignModel $campaignModel,
        TranslatorInterface $translator,
    ) {
        parent::__construct($translator);
    }

    protected function getPinnedScopes(): array
    {
        return [
            SearchScopePresets::standard(),
            SearchScopePresets::category(),
            SearchScopePresets::ids(),
            SearchScopePresets::isPublished(),
            SearchScopePresets::isUnpublished(),
            SearchScopePresets::isUncategorized(),
            SearchScopePresets::isMine(),
            SearchScopePresets::project(),
        ];
    }

    protected function getAdditionalCommandKeys(): array
    {
        $excluded = [
            'mautic.campaign.campaign.searchcommand.isexpired',
            'mautic.campaign.campaign.searchcommand.ispending',
        ];

        return array_values(array_filter(
            $this->campaignModel->getCommandList(),
            static fn (string $key): bool => !in_array($key, $excluded, true)
        ));
    }
}
