<?php

declare(strict_types=1);

namespace Mautic\DashboardBundle\Widget;

use Mautic\CacheBundle\Cache\CacheProvider;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\DashboardBundle\Entity\Widget;
use Mautic\DashboardBundle\Event\WidgetDetailEvent;
use Symfony\Component\Translation\TranslatorInterface;

class WidgetDetailEventFactory
{
    private TranslatorInterface $translator;

    private CacheProvider $cacheProvider;

    private CorePermissions $corePermissions;

    private UserHelper $userHelper;

    public function __construct(
        TranslatorInterface $translator,
        CacheProvider $cacheProvider,
        CorePermissions $corePermissions,
        UserHelper $userHelper
    ) {
        $this->translator      = $translator;
        $this->cacheProvider   = $cacheProvider;
        $this->corePermissions = $corePermissions;
        $this->userHelper      = $userHelper;
    }

    public function create(Widget $widget): WidgetDetailEvent
    {
        $event = new WidgetDetailEvent($this->translator, $this->cacheProvider);
        $event->setWidget($widget);
        $event->setCacheDir(false, $this->userHelper->getUser()->getId());
        $event->setSecurity($this->corePermissions);

        return $event;
    }
}