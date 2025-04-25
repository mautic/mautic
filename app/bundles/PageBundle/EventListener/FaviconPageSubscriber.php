<?php

declare(strict_types=1);

namespace Mautic\PageBundle\EventListener;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Mautic\PageBundle\Event\PageDisplayEvent;
use Mautic\PageBundle\PageEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FaviconPageSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CoreParametersHelper $coreParametersHelper,
        private AssetsHelper $assetsHelper,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PageEvents::PAGE_ON_DISPLAY => ['onPageDisplay', 0],
        ];
    }

    public function onPageDisplay(PageDisplayEvent $event): void
    {
        $content = $event->getContent();
        $dom     = new \DOMDocument();
        if (false === @$dom->loadHTML($content)) {
            return;
        }

        $xpath    = new \DOMXPath($dom);
        $favicons = $xpath->query('//link[@rel="icon"] | //link[@rel="shortcut icon"] | //link[@rel="apple-touch-icon"]');
        if (0 !== $favicons->length) {
            return;
        }

        $parentTag = $dom->getElementsByTagName('head')->item(0);
        if (!$parentTag) {
            $htmlTag     = $dom->getElementsByTagName('html')->item(0);
            $headElement = $dom->createElement('head');
            $htmlTag->appendChild($headElement);
            $parentTag = $headElement;
        }

        $customBrandingFavicon = $this->coreParametersHelper->get('branding_favicon', false);
        if ($customBrandingFavicon) {
            $icon           = $this->assetsHelper->getUrl('media/images/favicon.ico', null, null, true);
            $appleTouchIcon = $this->assetsHelper->getUrl('media/images/apple-touch-icon.png', null, null, true);

            $iconElement = $dom->createElement('link');
            $iconElement->setAttribute('rel', 'icon');
            $iconElement->setAttribute('type', 'image/x-icon');
            $iconElement->setAttribute('href', $icon);
            $parentTag->appendChild($iconElement);

            $iconSizeElement = $dom->createElement('link');
            $iconSizeElement->setAttribute('rel', 'icon');
            $iconSizeElement->setAttribute('type', 'image/x-icon');
            $iconSizeElement->setAttribute('sizes', '192x192');
            $iconSizeElement->setAttribute('href', $icon);
            $parentTag->appendChild($iconSizeElement);

            $appleTouchIconElement = $dom->createElement('link');
            $appleTouchIconElement->setAttribute('rel', 'apple-touch-icon');
            $appleTouchIconElement->setAttribute('href', $appleTouchIcon);
            $parentTag->appendChild($appleTouchIconElement);
        } else {
            // Set empty favicon so that it will not show default from /favicon.ico
            $iconElement = $dom->createElement('link');
            $iconElement->setAttribute('rel', 'icon');
            $iconElement->setAttribute('type', 'image/png');
            $iconElement->setAttribute('href', 'data:image/png;base64,iVBORw0KGgo=');
            $parentTag->appendChild($iconElement);
        }

        $content = $dom->saveHTML();
        if (false === $content) {
            return;
        }
        $event->setContent($content);
    }
}
