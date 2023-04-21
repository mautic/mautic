<?php

namespace Mautic\NotificationBundle\Controller;

use function assert;
use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Controller\CommonController;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\NotificationBundle\Entity\Notification;
use Mautic\NotificationBundle\Entity\NotificationRepository;
use Mautic\NotificationBundle\Model\NotificationModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AppCallbackController extends CommonController
{
    public function indexAction(Request $request, EntityManagerInterface $em)
    {
        $requestBody = json_decode($request->getContent(), true);
        $contactRepo = $em->getRepository(Lead::class);
        assert($contactRepo instanceof LeadRepository);
        $matchData   = [
            'email' => $requestBody['email'],
        ];

        /** @var Lead $contact */
        $contact = $contactRepo->findOneBy($matchData);

        if (null === $contact) {
            $contact = new Lead();
            $contact->setEmail($requestBody['email']);
            $contact->setLastActive(new \DateTime());
        }

        $pushIdCreated = false;

        if (array_key_exists('push_id', $requestBody)) {
            $pushIdCreated = true;
            $contact->addPushIDEntry($requestBody['push_id'], $requestBody['enabled'], true);
            $contactRepo->saveEntity($contact);
        }

        $statCreated = false;

        if (array_key_exists('stat', $requestBody)) {
            $stat             = $requestBody['stat'];
            $notificationRepo = $em->getRepository(Notification::class);
            assert($notificationRepo instanceof NotificationRepository);
            $notification     = $notificationRepo->getEntity($stat['notification_id']);

            if (null !== $notification) {
                $statCreated       = true;
                $notificationModel = $this->getModel('notification');
                assert($notificationModel instanceof NotificationModel);
                $notificationModel->createStatEntry($notification, $contact, $stat['source'], $stat['source_id']);
            }
        }

        return new JsonResponse([
            'contact_id'       => $contact->getId(),
            'stat_recorded'    => $statCreated,
            'push_id_recorded' => $pushIdCreated ?: 'existing',
        ]);
    }
}
