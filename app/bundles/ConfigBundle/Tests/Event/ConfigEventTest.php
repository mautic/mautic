<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ConfigBundle\Tests\Event;

use Mautic\ConfigBundle\Event\ConfigEvent;
use Symfony\Component\HttpFoundation\ParameterBag;

class ConfigEventTest extends \PHPUnit_Framework_TestCase
{
    public function testNormalizedDataGetSet()
    {
        $config   = [];
        $paramBag = $this->createMock(ParameterBag::class);

        $event = new ConfigEvent($config, $paramBag);

        $origNormData = ['orig'];

        $this->assertInstanceOf(ConfigEvent::class, $event->setOriginalNormData($origNormData));
        $this->assertEquals($origNormData, $event->getOriginalNormData());

        $normData = ['norm'];

        $this->assertNull($event->setNormData($normData));
        $this->assertEquals($normData, $event->getNormData());
    }
}
