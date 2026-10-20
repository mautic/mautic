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
            SearchScopePresets::isMine(),
            SearchScopePresets::isUncategorized(),
            SearchScopePresets::project(),
        ];
    }

    protected function getAdditionalCommandKeys(): array
    {
        $excluded = [
            'mautic.email.email.searchcommand.isexpired',
            'mautic.email.email.searchcommand.ispending',
        ];

        return array_values(array_filter(
            $this->emailModel->getCommandList(),
            static fn (string $key): bool => !in_array($key, $excluded, true)
        ));
    }
}
