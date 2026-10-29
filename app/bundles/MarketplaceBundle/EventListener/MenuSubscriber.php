<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Mautic\CoreBundle\Event\MenuEvent;
use Mautic\MarketplaceBundle\Security\Permissions\MarketplacePermissions;
use Mautic\MarketplaceBundle\Service\Config;
use Mautic\MarketplaceBundle\Service\RouteProvider;

final readonly class MenuSubscriber
{
    public function __construct(
        private Config $config,
    ) {
    }

    #[AsEventListener(event: MenuEvent::class, priority: 9999)]
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
