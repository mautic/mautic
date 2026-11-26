<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\EntityEventInstanceof;

use Mautic\CoreBundle\Event\EntityPostSave;

final class InstanceofBeforeAssignSubscriber
{
    public function onPostSave(EntityPostSave $event): void
    {
        $entity = new \stdClass();
        if ($entity instanceof \stdClass) {
        }

        $entity = $event->getEntity();
    }
}
