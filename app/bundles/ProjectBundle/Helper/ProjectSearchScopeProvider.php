<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Helper;

use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\AbstractSearchScopeProvider;
use Mautic\CoreBundle\Helper\SearchScopePresets;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ProjectSearchScopeProvider extends AbstractSearchScopeProvider
{
    /**
     * @param CommonRepository<object> $projectRepository
     */
    public function __construct(
        private readonly CommonRepository $projectRepository,
        TranslatorInterface $translator,
    ) {
        parent::__construct($translator);
    }

    protected function getPinnedScopes(): array
    {
        return [
            SearchScopePresets::standard(),
            SearchScopePresets::ids(),
        ];
    }

    protected function getAdditionalCommandKeys(): array
    {
        return $this->projectRepository->getSearchCommands();
    }
}
