<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\MenuEvent;
use Mautic\MarketplaceBundle\Security\Permissions\MarketplacePermissions;
use Mautic\MarketplaceBundle\Service\Config;
use Mautic\MarketplaceBundle\Service\RouteProvider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class MenuSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Config $config,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::BUILD_MENU => ['onBuildMenu', 9999],
        ];
    }

    public function onBuildMenu(MenuEvent $event): void
    {
        if ('admin' !== $event->getType() || !$this->config->marketplaceIsEnabled()) {
            return;
        }

        $event->addMenuItems(
            [
                'priority' => 81,
                'items'    => [
                    'marketplace.title' => [
                        'id'        => 'marketplace',
                        'route'     => RouteProvider::ROUTE_LIST,
                        'access'    => MarketplacePermissions::CAN_VIEW_PACKAGES,
                        'parent'    => 'mautic.core.integrations',
                        'iconClass' => 'ri-shopping-bag-2-line',
                        'priority'  => 16,
                    ],
                ],
            ]
        );
    }
}
