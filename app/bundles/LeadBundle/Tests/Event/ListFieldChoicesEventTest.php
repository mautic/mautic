<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Event;

use Mautic\LeadBundle\Event\ListFieldChoicesEvent;

final class ListFieldChoicesEventTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructGettersSetters(): void
    {
        $event = new ListFieldChoicesEvent();

        $this->assertSame([], $event->getChoicesForAllListFieldTypes());
        $this->assertSame([], $event->getChoicesForAllListFieldAliases());

        $event->setChoicesForFieldType('boolean', ['No' => 0, 'Yes' => 1]);
        $event->setChoicesForFieldAlias('campaign', ['Campaign A' => 1, 'Campaign B' => 2]);
        $event->setSearchTerm('Test search');

        $this->assertSame(['boolean' => ['No' => 0, 'Yes' => 1]], $event->getChoicesForAllListFieldTypes());
        $this->assertSame(['campaign' => ['Campaign A' => 1, 'Campaign B' => 2]], $event->getChoicesForAllListFieldAliases());
        $this->assertSame('Test search', $event->getSearchTerm());
    }
}
