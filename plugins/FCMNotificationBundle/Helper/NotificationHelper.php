<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\FCMNotificationBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Templating\Helper\AssetsHelper;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class NotificationHelper
{
    /**
     * @var MauticFactory
     */
    protected $factory;

    /**
     * @var IntegrationHelper
     */
    protected $integrationHelper;

    /**
     * @var CoreParametersHelper
     */
    protected $coreParametersHelper;

    /**
     * @var AssetsHelper
     */
    protected $assetsHelper;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var Request
     */
    protected $request;

    /**
     * NotificationHelper constructor.
     *
     * @param MauticFactory        $factory
     * @param AssetsHelper         $assetsHelper
     * @param CoreParametersHelper $coreParametersHelper
     * @param IntegrationHelper    $integrationHelper
     * @param Router               $router
     * @param RequestStack         $requestStack
     */
    public function __construct(MauticFactory $factory, AssetsHelper $assetsHelper, CoreParametersHelper $coreParametersHelper, IntegrationHelper $integrationHelper, Router $router, RequestStack $requestStack)
    {
        $this->factory              = $factory;
        $this->assetsHelper         = $assetsHelper;
        $this->coreParametersHelper = $coreParametersHelper;
        $this->integrationHelper    = $integrationHelper;
        $this->router               = $router;
        $this->request              = $requestStack;
    }

    /**
     * @param string $notification
     *
     * @return bool
     */
    public function unsubscribe($notification)
    {
        /** @var \Mautic\LeadBundle\Entity\LeadRepository $repo */
        $repo = $this->factory->getEntityManager()->getRepository('MauticLeadBundle:Lead');

        $lead = $repo->getLeadByEmail($notification);

        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $leadModel = $this->factory->getModel('lead.lead');

        return $leadModel->addDncForLead($lead, 'notification', null, DoNotContact::UNSUBSCRIBED);
    }

    public function getHeaderScript()
    {
        if ($this->hasScript()) {            
            return 'MauticJS.insertScript(\'https://www.gstatic.com/firebasejs/4.12.1/firebase.js\');
                    var _fbaq = _fbaq || [];';
        }
    }

    public function getScript()
    {
        if ($this->hasScript()) {
            $integration = $this->integrationHelper->getIntegrationObject('FCM');

            if (!$integration || $integration->getIntegrationSettings()->getIsPublished() === false) {
                return;
            }

            $settings        = $integration->getIntegrationSettings();
            $keys            = $integration->getDecryptedApiKeys();
            $supported       = $settings->getSupportedFeatures();
            $featureSettings = $settings->getFeatureSettings();

            $apiKey             = $keys['apiKey'];
            $projectId          = $keys['projectId'];
            $messagingSenderId  = $keys['messagingSenderId'];
            $publicVapidKey     = $keys['publicVapidKey'];
            
            $welcomenotificationEnabled = in_array('welcome_notification_enabled', $supported);
            
            $leadAssociationUrl         = $this->router->generate(
                'mautic_subscribe_notification',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            $welcomenotificationText = '';
        
            $server        = $this->request->getCurrentRequest()->server;
            $https         = (parse_url($server->get('HTTP_REFERER'), PHP_URL_SCHEME) == 'https') ? true : false;                        

            $fcmInit = <<<JS
var scrpt = document.createElement('link');
scrpt.rel ='manifest';
scrpt.href ='/manifest.json';
var head = document.getElementsByTagName('head')[0];
head.appendChild(scrpt);


//using queue might be necessary

var config = {
    apiKey: "{$apiKey}",
    authDomain: "{$projectId}.firebaseapp.com",
    databaseURL: "https://{$projectId}.firebaseio.com",
    projectId: "{$projectId}",
    storageBucket: "",
    messagingSenderId: "{$messagingSenderId}"
  };
  firebase.initializeApp(config);

  const messaging = firebase.messaging();
  messaging.usePublicVapidKey("{$$publicVapidKey}");

var postUserIdToMautic = function(userId) {
    var data = [];
    data['osid'] = userId;
    MauticJS.makeCORSRequest('GET', '{$leadAssociationUrl}', data);
};
    
    messaging.getToken().then(function(currentToken)){
        if (currentToken) {
            postUserIdToMautic(currentToken);          
        } else {
            messaging.requestPermission().then(function() {
                messaging.getToken().then(function(currentToken)){
                    if (currentToken) {
                        postUserIdToMautic(currentToken);          
                    }
                });
            }).catch(function(err) {
                console.log('Unable to get permission to notify.', err);
            });          
        }
      }).catch(function(err) {
        console.log('An error occurred while retrieving token. ', err);        
      });
    }
    
    // Just to be sure we've grabbed the ID
    window.onbeforeunload = function() {
        messaging.getToken().then(function(currentToken)){
            if (currentToken) {
                postUserIdToMautic(currentToken);          
            } 
        });        
    };
    
    messaging.onTokenRefresh(function() {
        messaging.getToken().then(function(refreshedToken) {
            postUserIdToMautic(refreshedToken);         
        }).catch(function(err) {
            console.log('Unable to retrieve refreshed token ', err);            
        });
    });
JS;
                
            return $fcmInit;
        }
    }

    private function hasScript()
    {
        $landingPage = true;
        $server      = $this->request->getCurrentRequest()->server;
        $cookies     = $this->request->getCurrentRequest()->cookies;
        // already exist
        if ($cookies->get('mtc_osid')) {
            return false;
        }

        if (strpos($server->get('HTTP_REFERER'), $this->coreParametersHelper->getParameter('site_url')) === false) {
            $landingPage = false;
        }

        $integration = $this->integrationHelper->getIntegrationObject('FCM');

        if (!$integration || $integration->getIntegrationSettings()->getIsPublished() === false) {
            return false;
        }

        $supportedFeatures = $integration->getIntegrationSettings()->getSupportedFeatures();

        // disable on Landing pages
        if ($landingPage === true && !in_array('landing_page_enabled', $supportedFeatures)) {
            return false;
        }

        // disable on Landing pages
        if ($landingPage === false && !in_array('tracking_page_enabled', $supportedFeatures)) {
            return false;
        }

        return true;
    }
}
