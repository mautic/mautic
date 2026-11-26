<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\EntityEventInstanceof;

use Mautic\CoreBundle\Event\EntityPostDelete;
use Mautic\CoreBundle\Event\EntityPostSave;

final class NullableUnionMissingInstanceofSubscriber
{
    public function onNullable(?EntityPostSave $event): void
    {
        $event?->getEntity();
    }

    public function onUnion(EntityPostSave|EntityPostDelete $event): void
    {
        $event->getEntity();
    }
}
