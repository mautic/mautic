<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\FCMNotificationBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Event\PageDisplayEvent;
use Mautic\PageBundle\PageEvents;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Plokko\Firebase\FCM\Exceptions\FcmErrorException;
use Plokko\Firebase\FCM\Message;
use Plokko\Firebase\FCM\Request;
use Plokko\Firebase\FCM\Targets\Token;
use Plokko\Firebase\ServiceAccount;
use Google\Auth\Cache\MemoryCacheItemPool;

class PopupController extends CommonController
{
    public function indexAction()
    {
        /** @var \Mautic\CoreBundle\Templating\Helper\AssetsHelper $assetsHelper */
        $assetsHelper = $this->factory->getHelper('template.assets');
        $assetsHelper->addStylesheet('/plugins/FCMNotificationBundle/Assets/css/popup/popup.css');

        $this->integrationHelper = $this->get('mautic.helper.integration');       
        $integration = $this->integrationHelper->getIntegrationObject('FCM');

        $settings          = $integration->getIntegrationSettings();
        $features          = $settings->getSupportedFeatures();
        $featureSettings   = $settings->getFeatureSettings();        

        $response = $this->render(
            'FCMNotificationBundle:Popup:index.html.php',
            [
                'siteUrl' => $this->coreParametersHelper->getParameter('site_url'),
                'icon'  => !empty($featureSettings['notification_icon'])?$featureSettings['notification_icon']:$integration->getIcon(),
                'sampleNotificationTitle'  => $featureSettings['sample_notification_title'],
                'sampleNotificationText'  => $featureSettings['sample_notification_text']
            ]
        );

        $content = $response->getContent();

        $event = new PageDisplayEvent($content, new Page());
        $this->dispatcher->dispatch(PageEvents::PAGE_ON_DISPLAY, $event);
        $content = $event->getContent();

        return $response->setContent($content);
    }

    public function testAction(){
        $this->notificationApi = $this->get('mauticplugin.fcmnotification.notification.api');       
        $this->notificationApi->send(
            'fBiGDEMndMM:APA91bGO9Tz6pfSwl5ZBYXfAC3JeVDCDFZiswEVKxW1N_lhgFKK8zDx9iZ7OS611suC-99I3rKWEvcPppz0F2uodLFPEzu5hPWRKsvWt-baq1GMmyjjp-3L29yPE0K783gsel4-b4bde',
            [
                'title' => 'My notification title2',
                'body' => 'bádi',
            ]
        );
        /*
        $this->integrationHelper = $this->get('mautic.helper.integration');       
        $integration = $this->integrationHelper->getIntegrationObject('FCM');
        $keys        = $integration->getDecryptedApiKeys();


        //-- Init the service account --//        
        $serviceAccount = new ServiceAccount($keys['service_account_json']);
        $cacheHandler = new MemoryCacheItemPool();
        $serviceAccount->setCacheHandler($cacheHandler);

        $settings          = $integration->getIntegrationSettings();        
        $featureSettings   = $settings->getFeatureSettings();   

        $message = new Message();

        $message->data->set('title', 'My notification title');
        $message->data->set('body', 'My notification body....');        
        $message->data->set('icon', $featureSettings['notification_icon']);
        
        $message->setTarget(new Token('c_4CGrLSyA8:APA91bHAKV10vdTdVA0p11_MSDdpcqbbVVa_tc6b5jdEsXtNIxlNqLIWVEFFfwaqzZKN5oz3vMD-XiWtc_hcVwwwqsKBF3Zd3Pb9xcIeWLsPrRGEW5HUUJQekMCjBBd_niqLAiQfmK-J'));

        $client = new Client(['debug'=>false]);
        //If true the validate_only is set to true the message will not be submitted but just checked with FCM
        //$validate_only = true;
        //Create a request
        $rq = new Request($serviceAccount,$validate_only,$client);
        try{
            //Use the request to submit the message
            $message->send($rq);
            //You can force the validate_only flag via the validate method, the request will be left intact
            //$message->validate($rq);
        }
        
        //Like this
        catch(FcmErrorException $e){
            switch($e->getErrorCode()){
                default:
                case 'UNSPECIFIED_ERROR':
                case 'INVALID_ARGUMENT':
                case 'UNREGISTERED':
                case 'SENDER_ID_MISMATCH':
                case 'QUOTA_EXCEEDED':
                case 'APNS_AUTH_ERROR':
                case 'UNAVAILABLE':
                case 'INTERNAL':
            }
            echo 'FCM error ['.$e->getErrorCode().']: ',$e->getMessage();
        }
        catch(RequestException $e){
            //HTTP response error
            $response = $e->getResponse();
            echo 'Got an http response error:',$response->getStatusCode(),':',$response->getReasonPhrase();

        }
        catch(GuzzleException $e){
            //GuzzleHttp generic error
            echo 'Got an http error:',$e->getMessage();
        }

        */



        $response = $this->render(
            'FCMNotificationBundle:Popup:index.html.php'
        );
        return $response->setContent($content);
    }
}
