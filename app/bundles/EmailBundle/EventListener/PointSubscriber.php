<?php

namespace Mautic\EmailBundle\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailOpenEvent;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\EmailBundle\Form\Type\EmailOpenType;
use Mautic\EmailBundle\Form\Type\EmailSendType;
use Mautic\EmailBundle\Form\Type\EmailToUserType;
use Mautic\EmailBundle\Helper\PointEventHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PointBundle\Event\PointBuilderEvent;
use Mautic\PointBundle\Event\TriggerBuilderEvent;
use Mautic\PointBundle\Model\PointModel;

final readonly class PointSubscriber
{
    public function __construct(
        private PointModel $pointModel,
        private EntityManagerInterface $entityManager,
        private PointEventHelper $pointEventHelper,
    ) {
    }

    #[AsEventListener(event: PointBuilderEvent::class)]
    public function onPointBuild(PointBuilderEvent $event): void
    {
        $action = [
            'group'    => 'mautic.email.actions',
            'label'    => 'mautic.email.point.action.open',
            'callback' => [PointEventHelper::class, 'validateEmail'],
            'formType' => EmailOpenType::class,
        ];

        $event->addAction('email.open', $action);

        $action = [
            'group'    => 'mautic.email.actions',
            'label'    => 'mautic.email.point.action.send',
            'callback' => [PointEventHelper::class, 'validateEmail'],
            'formType' => EmailOpenType::class,
        ];

        $event->addAction('email.send', $action);
    }

    #[AsEventListener(event: TriggerBuilderEvent::class)]
    public function onTriggerBuild(TriggerBuilderEvent $event): void
    {
        $sendEvent = [
            'group'           => 'mautic.email.point.trigger',
            'label'           => 'mautic.email.point.trigger.sendemail',
            'callback'        => [$this->pointEventHelper, 'sendEmail'],
            'formType'        => EmailSendType::class,
            'formTypeOptions' => ['update_select' => 'pointtriggerevent_properties_email'],
            'formTheme'       => '@MauticEmail/FormTheme/EmailSendList/emailsend_list_row.html.twig',
        ];

        $event->addEvent('email.send', $sendEvent);

        $sendToOwnerEvent = [
            'group'           => 'mautic.email.point.trigger',
            'label'           => 'mautic.email.point.trigger.send_email_to_user',
            'formType'        => EmailToUserType::class,
            'formTypeOptions' => ['update_select' => 'pointtriggerevent_properties_useremail_email'],
            'formTheme'       => '@MauticEmail/FormTheme/EmailSendList/email_to_user_row.html.twig',
            'eventName'       => EmailEvents::ON_SENT_EMAIL_TO_USER,
        ];

        $event->addEvent('email.send_to_user', $sendToOwnerEvent);
    }

    /**
     * Trigger point actions for email open.
     */
    #[AsEventListener(event: EmailOpenEvent::class)]
    public function onEmailOpen(EmailOpenEvent $event): void
    {
        $this->pointModel->triggerAction('email.open', $event->getEmail());
    }

    /**
     * Trigger point actions for email send.
     */
    #[AsEventListener(event: EmailSendEvent::class)]
    public function onEmailSend(EmailSendEvent $event): void
    {
        $leadArray = $event->getLead();
        if ($leadArray && is_array($leadArray) && !empty($leadArray['id'])) {
            $lead = $this->entityManager->getReference(Lead::class, $leadArray['id']);
        } else {
            return;
        }

        try {
            $this->pointModel->triggerAction('email.send', $event->getEmail(), null, $lead, true);
        } catch (EntityNotFoundException) {
            // Contact was deleted in the mean time, skip point triggering
            return;
        }
    }
}
