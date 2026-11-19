<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomContentEvent;
use Mautic\EmailBundle\Model\AbTest\EmailStatus;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class EmailDetailsRightColumnSubscriber implements EventSubscriberInterface
{
    /**
     * @return mixed[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::VIEW_INJECT_CUSTOM_CONTENT => ['injectContent', 0],
        ];
    }

    public function injectContent(CustomContentEvent $event): void
    {
        if ($event->checkContext('@MauticEmail/Email/details.html.twig', 'right.section.start')) {
            $vars  = $event->getVars();
            $email = $vars['email'];

            $data = [
                'email'          => $email,
                'emailStatus'    => new EmailStatus($email, $email->getPublishStatus()),
            ];
            $event->addTemplate('@MauticEmail/Email/abdetails.html.twig', $data);
        }
    }
}
