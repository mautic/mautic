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
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class AssetsSubscriber.
 */
class AssetsSubscriber extends CommonSubscriber
{
    /**
     * @var AssetsHelper
     */
    protected $assetsHelper;

    /**
     * AssetsSubscriber constructor.
     *
     * @param AssetsHelper $assetsHelper
     */
    public function __construct(AssetsHelper $assetsHelper)
    {
        $this->assetsHelper = $assetsHelper;
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

    /**
     * @param GetResponseEvent $event
     */
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
