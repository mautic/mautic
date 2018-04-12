<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DashboardBundle\Tests\Entity;

use Mautic\DashboardBundle\Entity\Widget;
use Mautic\LeadBundle\Tests\StandardImportTestHelper;

class WidgetTest extends \PHPUnit_Framework_TestCase
{
    public function testWidgetNameXssAttempt()
    {
        $widget = new Widget;
        $widget->setName('csrf<script>console.log(\'name\');</script>');
        $this->assertEquals('csrfconsole.log(\'name\');', $widget->getName());
    }

    public function testWidgetWidthXssAttempt()
    {
        $widget = new Widget;
        $widget->setWidth('100<script>console.log(\'yellow\');</script>');
        $this->assertEquals(100, $widget->getWidth());
    }

    public function testWidgetHeightXssAttempt()
    {
        $widget = new Widget;
        $widget->setHeight('100<script>console.log(\'yellow\');</script>');
        $this->assertEquals(100, $widget->getHeight());
    }

    public function testWidgetOrderingSqliAttempt()
    {
        $widget = new Widget;
        $widget->setOrdering('3;DROP grep;');
        $this->assertEquals(3, $widget->getOrdering());
    }

    public function testWidgetTypeXssAttempt()
    {
        $widget = new Widget;
        $widget->setType('map.of.leads<script>console.log(\'yellow\');</script>');
        $this->assertEquals('map.of.leadsconsole.log(\'yellow\');', $widget->getType());
    }
}
