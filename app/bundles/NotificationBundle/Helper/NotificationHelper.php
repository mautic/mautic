<?php

namespace Mautic\NotificationBundle\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Model\DoNotContact as DoNotContactModel;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class NotificationHelper
{
    public function __construct(
        protected LeadRepository $leadRepository,
        protected AssetsHelper $assetsHelper,
        protected CoreParametersHelper $coreParametersHelper,
        protected IntegrationHelper $integrationHelper,
        protected RouterInterface $router,
        protected RequestStack $requestStack,
        private DoNotContactModel $doNotContact,
    ) {
    }

    /**
     * @param string $notification
     *
     * @return bool|DoNotContact
     *
     * @deprecated as unused. To be removed in 8.0
     */
    public function unsubscribe($notification)
    {
        $lead = $this->leadRepository->getLeadByEmail($notification);

        if (!is_array($lead) || !isset($lead['id'])) {
            return false;
        }

        return $this->doNotContact->addDncForContact((int) $lead['id'], 'notification', DoNotContact::UNSUBSCRIBED);
    }

    public function getHeaderScript()
    {
        if ($this->hasScript()) {
            return 'MauticJS.insertScript(\'https://cdn.onesignal.com/sdks/OneSignalSDK.js\');
                    var OneSignal = OneSignal || [];';
        }
    }

    public function getScript()
    {
        if ($this->hasScript()) {
            $integration = $this->integrationHelper->getIntegrationObject('OneSignal');

            if (!$integration || false === $integration->getIntegrationSettings()->getIsPublished()) {
                return;
            }

            $settings        = $integration->getIntegrationSettings();
            $keys            = $integration->getDecryptedApiKeys();
            $supported       = $settings->getSupportedFeatures();
            $featureSettings = $settings->getFeatureSettings();

            $appId                      = $keys['app_id'];
            $safariWebId                = $keys['safari_web_id'];
            $welcomenotificationEnabled = in_array('welcome_notification_enabled', $supported);
            $notificationSubdomainName  = $featureSettings['subdomain_name'];
            $leadAssociationUrl         = $this->router->generate(
                'mautic_subscribe_notification',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            $welcomenotificationText = '';

            if (!$welcomenotificationEnabled) {
                $welcomenotificationText = 'welcomeNotification: { "disable": true },';
            }

            $server        = $this->requestStack->getCurrentRequest()->server;
            $https         = 'https' == parse_url($server->get('HTTP_REFERER'), PHP_URL_SCHEME);
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
                $oneSignalInit .= <<<'JS'
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

    private function hasScript(): bool
    {
        $landingPage = true;
        $server      = $this->requestStack->getCurrentRequest()->server;
        $cookies     = $this->requestStack->getCurrentRequest()->cookies;
        // already exist
        if ($cookies->get('mtc_osid')) {
            return false;
        }

        if (!str_contains($server->get('HTTP_REFERER'), $this->coreParametersHelper->get('site_url'))) {
            $landingPage = false;
        }

        $integration = $this->integrationHelper->getIntegrationObject('OneSignal');

        if (!$integration || false === $integration->getIntegrationSettings()->getIsPublished()) {
            return false;
        }

        $supportedFeatures = $integration->getIntegrationSettings()->getSupportedFeatures();

        // disable on Landing pages
        if (true === $landingPage && !in_array('landing_page_enabled', $supportedFeatures)) {
            return false;
        }

        // disable on Landing pages
        if (false === $landingPage && !in_array('tracking_page_enabled', $supportedFeatures)) {
            return false;
        }

        return true;
    }
}
