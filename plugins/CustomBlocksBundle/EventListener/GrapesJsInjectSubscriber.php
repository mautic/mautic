<?php

declare(strict_types=1);

namespace MauticPlugin\CustomBlocksBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomContentEvent;
use Mautic\CoreBundle\Helper\AssetsHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class GrapesJsInjectSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private AssetsHelper $assetsHelper,
        private RequestStack $requestStack
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::VIEW_INJECT_CUSTOM_CONTENT => ['onInjectCustomContent', 0],
        ];
    }

    public function onInjectCustomContent(CustomContentEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        // Keep this conservative: inject only on email builder-related routes/pages.
        // Adjust the conditions to match your instance routes.
        $route = (string) $request->attributes->get('_route');
        $path  = (string) $request->getPathInfo();

        $isEmailArea =
            str_contains($route, 'mautic_email') ||
            str_contains($path, '/emails');

        if (!$isEmailArea) {
            return;
        }

        $src = $this->assetsHelper->getUrl('plugins/CustomBlocksBundle/Assets/js/grapesjs.customBlocks.js');

        // Inject near the end of the body so `window.MauticGrapesJsPlugins` is available before builder init.
        $event->addContent(
            sprintf('<script src="%s"></script>', $src),
            CustomContentEvent::LOCATION_BODY_END
        );
    }
}
