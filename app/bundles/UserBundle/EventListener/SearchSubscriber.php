<?php

namespace Mautic\UserBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\DTO\GlobalSearchFilterDTO;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\GlobalSearch;
use Mautic\UserBundle\Model\RoleModel;
use Mautic\UserBundle\Model\UserModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SearchSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private UserModel $userModel,
        private RoleModel $userRoleModel,
        private CorePermissions $security,
        private GlobalSearch $globalSearch,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::GLOBAL_SEARCH => [
                ['onGlobalSearchUser', 0],
                ['onGlobalSearchRoles', 0],
            ],
            CoreEvents::BUILD_COMMAND_LIST => ['onBuildCommandList', 0],
        ];
    }

    public function onGlobalSearchUser(MauticEvents\GlobalSearchEvent $event): void
    {
        $filterDTO = new GlobalSearchFilterDTO($event->getSearchString());
        $results   = $this->globalSearch->performSearch(
            $filterDTO,
            $this->userModel,
            '@MauticUser/SubscribedEvents/Search/global_user.html.twig'
        );

        if (!empty($results)) {
            $event->addResults('mautic.user.users', $results);
        }
    }

    public function onGlobalSearchRoles(MauticEvents\GlobalSearchEvent $event): void
    {
        $filterDTO = new GlobalSearchFilterDTO($event->getSearchString());
        $results   = $this->globalSearch->performSearch(
            $filterDTO,
            $this->userRoleModel,
            '@MauticUser/SubscribedEvents/Search/global_role.html.twig'
        );

        if (!empty($results)) {
            $event->addResults('mautic.user.roles', $results);
        }
    }

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
