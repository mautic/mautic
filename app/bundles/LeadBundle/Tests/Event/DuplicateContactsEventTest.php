<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Event;

use Mautic\CategoryBundle\Entity\Category;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Event\DuplicateContactsEvent;
use Mautic\LeadBundle\Event\LeadListEvent;

class DuplicateContactsEventTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructGettersSetters()
    {
        $fields = ['field1', 'field2'];
        $event  = new DuplicateContactsEvent($fields);
        $this->assertSame($event->getFields(), $fields);

        $duplicates = ['duplicate1', 'duplicate2' ];
        $event->setDuplicates($duplicates);
        $this->assertSame($event->getDuplicates(), $duplicates);
        $this->assertTrue($event->isHandledByPlugin());

    }
}
