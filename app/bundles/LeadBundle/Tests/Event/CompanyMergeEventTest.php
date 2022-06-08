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

use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Event\CompanyMergeEvent;
use PHPUnit\Framework\TestCase;

class CompanyMergeEventTest extends TestCase
{
    public function testConstructGettersSetters()
    {
        $victor = new Company();
        $loser = new Company();
        $event   = new CompanyMergeEvent($victor, $loser);

        $this->assertEquals($victor, $event->getVictor());
        $this->assertEquals($loser, $event->getLoser());
    }
}
