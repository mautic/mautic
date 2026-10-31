<?php

declare(strict_types=1);

namespace MauticPlugin\MauticTagManagerBundle\Helper;

use Mautic\CoreBundle\Helper\AbstractSearchScopeProvider;
use Mautic\CoreBundle\Helper\SearchScopePresets;
use Mautic\LeadBundle\Entity\TagRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

final class TagSearchScopeProvider extends AbstractSearchScopeProvider
{
    public function __construct(
        private readonly TagRepository $tagRepository,
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
        return $this->tagRepository->getSearchCommands();
    }
}
