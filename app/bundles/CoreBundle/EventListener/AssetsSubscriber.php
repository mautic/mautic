<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
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

    #[AsEventListener(event: KernelEvents::REQUEST)]
    public function fetchCustomAssets(RequestEvent $event): void
    {
        if ($event->isMainRequest() && $this->dispatcher->hasListeners(CustomAssetsEvent::class)) {
            $this->dispatcher->dispatch(new CustomAssetsEvent($this->assetsHelper));
        }
    }
}
