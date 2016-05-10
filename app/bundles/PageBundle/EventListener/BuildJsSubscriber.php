<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\PageBundle\EventListener;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\BuildJsEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
/**
 * Class BuildJsSubscriber
 */
class BuildJsSubscriber extends CommonSubscriber
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            CoreEvents::BUILD_MAUTIC_JS => array('onBuildJs', 255)
        );
    }

    /**
     * @param BuildJsEvent $event
     *
     * @return void
     */
    public function onBuildJs(BuildJsEvent $event)
    {
        $router = $this->factory->getRouter();
        $pageTrackingUrl = str_replace(
            array('http://', 'https://'),
            '',
            $router->generate('mautic_page_tracker', array(), UrlGeneratorInterface::ABSOLUTE_URL)
        );

        $js = <<<JS
(function(m, l, n, d) {
    m.pageTrackingUrl = (l.protocol == 'https:' ? 'https:' : 'http:') + '//{$pageTrackingUrl}';

    m.sendPageview = function(pageview) {

        var params = {
            page_title: d.title,
            page_language: n.language,
            page_referrer: (d.referrer) ? d.referrer.split('/')[2] : '',
            page_url: l.href
        };

        // Merge user defined tracking pixel parameters.
        if (typeof pageview[2] === 'object') {
            for (var attr in pageview[2]) {
                params[attr] = pageview[2][attr];
            }
        }

        new Fingerprint2().get(function(result, components) {
            params.fingerprint = result;
            for (var componentId in components) {
                var component = components[componentId];
                if (typeof component.key !== 'undefined') {
                    if (component.key === 'resolution') {
                        params.resolution = component.value[0] + 'x' + component.value[1];
                    } else if (component.key === 'timezone_offset') {
                        params.timezone_offset = component.value;
                    } else if (component.key === 'navigator_platform') {
                        params.platform = component.value;
                    } else if (component.key === 'adblock') {
                        params.adblock = component.value;
                    } else if (component.key === 'do_not_track') {
                        params.do_not_track = component.value;
                    }
                }
            }

            m.trackingPixel = (new Image()).src = m.pageTrackingUrl + '?' + m.serialize(params);
        });

        
    }

    if (typeof m.getInput === 'function') {
        var pageview = m.getInput('send', 'pageview');

        if (pageview) {
            m.sendPageview(pageview)
        }
    }

})(MauticJS, location, navigator, document);
JS;

        $event->appendJs($js, 'Mautic Tracking Pixel');
    }
}