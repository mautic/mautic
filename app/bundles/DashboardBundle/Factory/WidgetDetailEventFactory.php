<?php

declare(strict_types=1);

namespace Mautic\DashboardBundle\Factory;

use Mautic\CacheBundle\Cache\CacheProviderTagAwareInterface;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\DashboardBundle\Entity\Widget;
use Mautic\DashboardBundle\Event\WidgetDetailEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

class WidgetDetailEventFactory
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly CacheProviderTagAwareInterface $cacheProvider,
        private readonly CorePermissions $corePermissions,
    ) {
    }

    public function create(Widget $widget): WidgetDetailEvent
    {
        return new WidgetDetailEvent($this->translator, $this->corePermissions, $widget, $this->cacheProvider);
    }
}
