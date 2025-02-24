<?php

declare(strict_types=1);

namespace Mautic\DashboardBundle\Factory;

use Mautic\CacheBundle\Cache\CacheProvider;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\DashboardBundle\Entity\Widget;
use Mautic\DashboardBundle\Event\WidgetDetailEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

class WidgetDetailEventFactory
{
    public function __construct(
        private TranslatorInterface $translator,
        private CacheProvider $cacheProvider,
        private CorePermissions $corePermissions,
        private UserHelper $userHelper,
        private CoreParametersHelper $coreParametersHelper,
        private PathsHelper $pathsHelper,
    ) {
    }

    public function create(Widget $widget): WidgetDetailEvent
    {
        $cacheDir = $this->coreParametersHelper->get('cached_data_dir', $this->pathsHelper->getSystemPath('cache', true));
        $event    = new WidgetDetailEvent($this->translator, $this->corePermissions, $widget, $this->cacheProvider);
        $event->setCacheDir($cacheDir, $this->userHelper->getUser()->getId());

        return $event;
    }
}
