<?php

declare(strict_types=1);

namespace Mautic\WebhookBundle\Helper;

use Mautic\CoreBundle\Helper\AbstractSearchScopeProvider;
use Mautic\CoreBundle\Helper\SearchScopePresets;
use Mautic\WebhookBundle\Model\WebhookModel;
use Symfony\Contracts\Translation\TranslatorInterface;

final class WebhookSearchScopeProvider extends AbstractSearchScopeProvider
{
    public function __construct(
        private readonly WebhookModel $webhookModel,
        TranslatorInterface $translator,
    ) {
        parent::__construct($translator);
    }

    protected function getPinnedScopes(): array
    {
        return [
            SearchScopePresets::standard(),
            SearchScopePresets::name(),
            SearchScopePresets::category(),
            SearchScopePresets::ids(),
            SearchScopePresets::isPublished(),
            SearchScopePresets::isUnpublished(),
            SearchScopePresets::isUncategorized(),
            SearchScopePresets::isMine(),
        ];
    }

    protected function getAdditionalCommandKeys(): array
    {
        return $this->webhookModel->getCommandList();
    }
}
