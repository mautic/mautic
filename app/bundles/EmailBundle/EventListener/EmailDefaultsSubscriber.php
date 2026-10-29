<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\EventListener;

use Mautic\EmailBundle\Event\EmailEvent;
use Mautic\EmailBundle\Event\EmailPreSaveEvent;
use Mautic\EmailBundle\Helper\EmailDefaultsHelper;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class EmailDefaultsSubscriber
{
    public function __construct(
        private EmailDefaultsHelper $defaultsHelper,
    ) {
    }

    #[AsEventListener(
        event: EmailPreSaveEvent::class,
        priority: 0
    )]
    public function onEmailPreSave(EmailEvent $event): void
    {
        $email = $event->getEmail();

        if (!$event->isNew() || $email->getIsClone()) {
            return;
        }

        $this->defaultsHelper->applyDefaults($email);
    }
}
