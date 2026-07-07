<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Helper;

use Mautic\CoreBundle\Helper\AbstractSearchScopeProvider;
use Mautic\CoreBundle\Helper\SearchScopePresets;
use Mautic\ProjectBundle\Entity\ProjectRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ProjectSearchScopeProvider extends AbstractSearchScopeProvider
{
    public function __construct(
        private ProjectRepository $projectRepository,
        TranslatorInterface $translator,
    ) {
        parent::__construct($translator);
    }

    protected function getPinnedScopes(): array
    {
        return [
            SearchScopePresets::standard(),
            SearchScopePresets::name(),
            SearchScopePresets::ids(),
            SearchScopePresets::isMine(),
        ];
    }

    protected function getAdditionalCommandKeys(): array
    {
        return $this->projectRepository->getSearchCommands();
    }
}
