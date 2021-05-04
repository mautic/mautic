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
