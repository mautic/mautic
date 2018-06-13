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
use Symfony\Component\HttpFoundation\Response;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


class JsController extends CommonController
{
    /**
     * We can't user JsonResponse here, because
     * it improperly encodes the data array.
     *
     * @return Response
     */
    public function manifestAction()
    {
        $gcmSenderId = $this->get('mautic.helper.core_parameters')->getParameter('gcm_sender_id', '103953800507');
        $data        = [
            'start_url'             => '/',
            'gcm_sender_id'         => $gcmSenderId,
            'gcm_user_visible_only' => true,
        ];

        return new Response(
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            200,
            [
                'Content-Type' => 'application/json',
            ]
        );
    }

    /**
     * @return Response
     */
    public function workerAction()
    {
        //$this->integrationHelper = new IntegrationHelper();
        $this->integrationHelper = $this->get('mautic.helper.integration');       
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

        $this->router   = $this->get('router');
        $trackOpenUrl   = $this->router->generate('mautic_track_notification_open', [], UrlGeneratorInterface::ABSOLUTE_URL);

        return new Response(
            "importScripts('https://www.gstatic.com/firebasejs/4.12.1/firebase.js');

               // Initialize Firebase
              var config = {
                apiKey: '{$apiKey}',
                authDomain: '{$projectId}.firebaseapp.com',
                databaseURL: 'https://{$projectId}.firebaseio.com',
                projectId: '{$projectId}',
                storageBucket: '',
                messagingSenderId: '{$messagingSenderId}'
              };
              firebase.initializeApp(config);

              const messaging = firebase.messaging();

              messaging.setBackgroundMessageHandler(function(payload) {                
                var notificationTitle = payload.data.title;
                var notificationOptions = {
                    body: payload.data.body,                    
                    requireInteraction: true,
                };
                if (payload.data.icon){
                    notificationOptions.icon = payload.data.icon;
                }

                if (payload.data.web_button_id){
                    notificationOptions.actions = [{
                        action: payload.data.web_button_id,
                        title: payload.data.web_button_text,                        
                    }];
                }   

                var notification = self.registration.showNotification(
                    notificationTitle,
                    notificationOptions
                );

                if (payload.data.click_action){
                    self.addEventListener('notificationclick', function(event) {
                        console.log('[Service Worker] Notification click Received.');
                        event.notification.close();
                        event.waitUntil(
                            clients.openWindow(payload.data.click_action)
                        );
                    });
                }
            
                if (payload.data.notification_id){
                    notification.onshow = function(){
                        fetch('{$trackOpenUrl}', {  
                            credentials: 'include',
                            method: 'post',                                                          
                            body: JSON.stringify({notification_id: payload.data.notification_id}),
                            headers: {
                                'Accept': 'application/json, text/plain, */*',
                                'Content-Type': 'application/json'
                            }
                          })
                          .then(response => response.json())  
                          .then(function (data) {  
                            console.log('Request succeeded with JSON response', data);  
                          })  
                          .catch(function (error) {  
                            console.log('Request failed', error);  
                          });
                    }
                }


                return notification;
              });
             ",
            200,
            [
                'Service-Worker-Allowed' => '/',
                'Content-Type'           => 'application/javascript',
            ]
        );
    }   
}
