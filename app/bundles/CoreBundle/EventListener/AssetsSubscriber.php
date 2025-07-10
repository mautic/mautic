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
    public function __construct(
        private AssetsHelper $assetsHelper,
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['fetchCustomAssets', 0],
        ];
    }

    public function fetchCustomAssets(RequestEvent $event): void
    {
        if ($event->isMainRequest() && $this->dispatcher->hasListeners(CoreEvents::VIEW_INJECT_CUSTOM_ASSETS)) {
            $this->dispatcher->dispatch(
                new CustomAssetsEvent($this->assetsHelper),
                CoreEvents::VIEW_INJECT_CUSTOM_ASSETS
            );
        }
    }
}
