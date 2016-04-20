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
            CoreEvents::BUILD_MAUTIC_JS => array('onBuildJs', 254)
        );
    }

    /**
     * @param BuildJsEvent $event
     *
     * @return void
     */
    public function onBuildJs(BuildJsEvent $event)
    {
        $trackingUrl = $this->factory->getRouter()->generate('mautic_page_tracker', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $trackingUrl = str_replace(array('http://', 'https://'), '', $trackingUrl);
        $js = <<<JS
(function(w, l, n, d){
    serialize = function(obj) {
        var str = [];
        for(var p in obj)
            if (obj.hasOwnProperty(p)) {
                str.push(encodeURIComponent(p) + "=" + encodeURIComponent(obj[p]));
            }
        return str.join("&");
    }
    var params = {
        title: d.title,
        language: n.language,
        referrer: (d.referrer) ? d.referrer.split('/')[2] : '',
        url: window.location.href
    };
    w.MauticJS.trackingPixel += '?' + serialize(params);
})(window, location, navigator, document);
JS;
        $event->appendJs($js, 'Page Info');
    }
}