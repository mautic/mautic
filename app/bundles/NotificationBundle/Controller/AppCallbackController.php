<?php

namespace Mautic\NotificationBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\NotificationBundle\Entity\NotificationRepository;
use Mautic\NotificationBundle\Model\NotificationModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Service\Attribute\Required;

final class AppCallbackController extends CommonController
{
    private NotificationModel $notificationModel;

    private LeadRepository $leadRepository;

    private NotificationRepository $notificationRepository;

    #[Required]
    public function autowireAppCallbackController(
        NotificationModel $notificationModel,
        LeadRepository $leadRepository,
        NotificationRepository $notificationRepository,
    ): void {
        $this->notificationModel = $notificationModel;
        $this->leadRepository = $leadRepository;
        $this->notificationRepository = $notificationRepository;
    }

    public function indexAction(Request $request): JsonResponse
    {
        $requestBody = json_decode($request->getContent(), true);

        $matchData   = [
            'email' => $requestBody['email'],
        ];

        $contact = $this->leadRepository->findOneBy($matchData);

        if (null === $contact) {
            $contact = new Lead();
            $contact->setEmail($requestBody['email']);
            $contact->setLastActive(new \DateTime());
        }

        $pushIdCreated = false;

        if (array_key_exists('push_id', $requestBody) && !empty(trim($requestBody['push_id']))) {
            $pushIdCreated = true;
            $contact->addPushIDEntry($requestBody['push_id'], $requestBody['enabled'], true);
            $this->leadRepository->saveEntity($contact);
        }

        $statCreated = false;

        if (array_key_exists('stat', $requestBody)) {
            $stat             = $requestBody['stat'];
            $notification     = $this->notificationRepository->getEntity($stat['notification_id']);

            if (null !== $notification) {
                $statCreated       = true;
                $this->notificationModel->createStatEntry($notification, $contact, $stat['source'], $stat['source_id']);
            }
        }

        return new JsonResponse([
            'contact_id'       => $contact->getId(),
            'stat_recorded'    => $statCreated,
            'push_id_recorded' => $pushIdCreated ?: 'existing',
        ]);
    }
}
