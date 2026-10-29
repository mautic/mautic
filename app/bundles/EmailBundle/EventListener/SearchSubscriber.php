<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\EventListener;

use Mautic\CoreBundle\DTO\GlobalSearchFilterDTO;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\GlobalSearch;
use Mautic\EmailBundle\Model\EmailModel;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class SearchSubscriber
{
    public function __construct(
        private EmailModel $emailModel,
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
            $this->emailModel,
            '@MauticEmail/SubscribedEvents/Search/global.html.twig'
        );

        if ([] !== $results) {
            $event->addResults('mautic.email.emails', $results);
        }
    }

    #[AsEventListener(
        event: MauticEvents\CommandListEvent::class,
        priority: 0
    )]
    public function onBuildCommandList(MauticEvents\CommandListEvent $event): void
    {
        if ($this->security->isGranted(['email:emails:viewown', 'email:emails:viewother'], 'MATCH_ONE')) {
            $event->addCommands(
                'mautic.email.emails',
                $this->emailModel->getCommandList()
            );
        }
    }
}
