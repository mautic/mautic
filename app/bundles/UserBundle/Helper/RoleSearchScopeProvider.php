<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Helper;

use Mautic\CoreBundle\Helper\AbstractSearchScopeProvider;
use Mautic\CoreBundle\Helper\SearchScopePresets;
use Mautic\CoreBundle\Model\FormModel;
use Symfony\Contracts\Translation\TranslatorInterface;

final class RoleSearchScopeProvider extends AbstractSearchScopeProvider
{
    /**
     * @param FormModel<object> $roleModel
     */
    public function __construct(
        private readonly FormModel $roleModel,
        TranslatorInterface $translator,
    ) {
        parent::__construct($translator);
    }

    protected function getPinnedScopes(): array
    {
        return [
            SearchScopePresets::standard(),
            SearchScopePresets::name(),
            SearchScopePresets::command('mautic.user.user.searchcommand.isadmin'),
            SearchScopePresets::ids(),
        ];
    }

    protected function getAdditionalCommandKeys(): array
    {
        return $this->roleModel->getCommandList();
    }
}
