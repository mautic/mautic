<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\NotificationBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\HttpFoundation\RequestStack;
use Mautic\CoreBundle\Templating\Helper\AssetsHelper;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;



class NotificationHelper
{
    /**
     * @var MauticFactory
     */
    protected $factory;

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
     * PageSubscriber constructor.
     *
     * @param AssetsHelper         $assetsHelper
     * @param CoreParametersHelper $coreParametersHelper
     * @param Router $router
     */
    public function __construct(MauticFactory $factory, AssetsHelper $assetsHelper, CoreParametersHelper $coreParametersHelper, Router $router, RequestStack $requestStack)
    {
        $this->factory = $factory;
        $this->assetsHelper         = $assetsHelper;
        $this->coreParametersHelper = $coreParametersHelper;
        $this->router = $router;
        $this->request = $requestStack;
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


    public function getHeaderScript(){
        if($this->hasScript()) {
            return 'MauticJS.insertScript(\'https://cdn.onesignal.com/sdks/OneSignalSDK.js\');
        var OneSignal = OneSignal || [];';
        }
    }

    public function getScript()
    {
        if($this->hasScript()) {

            $appId = $this->coreParametersHelper->getParameter('notification_app_id');
            $safariWebId = $this->coreParametersHelper->getParameter('notification_safari_web_id');
            $welcomenotificationEnabled = $this->coreParametersHelper->getParameter('welcomenotification_enabled');
            $notificationSubdomainName = $this->coreParametersHelper->getParameter('notification_subdomain_name');
            $leadAssociationUrl = $this->router->generate(
                'mautic_subscribe_notification',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            $welcomenotificationText = '';
            if (!$welcomenotificationEnabled) {
                $welcomenotificationText = 'welcomeNotification: { "disable": true },';
            }

            $server = $this->request->getCurrentRequest()->server;
            $https = (parse_url($server->get('HTTP_REFERER'), PHP_URL_SCHEME) == 'https') ? true : false;

            $subdomainName = '';
            if (!$https && $notificationSubdomainName) {
                $subdomainName = 'subdomainName: "'.$notificationSubdomainName.'",
             httpPermissionRequest: {
                enable: true,
                useCustomModal: true
            },';
            }

            $oneSignalInit = <<<JS
         var scrpt = document.createElement('link');
         scrpt.rel ='manifest';
         scrpt.href ='/manifest.json';
         var head = document.getElementsByTagName('head')[0];
         head.appendChild(scrpt);
         
    OneSignal.push(["init", {
        appId: "{$appId}",
        safari_web_id: "{$safariWebId}",
        autoRegister: true,
        {$welcomenotificationText}
        {$subdomainName}
        notifyButton: {
            enable: false // Set to false to hide
        }
    }]);

    var postUserIdToMautic = function(userId) {
        var data = [];
        data['osid'] = userId;
        MauticJS.makeCORSRequest('GET', '{$leadAssociationUrl}', data);
    };

    OneSignal.push(function() {
        OneSignal.getUserId(function(userId) {
            if (! userId) {
                OneSignal.on('subscriptionChange', function(isSubscribed) {
                    if (isSubscribed) {
                        OneSignal.getUserId(function(newUserId) {
                            postUserIdToMautic(newUserId);
                        });
                    }
                });
            } else {
                postUserIdToMautic(userId);
            }
        });
        // Just to be sure we've grabbed the ID
        window.onbeforeunload = function() {
            OneSignal.getUserId(function(userId) {
                if (userId) {
                    postUserIdToMautic(userId);
                }        
            });    
        };
    });
JS;

            if (!$https && $notificationSubdomainName) {
                $oneSignalInit .= <<<JS
  OneSignal.push(function() {
            OneSignal.on('notificationPermissionChange', function(permissionChange) {
                if(currentPermission == 'granted'){
                setTimeout(function(){
                    OneSignal.registerForPushNotifications({httpPermissionRequest: true});
                }, 100);
                }
            });
        });
JS;
            }


            return $oneSignalInit;
        }
    }

    private function hasScript(){

        $landingPage = true;
        $server = $this->request->getCurrentRequest()->server;
        $cookies = $this->request->getCurrentRequest()->cookies;
        // already exist
        if($cookies->get('mtc_osid')){
            return false;
        }

        if (strpos($server->get('HTTP_REFERER'), $this->coreParametersHelper->getParameter('site_url')) === false) {
            $landingPage = false;
        }

        // disable on Landing pages
        if($landingPage == true && !$this->coreParametersHelper->getParameter('notification_landing_page_enabled'))
        {
            return false;
        }

        // disable on tracking pages
        if($landingPage == false && !$this->coreParametersHelper->getParameter('notification_tracking_page_enabled'))
        {
            return false;
        }

        return true;
    }

}
