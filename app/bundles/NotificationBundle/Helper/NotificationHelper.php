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
     * PageSubscriber constructor.
     *
     * @param AssetsHelper         $assetsHelper
     * @param CoreParametersHelper $coreParametersHelper
     * @param Router $router
     */
    public function __construct(MauticFactory $factory, AssetsHelper $assetsHelper, CoreParametersHelper $coreParametersHelper, Router $router)
    {
        $this->factory = $factory;
        $this->assetsHelper         = $assetsHelper;
        $this->coreParametersHelper = $coreParametersHelper;
        $this->router = $router;
    }

    /**
     * @param string $email
     *
     * @return bool
     */
    public function unsubscribe($email)
    {
        /** @var \Mautic\LeadBundle\Entity\LeadRepository $repo */
        $repo = $this->factory->getEntityManager()->getRepository('MauticLeadBundle:Lead');

        $lead = $repo->getLeadByEmail($email);

        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $leadModel = $this->factory->getModel('lead.lead');

        return $leadModel->addDncForLead($lead, 'notification', null, DoNotContact::UNSUBSCRIBED);
    }

    /**
     * @param string $email
     *
     * @return bool
     */
    public function getScript()
    {
//        if (!$this->coreParametersHelper->getParameter('notification_landing_page_enabled')) {
//            return;
//        }
        $appId                      = $this->coreParametersHelper->getParameter('notification_app_id');
        $safariWebId                = $this->coreParametersHelper->getParameter('notification_safari_web_id');
        $welcomenotificationEnabled = $this->coreParametersHelper->getParameter('welcomenotification_enabled');

        $leadAssociationUrl = $this->router->generate('mautic_subscribe_notification', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $welcomenotificationText = '';
        if (!$welcomenotificationEnabled) {
            $welcomenotificationText = 'welcomeNotification: { "disable": true },';
        }

        $oneSignalInit = <<<JS

    var OneSignal = OneSignal || [];
    
    OneSignal.push(["init", {
        appId: "{$appId}",
        safari_web_id: "{$safariWebId}",
        autoRegister: true,
        {$welcomenotificationText}
        notifyButton: {
            enable: false // Set to false to hide
        }
    }]);

    var postUserIdToMautic = function(userId) {
        var xhr = new XMLHttpRequest();

        xhr.open('post', '{$leadAssociationUrl}', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.send('osid=' + userId);
    };

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
JS;
        return $oneSignalInit;
    }

}
