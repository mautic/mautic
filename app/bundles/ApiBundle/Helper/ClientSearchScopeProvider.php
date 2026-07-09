<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\Helper;

use Mautic\ApiBundle\Model\ClientModel;
use Mautic\CoreBundle\Helper\AbstractSearchScopeProvider;
use Mautic\CoreBundle\Helper\SearchScopePresets;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ClientSearchScopeProvider extends AbstractSearchScopeProvider
{
    public function __construct(
        private readonly ClientModel $clientModel,
        TranslatorInterface $translator,
    ) {
        parent::__construct($translator);
    }

    protected function getPinnedScopes(): array
    {
        return [
            SearchScopePresets::standard(),
            SearchScopePresets::name(),
            SearchScopePresets::command('mautic.api.client.searchcommand.callback'),
            SearchScopePresets::command('mautic.api.client.searchcommand.redirecturi'),
            SearchScopePresets::ids(),
        ];
    }

    protected function getAdditionalCommandKeys(): array
    {
        return $this->clientModel->getCommandList();
    }
}
