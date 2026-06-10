<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\BuildJsEvent;
use MauticPlugin\MauticFocusBundle\Entity\FocusRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class BuildJsSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private RouterInterface $router,
        private FocusRepository $focusRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::BUILD_MAUTIC_JS => ['onBuildJs', 200],
        ];
    }

    /**
     * Adds tracking JS that loads filter-based focus items matching the tracked contact.
     */
    public function onBuildJs(BuildJsEvent $event): void
    {
        // No filter-based items -> no JS, no /focus/check request per page view
        if (!$this->focusRepository->hasPublishedWithFilters()) {
            return;
        }

        $checkUrl = $this->router->generate('mautic_focus_check', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $js = <<<JS
MauticJS.checkFocusItems = function (params) {
    params = params || {};

    MauticJS.makeCORSRequest('GET', '{$checkUrl}', params, function (response, xhr) {
        if (response && response.focus_items && response.focus_items.length) {
            if (response.id && response.sid) {
                MauticJS.setTrackedContact(response);
            }

            for (var i = 0; i < response.focus_items.length; i++) {
                if (response.focus_items[i].js_url) {
                    MauticJS.insertScript(response.focus_items[i].js_url);
                }
            }
        }
    });
};

MauticJS.beforeFirstEventDelivery(MauticJS.checkFocusItems);
JS;
        $event->appendJs($js, 'Mautic Focus Items');
    }
}
