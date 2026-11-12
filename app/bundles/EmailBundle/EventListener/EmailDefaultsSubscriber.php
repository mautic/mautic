<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\EventListener;

use Mautic\CoreBundle\Event\EntityPreSave;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Helper\EmailDefaultsHelper;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class EmailDefaultsSubscriber
{
    public function __construct(
        private EmailDefaultsHelper $defaultsHelper,
    ) {
    }

    #[AsEventListener]
    public function onEmailPreSave(EntityPreSave $event): void
    {
        $email = $event->getEntity();
        if (!$email instanceof Email) {
            return;
        }

        if (!$event->isNew() || $email->getIsClone()) {
            return;
        }

        $this->defaultsHelper->applyDefaults($email);
    }
}
