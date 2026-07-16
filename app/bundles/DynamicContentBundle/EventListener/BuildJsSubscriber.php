<?php

namespace Mautic\DynamicContentBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\BuildJsEvent;
use Mautic\CoreBundle\Event\BuildJsScope;
use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class BuildJsSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly AssetsHelper $assetsHelper,
        private readonly TranslatorInterface $translator,
        private readonly RequestStack $requestStack,
        private readonly RouterInterface $router,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::BUILD_MAUTIC_JS => ['onBuildJs', 200],
        ];
    }

    /**
     * Adds DWC helpers after page-event setup and before page-event delivery.
     */
    public function onBuildJs(BuildJsEvent $event): void
    {
        $dwcUrl = $this->router->generate('mautic_api_dynamicContent_action', ['objectAlias' => 'slotNamePlaceholder'], UrlGeneratorInterface::ABSOLUTE_URL);

        $js = <<<JS
        
           // call variable if doesnt exist
            if (typeof MauticDomain == 'undefined') {
                var MauticDomain = '{$this->requestStack->getCurrentRequest()->getSchemeAndHttpHost()}';
            }            
            if (typeof MauticLang == 'undefined') {
                var MauticLang = {
                     'submittingMessage': "{$this->translator->trans('mautic.form.submission.pleasewait')}"
        };
            }
MauticJS.replaceDynamicContent = function (params) {
    params = params || {};

    var dynamicContentSlots = document.querySelectorAll('.mautic-slot, [data-slot="dwc"]');
    if (dynamicContentSlots.length) {
        MauticJS.iterateCollection(dynamicContentSlots)(function(node, i) {
            var slotName = node.dataset['slotName'];
            if ('undefined' === typeof slotName) {
                slotName = node.dataset['paramSlotName'];
            }
            if ('undefined' === typeof slotName) {
                node.innerHTML = '';
                return;
            }
            var url = '{$dwcUrl}'.replace('slotNamePlaceholder', slotName);

            MauticJS.makeCORSRequest('GET', url, params, function(response, xhr) {
                if (response.content) {
                    var dwcContent = response.content;
                    node.innerHTML = dwcContent;

                    MauticJS.onDynamicContentResponse(response);
                    MauticJS.enhanceDynamicContent(dwcContent);
                }
            });
        });
    }
};

// Tracking overrides this hook before PageBundle drains the pre-delivery queue.
MauticJS.onDynamicContentResponse = function() {};

MauticJS.enhanceDynamicContent = function(dwcContent) {
    // form load library
    if (dwcContent.search("mauticform_wrapper") > 0) {
        // if doesn't exist
        if (typeof MauticSDK == 'undefined') {
            MauticJS.insertScript('{$this->assetsHelper->getUrl('media/js/mautic-form.js', null, null, true)}');

            // check initialize form library
            var fileInterval = setInterval(function() {
                if (typeof MauticSDK != 'undefined') {
                    MauticSDK.onLoad();
                    clearInterval(fileInterval); // clear interval
                 }
             }, 100); // check every 100ms
        } else {
            MauticSDK.onLoad();
         }
    }

    var m;
    var regEx = /<script[^>]+src="?([^"\s]+)"?\s/g;

    while (m = regEx.exec(dwcContent)) {
        if ((m[1]).search("/focus/") > 0) {
            MauticJS.insertScript(m[1]);
        }
    }
};

MauticJS.beforeFirstEventDelivery(MauticJS.replaceDynamicContent);
JS;
        $event->appendJsForScope($js, BuildJsScope::ESSENTIAL, 'Mautic Dynamic Content');

        $js = <<<'JS_WRAP'
        (function(window) {
        var MauticJS = window.MauticJS;
        if (!MauticJS || MauticJS.runtimeReady !== true) {
            return;
        }
        
        MauticJS.onDynamicContentResponse = function(response) {
            if (response.id && response.sid) {
                MauticJS.setTrackedContact(response);
            }
        };
        })(window);
        JS_WRAP;
        $event->appendJsForScope($js, BuildJsScope::TRACKING, 'Mautic Dynamic Content Tracking');
    }
}
