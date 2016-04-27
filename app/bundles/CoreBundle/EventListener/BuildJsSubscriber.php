<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\EventListener;

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
            CoreEvents::BUILD_MAUTIC_JS => array(
                array('onBuildJs', 255),
                array('onBuildJsMauticCoreJs', 1000)
            )
        );
    }

    /**
     * Adds the MauticJS definition and core
     * JS functions for use in Bundles. This
     * must retain top priority of 1000
     * 
     * @param BuildJsEvent $event
     * 
     * @return void
     */
    public function onBuildJsMauticCoreJs(BuildJsEvent $event)
    {
        $js = <<<JS
var MauticJS = MauticJS || {};

MauticJS.serialize = function(obj) {
    var str = [];
    for(var p in obj)
        if (obj.hasOwnProperty(p)) {
            str.push(encodeURIComponent(p) + "=" + encodeURIComponent(obj[p]));
        }
    return str.join("&");
};

MauticJS.documentReady = function(f) {
    /in/.test(document.readyState) ? setTimeout('MauticJS.documentReady(' + f + ')', 9) : f();
};
JS;
        $event->appendJs($js, 'Mautic Core');
    }

    /**
     * @param BuildJsEvent $event
     *
     * @return void
     */
    public function onBuildJs(BuildJsEvent $event)
    {
        $router = $this->factory->getRouter();
        $trackingUrl = str_replace(
            array('http://', 'https://'),
            '',
            $router->generate('mautic_page_tracker', [], UrlGeneratorInterface::ABSOLUTE_URL)
        );
        
        $js = <<<JS
(function(m, l){
    m.trackingPixelUrl = (l.protocol == 'https:' ? 'https:' : 'http:') + '//{$trackingUrl}';
    
    if (m.hasOwnProperty("trackingPixelParams")) {
        m.trackingPixelUrl += "?" + m.trackingPixelParams;
    }
    
    m.trackingPixel = (new Image()).src = m.trackingPixelUrl;
})(MauticJS, location);
JS;

        $event->appendJs($js, 'Mautic Tracking Pixel');
    }
}
