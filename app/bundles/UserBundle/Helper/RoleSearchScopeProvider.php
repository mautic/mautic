<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Helper;

use Mautic\CoreBundle\Helper\AbstractSearchScopeProvider;
use Mautic\CoreBundle\Helper\SearchScopePresets;
use Mautic\CoreBundle\Model\SearchCommandListInterface;
use Mautic\UserBundle\Model\RoleModel;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Translation\TranslatorInterface;

final class RoleSearchScopeProvider extends AbstractSearchScopeProvider
{
    public function __construct(
        #[Autowire(service: RoleModel::class)]
        private readonly SearchCommandListInterface $roleModel,
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
