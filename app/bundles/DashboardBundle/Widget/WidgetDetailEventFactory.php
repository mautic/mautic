<?php

/*
 * @copyright   2019 Mautic Inc. All rights reserved
  *
 * @link        http://mautic.com
 * @created     15.1.19
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DashboardBundle\Widget;

use Mautic\CacheBundle\Cache\CacheProvider;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\DashboardBundle\Entity\Widget;
use Mautic\DashboardBundle\Event\WidgetDetailEvent;
use Symfony\Component\Translation\TranslatorInterface;

class WidgetDetailEventFactory
{
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var CacheProvider
     */
    private $cacheProvider;
    /**
     * @var CorePermissions|null
     */
    private $corePermissions;
    /**
     * @var UserHelper
     */
    private $userHelper;

    /**
     * WidgetDetailEventFactory constructor.
     *
     * @param TranslatorInterface  $translator
     * @param CacheProvider        $cacheProvider
     * @param CorePermissions|null $corePermissions
     * @param UserHelper           $userHelper
     */
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

    public function create(Widget $widget, ?string $cacheId)
    {
        $event = new WidgetDetailEvent($this->translator, $this->cacheProvider);
        $event->setWidget($widget);
        $event->setCacheDir(false, $this->userHelper->getUser()->getId());
        $event->setSecurity($this->corePermissions);
    }
}
