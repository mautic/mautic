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

namespace Mautic\FormBundle\Tests\Event;

use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Event\FormFieldEvent;

final class FormFieldEventTest extends \PHPUnit\Framework\TestCase
{
    public function testWorkflow(): void
    {
        $field  = new Field();
        $field2 = new Field();
        $event  = new FormFieldEvent($field, true);
        $this->assertTrue($event->isNew());
        $this->assertSame($field, $event->getField());

        $event->setField($field2);

        $this->assertSame($field2, $event->getField());
    }
}
