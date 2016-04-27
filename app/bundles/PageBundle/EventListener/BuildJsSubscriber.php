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
        $trackingUrl = str_replace(
            array('http://', 'https://'),
            '',
            $router->generate('mautic_page_tracker', [], UrlGeneratorInterface::ABSOLUTE_URL)
        );

        $js = <<<JS
(function(m, l, n, d){
    m.trackingPixelUrl = (l.protocol == 'https:' ? 'https:' : 'http:') + '//{$trackingUrl}';
    
    var params = {
        page_title: d.title,
        page_language: n.language,
        page_referrer: (d.referrer) ? d.referrer.split('/')[2] : '',
        page_url: l.href
    };
    
    // Merge user defined tracking pixel parameters.
    if (m.hasOwnProperty("trackingPixelParams")) {
        for (var attr in m.trackingPixelParams) {
            params[attr] = m.trackingPixelParams[attr];
        }
    }
    
    m.trackingPixelUrl += '?' + m.serialize(params);
    
    m.trackingPixel = (new Image()).src = m.trackingPixelUrl;
})(MauticJS, location, navigator, document);
JS;

        $event->appendJs($js, 'Mautic Tracking Pixel');
    }
}