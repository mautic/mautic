<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\BuildJsEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PluginBundle\Helper\IntegrationHelper;

/**
 * Class BuildJsSubscriber.
 */
class BuildJsSubscriber extends CommonSubscriber
{
    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var IntegrationHelper
     */
    protected $integrationHelper;

    /**
     * BuildJsSubscriber constructor.
     *
     * @param LeadModel         $leadModel
     * @param IntegrationHelper $integrationHelper
     */
    public function __construct(
        LeadModel $leadModel,
        IntegrationHelper $integrationHelper
    ) {
        $this->leadModel         = $leadModel;
        $this->integrationHelper = $integrationHelper;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            CoreEvents::BUILD_MAUTIC_JS => ['onBuildJs', 100],
        ];
    }

    /**
     * Adds the MauticJS definition and core
     * JS functions for use in Bundles. This
     * must retain top priority of 1000.
     *
     * @param BuildJsEvent $event
     */
    public function onBuildJs(BuildJsEvent $event)
    {
        $integration = $this->integrationHelper->getIntegrationObject('Facebook');
        if (!$integration || !$integration->getIntegrationSettings()->isPublished()) {
            return;
        }
        if (!in_array('facebook_pixel', $integration->getIntegrationSettings()->getSupportedFeatures()) || empty($integration->getIntegrationSettings()->getFeatureSettings()['facebookPixelId'])) {
            return;
        }

        $lead        = $this->leadModel->getCurrentLead();
        $leadId      = $lead->getId();
        $sessionName = 'mtc-fb-event-'.$leadId;
        $cutomMatch  = '{}';
        if ($lead && $lead->getEmail()) {
            $cutomMatch = "{em: '{$lead->getEmail()}'}";
        }

        $js = <<<JS
        MauticJS.fbpSet = false;
        MauticJS.fbPixelLoad = function() {
            if(!MauticJS.fbpSet){
            !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
document,'script','https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '{$integration->getIntegrationSettings()->getFeatureSettings()['facebookPixelId']}', {$cutomMatch}); 
        fbq('track', 'PageView');
        // check custom event from campaign acton
        setTimeout(function(){
            if (typeof fbq != 'undefined') {
                var values = (decodeURIComponent(MauticJS.readCookie('{$sessionName}')).split('|'));
                if(values.length){
                values.shift();
                for(var i = 0; i < values.length; i++) {
                    var hash = values[i].split(':');
                    if(hash.length==2){
                        fbq('trackCustom', hash[0], {
                            eventLabel: hash[1]
                        });
				    }
                }
                document.cookie = '{$sessionName}' + '=;expires=Thu, 01 Jan 1970 00:00:01 GMT;';
                }
			}
        }, 1000)
        MauticJS.fbpSet = true;
    };
}
 MauticJS.fbPixelLoad();
JS;
        $event->appendJs($js, 'Mautic Social Integration');
    }
}
