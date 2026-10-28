<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\EventListener;

use Mautic\CoreBundle\Event\CustomAssetsEvent;
use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class AssetsSubscriber
{
    public function __construct(
        private AssetsHelper $assetsHelper,
        private EventDispatcherInterface $dispatcher,
    ) {
    }
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: KernelEvents::REQUEST,
        priority: 0
    )]
    public function fetchCustomAssets(RequestEvent $event): void
    {
        if ($event->isMainRequest() && $this->dispatcher->hasListeners(CustomAssetsEvent::class)) {
            $this->dispatcher->dispatch(new CustomAssetsEvent($this->assetsHelper));
        }
    }
}
