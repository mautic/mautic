<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\EntityEventInstanceof;

use Mautic\CoreBundle\Event\EntityPreSave;
use Mautic\EmailBundle\Entity\Email;

final class InstanceofCallSubscriber
{
    public function onPreSave(EntityPreSave $event): void
    {
        if (!$event->getEntity() instanceof Email) {
            return;
        }
    }
}
