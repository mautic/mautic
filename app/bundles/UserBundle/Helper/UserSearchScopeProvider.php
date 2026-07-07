<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Helper;

use Mautic\CoreBundle\Helper\AbstractSearchScopeProvider;
use Mautic\CoreBundle\Helper\SearchScopePresets;
use Mautic\UserBundle\Model\UserModel;
use Symfony\Contracts\Translation\TranslatorInterface;

final class UserSearchScopeProvider extends AbstractSearchScopeProvider
{
    public function __construct(
        private UserModel $userModel,
        TranslatorInterface $translator,
    ) {
        parent::__construct($translator);
    }

    protected function getPinnedScopes(): array
    {
        return [
            SearchScopePresets::standard(),
            SearchScopePresets::name(),
            SearchScopePresets::email(),
            SearchScopePresets::command('mautic.user.user.searchcommand.username'),
            SearchScopePresets::command('mautic.user.user.searchcommand.role'),
            SearchScopePresets::command('mautic.user.user.searchcommand.position'),
            SearchScopePresets::command('mautic.user.user.searchcommand.isadmin'),
            SearchScopePresets::isPublished(),
            SearchScopePresets::isUnpublished(),
            SearchScopePresets::ids(),
        ];
    }

    protected function getAdditionalCommandKeys(): array
    {
        return $this->userModel->getCommandList();
    }
}
