<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Event;

use Mautic\LeadBundle\Event\TypeOperatorsEvent;

final class TypeOperatorsEventTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructGettersSetters(): void
    {
        $event = new TypeOperatorsEvent();

        $this->assertSame([], $event->getOperatorsForAllFieldTypes());

        $event->setOperatorsForFieldType('email', ['include' => ['=', 'like']]);
        $event->setOperatorsForFieldType('firsname', ['exclude' => ['!=', '!like']]);

        $this->assertSame([
            'email'    => ['include' => ['=', 'like']],
            'firsname' => ['exclude' => ['!=', '!like']],
        ], $event->getOperatorsForAllFieldTypes());
    }
}
