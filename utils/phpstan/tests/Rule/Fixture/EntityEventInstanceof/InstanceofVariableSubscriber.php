<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\EntityEventInstanceof;

use Mautic\CoreBundle\Event\EntityPostSave;
use Mautic\EmailBundle\Entity\Email;

final class InstanceofVariableSubscriber
{
    public function onPostSave(EntityPostSave $event): void
    {
        $entity = $event->getEntity();
        if (!$entity instanceof Email) {
            return;
        }
    }
}
