<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\EventListener;

use Mautic\CoreBundle\Form\DataTransformer\ArrayStringTransformer;
use Mautic\EmailBundle\Exception\EmailCouldNotBeSentException;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PointBundle\Entity\TriggerEvent;
use Mautic\PointBundle\Event\TriggerExecutedEvent;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Hash\UserHash;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EmailToUserSubscriber implements EventSubscriberInterface
{
    /** @var EmailModel */
    private $emailModel;

    public function __construct(EmailModel $emailModel)
    {
        $this->emailModel = $emailModel;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [EmailToUserSubscriber::class => ['onEmailToUser', 0]];
    }

    public function onEmailToUser(TriggerExecutedEvent $event)
    {
        $triggerEvent = $event->getTriggerEvent();
        $lead         = $event->getLead();

        try {
            $this->sendEmailToUsers($triggerEvent, $lead);
        }
        catch (EmailCouldNotBeSentException $e) {

        }
    }

    /**
     * @param TriggerEvent  $triggerEvent
     * @param Lead          $lead
     *
     * @throws EmailCouldNotBeSentException
     * @throws \Doctrine\ORM\ORMException
     */
    private function sendEmailToUsers(TriggerEvent $triggerEvent, Lead $lead)
    {
        $config = $triggerEvent->getProperties();

        $emailId = (int)$config['useremail']['email'];
        $email = $this->emailModel->getEntity($emailId);

        if (!$email || !$email->isPublished()) {
            throw new EmailCouldNotBeSentException('Email not found or published');
        }

        $transformer = new ArrayStringTransformer();

        $owner = $lead->getOwner();

        $leadCredentials = $lead->getProfileFields();

        $sendToOwner = empty($config['to_owner']) ? false : $config['to_owner'];
        $userIds = empty($config['user_id']) ? [] : $config['user_id'];
        $to = empty($config['to']) ? [] : $transformer->reverseTransform($config['to']);
        $cc = empty($config['cc']) ? [] : $transformer->reverseTransform($config['cc']);
        $bcc = empty($config['bcc']) ? [] : $transformer->reverseTransform($config['bcc']);
        $users = $this->transformToUserIds($userIds, $sendToOwner, $owner);
        $idHash = UserHash::getFakeUserHash();
        $tokens = $this->emailModel->dispatchEmailSendEvent($email, $leadCredentials, $idHash)->getTokens();
        $errors = $this->emailModel->sendEmailToUser($email, $users, $leadCredentials, $tokens, [], false, $to, $cc, $bcc);

        if ($errors) {
            throw new EmailCouldNotBeSentException(implode(', ', $errors));
        }
    }

    /**
     * Transform user IDs and owner ID in format we get them from the campaign
     * event form to the format the sendEmailToUser expects it.
     * The owner ID will be added only if it's not already present in the user IDs array.
     *
     * @param array $userIds
     * @param bool  $sendToOwner
     * @param User  $owner
     *
     * @return array
     */
    private function transformToUserIds(array $userIds, $sendToOwner, User $owner = null)
    {
        $users = [];

        if ($userIds) {
            foreach ($userIds as $userId) {
                $users[] = ['id' => $userId];
            }
        }

        if ($sendToOwner && $owner && !in_array($owner->getId(), $userIds)) {
            $users[] = ['id' => $owner->getId()];
        }

        return $users;
    }
}
