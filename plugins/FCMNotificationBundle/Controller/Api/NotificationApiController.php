<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\FCMNotificationBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Class NotificationApiController.
 */
class NotificationApiController extends CommonApiController
{
    /**
     * {@inheritdoc}
     */
    public function initialize(FilterControllerEvent $event)
    {
        $this->model           = $this->getModel('notification');
        $this->entityClass     = 'Mautic\NotificationBundle\Entity\Notification';
        $this->entityNameOne   = 'notification';
        $this->entityNameMulti = 'notifications';

        parent::initialize($event);
    }

    /**
     * Receive Web Push subscription request.
     *
     * @return JsonResponse
     */
    public function subscribeAction()
    {
        $fcm_id = $this->request->get('fcm_id');
        if ($fcm_id) {
            /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
            $leadModel = $this->getModel('lead');

            if ($currentLead = $leadModel->getCurrentLead()) {
                $currentLead->addPushIDEntry($fcm_id);
                $leadModel->saveEntity($currentLead);
            }

            return new JsonResponse(['success' => true, 'fcm_id' => $fcm_id], 200, ['Access-Control-Allow-Origin' => '*']);
        }

        return new JsonResponse(['success' => 'false'], 200, ['Access-Control-Allow-Origin' => '*']);
    }

    /**
     * Receive Web Push show event.
     *
     * @return JsonResponse
     */
    public function trackopenAction()
    {
        $notification_id = $this->request->get('notification_id');        
        if ($notification_id) {
            $this->integrationHelper = $this->get('mautic.helper.integration'); 
            $integrationObject = $this->integrationHelper->getIntegrationObject('FCM');
        
            $notificationModel = $this->get('mauticplugin.fcmnotification.notification.model.notification');             
            $notificationModel->getRepository()->upCount($notification_id, 'read');
    
            return new JsonResponse(['success' => true], 200, ['Access-Control-Allow-Origin' => '*']);
        }

        return new JsonResponse(['success' => 'false'], 200, ['Access-Control-Allow-Origin' => '*']);
    }
}
