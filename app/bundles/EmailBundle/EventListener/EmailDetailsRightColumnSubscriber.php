<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\EventListener;

use Mautic\CoreBundle\Event\CustomContentEvent;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Model\AbTest\EmailStatus;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final class EmailDetailsRightColumnSubscriber
{
    #[AsEventListener(
        event: CustomContentEvent::class,
    )]
    public function injectContent(CustomContentEvent $event): void
    {
        if ($event->checkContext('@MauticEmail/Email/details.html.twig', 'right.section.start')) {
            $vars  = $event->getVars();
            /** @var Email $email */
            $email = $vars['email'];

            $data = [
                'email'          => $email,
                'emailStatus'    => new EmailStatus($email, $email->getPublishStatus()),
            ];
            $event->addTemplate('@MauticEmail/Email/abdetails.html.twig', $data);
        }
    }
}
