<?php

namespace Mautic\PageBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\BuildJsEvent;
use Mautic\PageBundle\Helper\TrackingHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class BuildJsSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private TrackingHelper $trackingHelper,
        private RouterInterface $router,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::BUILD_MAUTIC_JS => [
                // onBuildJs must always needs to be last to ensure setup before delivering the event
                ['onBuildJs', -255],
                ['onBuildJsForTrackingEvent', 256],
            ],
        ];
    }

    public function onBuildJs(BuildJsEvent $event): void
    {
        $pageTrackingUrl = $this->router->generate('mautic_page_tracker', [], UrlGeneratorInterface::ABSOLUTE_URL);
        // Determine if this is https
        $parts           = parse_url($pageTrackingUrl);
        $scheme          = $parts['scheme'];
        $pageTrackingUrl = str_replace(['http://', 'https://'], '', $pageTrackingUrl);

        $pageTrackingCORSUrl = str_replace(
            ['http://', 'https://'],
            '',
            $this->router->generate('mautic_page_tracker_cors', [], UrlGeneratorInterface::ABSOLUTE_URL)
        );
        $contactIdUrl = str_replace(
            ['http://', 'https://'],
            '',
            $this->router->generate('mautic_page_tracker_getcontact', [], UrlGeneratorInterface::ABSOLUTE_URL)
        );

        $js = <<<JS
(function(m, l, n, d) {
    m.pageTrackingUrl = (l.protocol == 'https:' ? 'https:' : '{$scheme}:') + '//{$pageTrackingUrl}';
    m.pageTrackingCORSUrl = (l.protocol == 'https:' ? 'https:' : '{$scheme}:') + '//{$pageTrackingCORSUrl}';
    m.contactIdUrl = (l.protocol == 'https:' ? 'https:' : '{$scheme}:') + '//{$contactIdUrl}';

    m.getOs = function() {
        var OSName="Unknown OS";

        if (navigator.appVersion.indexOf("Win")!=-1) OSName="Windows";
        if (navigator.appVersion.indexOf("Mac")!=-1) OSName="MacOS";
        if (navigator.appVersion.indexOf("X11")!=-1) OSName="UNIX";
        if (navigator.appVersion.indexOf("Linux")!=-1) OSName="Linux";

        return OSName;
    }

    m.deliverPageEvent = function(event, params) {
        if (!m.firstDeliveryMade && params['counter'] > 0) {
            // Wait for the first delivery to complete so that the tracking information is set
            setTimeout(function () {
                m.deliverPageEvent(event, params);
            }, 5);
            
            return;
        }

        // Pre delivery events always take all known params and should use them in the request
        if (m.preEventDeliveryQueue.length && m.beforeFirstDeliveryMade === false) {
            for(var i = 0; i < m.preEventDeliveryQueue.length; i++) {
                m.preEventDeliveryQueue[i](params);
            }

            // In case the first delivery set sid, append it
            params = m.appendTrackedContact(params);

            m.beforeFirstDeliveryMade = true;
        }

        MauticJS.makeCORSRequest('POST', m.pageTrackingCORSUrl, params, 
        function(response) {
            MauticJS.dispatchEvent('mauticPageEventDelivered', {'event': event, 'params': params, 'response': response});
        },
        function() {
            // CORS failed so load an image
            m.buildTrackingImage(event, params);
            m.firstDeliveryMade = true;
        });
    }
    
    m.buildTrackingImage = function(pageview, params) {
        delete m.trackingPixel;
        m.trackingPixel = new Image();

        if (typeof pageview[3] === 'object') {
            var events = ['onabort', 'onerror', 'onload'];
            for (var i = 0; i < events.length; i++) {
                var e = events[i];
                if (typeof pageview[3][e] === 'function') {
                    m.trackingPixel[e] = pageview[3][e];
                }
            }
        }
        
        m.trackingPixel.onload = function(e) {
            MauticJS.dispatchEvent('mauticPageEventDelivered', {'event': pageview, 'params': params, 'image': true});
        };

        m.trackingPixel.src = m.pageTrackingUrl + '?' + m.serialize(params);
    }

    m.pageViewCounter = 0;
    m.sendPageview = function(pageview) {
        var queue = [];

        if (!pageview) {
            if (typeof m.getInput === 'function') {
                queue = m.getInput('send', 'pageview');
            } else {
                return false;
            }
        } else {
            queue.push(pageview);
        }

        if (queue) {
            for (var i=0; i<queue.length; i++) {
                var event = queue[i];

                var params = {
                    page_title: d.title,
                    page_language: n.language,
                    preferred_locale: (n.language).replace('-', '_'),
                    page_referrer: (d.referrer) ? d.referrer.split('/')[2] : '',
                    page_url: l.href,
                    counter: m.pageViewCounter,
                    timezone_offset: new Date().getTimezoneOffset(),
                    resolution: window.screen.width + 'x' + window.screen.height,
                    platform: m.getOs(),
                    do_not_track: navigator.doNotTrack == 1
                };
                
                if (window.Intl && window.Intl.DateTimeFormat) {
                    params.timezone =  new window.Intl.DateTimeFormat().resolvedOptions().timeZone;
                }
                
                params = MauticJS.appendTrackedContact(params);
                
                // Merge user defined tracking pixel parameters.
                if (typeof event[2] === 'object') {
                    for (var attr in event[2]) {
                        params[attr] = event[2][attr];
                    }
                }

                m.deliverPageEvent(event, params);
                
                m.pageViewCounter++;
            }
        }
    }

    // Process pageviews after mtc.js loaded
    m.sendPageview();

    // Process pageviews after new are added
    document.addEventListener('eventAddedToMauticQueue', function(e) {
      if (MauticJS.ensureEventContext(e, 'send', 'pageview')) {
          m.sendPageview(e.detail);
      }
    });
})(MauticJS, location, navigator, document);
JS;

        $event->appendJs($js, 'Mautic Tracking Pixel');
    }

    public function onBuildJsForTrackingEvent(BuildJsEvent $event): void
    {
        $js = '';

        $lead   = $this->trackingHelper->getLead();

        if ($id = $this->trackingHelper->displayInitCode('google_analytics')) {
            $gtagSettings = [];

            if ($this->trackingHelper->getAnonymizeIp()) {
                $gtagSettings['anonymize_ip'] = true;
            }

            if ($lead && $lead->getId()) {
                $gtagSettings['user_id'] = $lead->getId();
            }

            if (count($gtagSettings) > 0) {
                $gtagSettings = ', '.json_encode($gtagSettings);
            } else {
                $gtagSettings = '';
            }

            $js .= <<<JS
a = document.createElement('script');
a.async = 1;
a.src = 'https://www.googletagmanager.com/gtag/js?id={$id}';
document.getElementsByTagName('head')[0].appendChild(a);

window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', '{$id}'{$gtagSettings});
JS;
        }

        if ($id = $this->trackingHelper->displayInitCode('facebook_pixel')) {
            $customMatch = [];
            if ($lead && $lead->getId()) {
                $fieldsToMatch = [
                    'fn' => 'firstname',
                    'ln' => 'lastname',
                    'em' => 'email',
                    'ph' => 'phone',
                    'ct' => 'city',
                    'st' => 'state',
                    'zp' => 'zipcode',
                ];
                foreach ($fieldsToMatch as $key => $fieldToMatch) {
                    $par = 'get'.ucfirst($fieldToMatch);
                    if ($value = $lead->{$par}()) {
                        $customMatch[$key] = $value;
                    }
                }
            }
            $customMatch = json_encode($customMatch);

            $js .= <<<JS
!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
document,'script','https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '{$id}'); // Insert your pixel ID here.
fbq('track', 'PageView', {$customMatch});
JS;
        }
        $js .= <<<'JS_WRAP'
        MauticJS.mtcEventSet=false;
        document.addEventListener('mauticPageEventDelivered', function(e) {
            var detail   = e.detail;
            if (!MauticJS.mtcEventSet && detail.response && detail.response.events) {
                MauticJS.setTrackedEvents(detail.response.events);
            }
      });
      
      
MauticJS.setTrackedEvents = function(events) {
        MauticJS.mtcEventSet=true;
       if (typeof fbq  !== 'undefined' && typeof events.facebook_pixel !== 'undefined') {
                 var e = events.facebook_pixel; 
                     for(var i = 0; i < e.length; i++) {
                         if(typeof e[i]['action']  !== 'undefined' && typeof e[i]['label']  !== 'undefined' )
                            fbq('trackCustom', e[i]['action'], {
                                eventLabel: e[i]['label']
                            });
                     }
                }
                
                if (typeof ga  !== 'undefined' && typeof events.google_analytics !== 'undefined') {
                    var e = events.google_analytics; 
                    for(var i = 0; i < e.length; i++) {
                         if(typeof e[i]['action']  !== 'undefined' && typeof e[i]['label']  !== 'undefined' ) {
                            ga('send', {
                                hitType: 'event',
                                eventCategory: e[i]['category'],
                                eventAction: e[i]['action'],
                                eventLabel: e[i]['label'],
                            });
                        }
                    }
                }        
                
                if (typeof events.focus_item !== 'undefined') {
                 var e = events.focus_item; 
                    for(var i = 0; i < e.length; i++) {
                         if(typeof e[i]['id']  !== 'undefined' && typeof e[i]['js']  !== 'undefined' ){
                             MauticJS.insertScript(e[i]['js']);
                     }
                   }
                }
};

   
JS_WRAP;
        $event->appendJs($js, 'Mautic 3rd party tracking pixels');
    }
}
