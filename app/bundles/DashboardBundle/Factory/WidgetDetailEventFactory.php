<?php

declare(strict_types=1);

namespace Mautic\DashboardBundle\Factory;

use Mautic\CacheBundle\Cache\CacheProviderTagAwareInterface;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\DashboardBundle\Entity\Widget;
use Mautic\DashboardBundle\Event\GenerateWidgetDetailEvent;
use Mautic\DashboardBundle\Event\PreLoadWidgetDetailEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class WidgetDetailEventFactory
{
    public function __construct(
        private TranslatorInterface $translator,
        private CacheProviderTagAwareInterface $cacheProvider,
        private CorePermissions $corePermissions,
    ) {
    }

    public function createPreLoad(Widget $widget): PreLoadWidgetDetailEvent
    {
        return new PreLoadWidgetDetailEvent($this->translator, $this->corePermissions, $widget, $this->cacheProvider);
    }

    public function createGenerate(Widget $widget): GenerateWidgetDetailEvent
    {
        return new GenerateWidgetDetailEvent($this->translator, $this->corePermissions, $widget, $this->cacheProvider);
    }
}
