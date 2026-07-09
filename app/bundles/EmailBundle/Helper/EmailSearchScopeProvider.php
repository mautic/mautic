<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Helper;

use Mautic\CoreBundle\Helper\AbstractSearchScopeProvider;
use Mautic\CoreBundle\Helper\SearchScopePresets;
use Mautic\EmailBundle\Model\EmailModel;
use Symfony\Contracts\Translation\TranslatorInterface;

final class EmailSearchScopeProvider extends AbstractSearchScopeProvider
{
    public function __construct(
        private readonly EmailModel $emailModel,
        TranslatorInterface $translator,
    ) {
        parent::__construct($translator);
    }

    protected function getPinnedScopes(): array
    {
        return [
            SearchScopePresets::standard(),
            SearchScopePresets::command('mautic.email.email.searchcommand.subject'),
            SearchScopePresets::name(),
            SearchScopePresets::lang(),
            SearchScopePresets::category(),
            SearchScopePresets::isPublished(),
            SearchScopePresets::isUnpublished(),
            SearchScopePresets::command('mautic.email.email.searchcommand.isexpired'),
            SearchScopePresets::command('mautic.email.email.searchcommand.ispending'),
            SearchScopePresets::isMine(),
            SearchScopePresets::isUncategorized(),
            SearchScopePresets::project(),
        ];
    }

    protected function getAdditionalCommandKeys(): array
    {
        return $this->emailModel->getCommandList();
    }
}
