<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Event;

use Mautic\LeadBundle\Event\ListFieldChoicesEvent;

class ListFieldChoicesEventTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructGettersSetters(): void
    {
        $event = new ListFieldChoicesEvent();

        $this->assertSame([], $event->getChoicesForAllListFieldTypes());
        $this->assertSame([], $event->getChoicesForAllListFieldAliases());

        $event->setChoicesForFieldType('boolean', ['No' => 0, 'Yes' => 1]);
        $event->setChoicesForFieldAlias('campaign', ['Campaign A' => 1, 'Campaign B' => 2]);

        $this->assertSame(['boolean' => ['No' => 0, 'Yes' => 1]], $event->getChoicesForAllListFieldTypes());
        $this->assertSame(['campaign' => ['Campaign A' => 1, 'Campaign B' => 2]], $event->getChoicesForAllListFieldAliases());
    }
}
