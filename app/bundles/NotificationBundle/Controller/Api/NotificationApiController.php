<?php

namespace Mautic\NotificationBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\NotificationBundle\Entity\Notification;
use Mautic\NotificationBundle\Model\NotificationModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

/**
 * @extends CommonApiController<Notification>
 */
class NotificationApiController extends CommonApiController
{
    /**
     * @var ContactTracker
     */
    protected $contactTracker;

    public function initialize(ControllerEvent $event)
    {
        $notificationModel = $this->getModel('notification');
        \assert($notificationModel instanceof NotificationModel);

        $this->model           = $notificationModel;
        $this->contactTracker  = $this->container->get('mautic.tracker.contact');
        $this->entityClass     = Notification::class;
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
        $osid = $this->request->get('osid');
        if ($osid) {
            /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
            $leadModel = $this->getModel('lead');

            if ($currentLead = $this->contactTracker->getContact()) {
                $currentLead->addPushIDEntry($osid);
                $leadModel->saveEntity($currentLead);
            }

            return new JsonResponse(['success' => true, 'osid' => $osid], 200, ['Access-Control-Allow-Origin' => '*']);
        }

        return new JsonResponse(['success' => 'false'], 200, ['Access-Control-Allow-Origin' => '*']);
    }
}
