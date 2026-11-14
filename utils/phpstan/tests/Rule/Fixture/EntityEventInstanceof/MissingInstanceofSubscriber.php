<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\EntityEventInstanceof;

use Mautic\CoreBundle\Event\EntityPostSave;
use Mautic\CoreBundle\Event\EntityPreDelete;

final class MissingInstanceofSubscriber
{
    public function onPostSave(EntityPostSave $event): void
    {
        $entity = $event->getEntity();
        // unrelated check does not count
        $isObject = $event instanceof \stdClass;
    }

    public function onPreDelete(EntityPreDelete $event): void
    {
        $event->getEntity();
    }
}
