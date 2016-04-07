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
        $trackingUrl = $this->factory->getRouter()->generate('mautic_page_tracker', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $trackingUrl = str_replace(array('http://', 'https://'), '', $trackingUrl);

        $js = <<<JS
(function(w, l){
    var MauticJS = MauticJS || [];

    MauticJS.trackingPixel = (new Image()).src = (l.protocol == 'https:' ? 'https:' : 'http:') + '//{$trackingUrl}';

    w.MauticJS = MauticJS;
})(window, location);
JS;

        $event->appendJs('Mautic Tracking Pixel', $js);
    }
}
