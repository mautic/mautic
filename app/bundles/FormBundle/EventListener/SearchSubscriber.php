<?php

declare(strict_types=1);

namespace Mautic\FormBundle\EventListener;

use Mautic\CoreBundle\DTO\GlobalSearchFilterDTO;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\GlobalSearch;
use Mautic\FormBundle\Model\FormModel;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class SearchSubscriber
{
    public function __construct(
        private FormModel $formModel,
        private CorePermissions $security,
        private GlobalSearch $globalSearch,
    ) {
    }

    #[AsEventListener(
        event: MauticEvents\GlobalSearchEvent::class,
        priority: 0
    )]
    public function onGlobalSearch(MauticEvents\GlobalSearchEvent $event): void
    {
        $filterDTO = new GlobalSearchFilterDTO($event->getSearchString());
        $results   = $this->globalSearch->performSearch(
            $filterDTO,
            $this->formModel,
            '@MauticForm/SubscribedEvents/Search/global.html.twig'
        );

        if ([] !== $results) {
            $event->addResults('mautic.campaign.campaigns', $results);
        }
    }

    #[AsEventListener(
        event: MauticEvents\CommandListEvent::class,
        priority: 0
    )]
    public function onBuildCommandList(MauticEvents\CommandListEvent $event): void
    {
        if ($this->security->isGranted(['form:forms:viewown', 'form:forms:viewother'], 'MATCH_ONE')) {
            $event->addCommands(
                'mautic.form.forms',
                $this->formModel->getCommandList()
            );
        }
    }
}
