<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomAssetsEvent;
use Mautic\CoreBundle\Templating\Helper\AssetsHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class AssetsSubscriber implements EventSubscriberInterface
{
    /**
     * @var AssetsHelper
     */
    private $assetsHelper;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(AssetsHelper $assetsHelper, EventDispatcherInterface $dispatcher)
    {
        $this->assetsHelper = $assetsHelper;
        $this->dispatcher   = $dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['fetchCustomAssets', 0],
        ];
    }

    public function fetchCustomAssets(GetResponseEvent $event)
    {
        if ($event->isMasterRequest() && $this->dispatcher->hasListeners(CoreEvents::VIEW_INJECT_CUSTOM_ASSETS)) {
            $this->dispatcher->dispatch(
                CoreEvents::VIEW_INJECT_CUSTOM_ASSETS,
                new CustomAssetsEvent($this->assetsHelper)
            );
        }
    }
}
