<?php

namespace Mautic\DashboardBundle\Tests\Entity;

use Mautic\DashboardBundle\Entity\Widget;

class WidgetTest extends \PHPUnit\Framework\TestCase
{
    public function testWidgetNameXssAttempt()
    {
        $widget = new Widget();
        $widget->setName('csrf<script>console.log(\'name\');</script>');
        $this->assertEquals('csrfconsole.log(\'name\');', $widget->getName());
    }

    public function testWidgetWidthXssAttempt()
    {
        $widget = new Widget();
        $widget->setWidth('100<script>console.log(\'yellow\');</script>');
        $this->assertEquals(100, $widget->getWidth());
    }

    public function testWidgetHeightXssAttempt()
    {
        $widget = new Widget();
        $widget->setHeight('100<script>console.log(\'yellow\');</script>');
        $this->assertEquals(100, $widget->getHeight());
    }

    public function testWidgetOrderingSqliAttempt()
    {
        $widget = new Widget();
        $widget->setOrdering('3;DROP grep;');
        $this->assertEquals(3, $widget->getOrdering());
    }

    public function testWidgetTypeXssAttempt()
    {
        $widget = new Widget();
        $widget->setType('map.of.leads<script>console.log(\'yellow\');</script>');
        $this->assertEquals('map.of.leadsconsole.log(\'yellow\');', $widget->getType());
    }

    public function testToArrayEmpty()
    {
        $widget   = new Widget();
        $expected = [
            'name'     => null,
            'width'    => null,
            'height'   => null,
            'ordering' => null,
            'type'     => null,
            'params'   => [],
            'template' => null,
        ];
        $this->assertEquals($expected, $widget->toArray());
    }

    public function testToArrayFilled()
    {
        $widget = new Widget();
        $widget->setName('The itsy bitsy spider');
        $widget->setWidth(4);
        $widget->setHeight(5);
        $widget->setOrdering(6);
        $widget->setType('climed up');
        $widget->setParams([]);
        $widget->setTemplate('the water spout');
        $expected = [
            'name'     => 'The itsy bitsy spider',
            'width'    => 4,
            'height'   => 5,
            'ordering' => 6,
            'type'     => 'climed up',
            'params'   => [],
            'template' => 'the water spout',
        ];
        $this->assertEquals($expected, $widget->toArray());
    }
}
