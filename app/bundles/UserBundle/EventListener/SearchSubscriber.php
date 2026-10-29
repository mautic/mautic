<?php

declare(strict_types=1);

namespace Mautic\UserBundle\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Mautic\CoreBundle\DTO\GlobalSearchFilterDTO;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\GlobalSearch;
use Mautic\UserBundle\Model\RoleModel;
use Mautic\UserBundle\Model\UserModel;

final readonly class SearchSubscriber
{
    public function __construct(
        private UserModel $userModel,
        private RoleModel $userRoleModel,
        private CorePermissions $security,
        private GlobalSearch $globalSearch,
    ) {
    }

    #[AsEventListener(event: MauticEvents\GlobalSearchEvent::class)]
    public function onGlobalSearchUser(MauticEvents\GlobalSearchEvent $event): void
    {
        $filterDTO = new GlobalSearchFilterDTO($event->getSearchString());
        $results   = $this->globalSearch->performSearch(
            $filterDTO,
            $this->userModel,
            '@MauticUser/SubscribedEvents/Search/global_user.html.twig'
        );

        if ([] !== $results) {
            $event->addResults('mautic.user.users', $results);
        }
    }

    #[AsEventListener(event: MauticEvents\GlobalSearchEvent::class)]
    public function onGlobalSearchRoles(MauticEvents\GlobalSearchEvent $event): void
    {
        $filterDTO = new GlobalSearchFilterDTO($event->getSearchString());
        $results   = $this->globalSearch->performSearch(
            $filterDTO,
            $this->userRoleModel,
            '@MauticUser/SubscribedEvents/Search/global_role.html.twig'
        );

        if ([] !== $results) {
            $event->addResults('mautic.user.roles', $results);
        }
    }

    #[AsEventListener(event: MauticEvents\CommandListEvent::class)]
    public function onBuildCommandList(MauticEvents\CommandListEvent $event): void
    {
        if ($this->security->isGranted('user:users:view')) {
            $event->addCommands(
                'mautic.user.users',
                $this->userModel->getCommandList()
            );
        }
        if ($this->security->isGranted('user:roles:view')) {
            $event->addCommands(
                'mautic.user.roles',
                $this->userRoleModel->getCommandList()
            );
        }
    }
}
