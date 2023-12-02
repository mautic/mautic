<?php

namespace Mautic\CoreBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomAssetsEvent;
use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class AssetsSubscriber implements EventSubscriberInterface
{
    private \Mautic\CoreBundle\Twig\Helper\AssetsHelper $assetsHelper;

    private \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher;

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

    public function fetchCustomAssets(RequestEvent $event)
    {
        if ($event->isMainRequest() && $this->dispatcher->hasListeners(CoreEvents::VIEW_INJECT_CUSTOM_ASSETS)) {
            $this->dispatcher->dispatch(
                new CustomAssetsEvent($this->assetsHelper),
                CoreEvents::VIEW_INJECT_CUSTOM_ASSETS
            );
        }
    }
}
