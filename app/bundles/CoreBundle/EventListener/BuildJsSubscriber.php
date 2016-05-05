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
            CoreEvents::BUILD_MAUTIC_JS => array('onBuildJs', 1000)
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
    public function onBuildJs(BuildJsEvent $event)
    {
        $js = <<<JS
var MauticJS = MauticJS || {};

MauticJS.serialize = function(obj) {
    var str = [];
    for (var p in obj)
        if (obj.hasOwnProperty(p)) {
            str.push(encodeURIComponent(p) + "=" + encodeURIComponent(obj[p]));
        }
    return str.join("&");
};

MauticJS.documentReady = function(f) {
    /in/.test(document.readyState) ? setTimeout('MauticJS.documentReady(' + f + ')', 9) : f();
};

if (typeof window[window.MauticTrackingObject] === 'undefined') {
    console.log('Mautic tracking is not initiated correctly. Follow the documentation.');
} else {
    MauticJS.input = window[window.MauticTrackingObject];
    MauticJS.inputQueue = MauticJS.input.q;

    MauticJS.getInput = function(task, type) {
        if (MauticJS.inputQueue.length) {
            for (var i in MauticJS.inputQueue) {
                if (MauticJS.inputQueue[i][0] === task && MauticJS.inputQueue[i][1] === type);
                return MauticJS.inputQueue[i];
            }
        } else {
            return false;
        }
    }
}
JS;
        $event->appendJs($js, 'Mautic Core');
    }
}
