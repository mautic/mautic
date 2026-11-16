<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Helper;

use Mautic\CoreBundle\Helper\AbstractSearchScopeProvider;
use Mautic\CoreBundle\Helper\SearchScopePresets;
use Mautic\FormBundle\Model\FormModel;
use Symfony\Contracts\Translation\TranslatorInterface;

final class FormSearchScopeProvider extends AbstractSearchScopeProvider
{
    public function __construct(
        private readonly FormModel $formModel,
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
            SearchScopePresets::command('mautic.form.form.searchcommand.hasresults'),
            SearchScopePresets::project(),
        ];
    }

    protected function getAdditionalCommandKeys(): array
    {
        $excluded = [
            'mautic.form.form.searchcommand.isexpired',
            'mautic.form.form.searchcommand.ispending',
        ];

        return array_values(array_filter(
            $this->formModel->getCommandList(),
            static fn (string $key): bool => !in_array($key, $excluded, true)
        ));
    }
}
