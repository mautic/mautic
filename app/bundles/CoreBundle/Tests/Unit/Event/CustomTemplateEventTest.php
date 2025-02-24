<?php

namespace Mautic\CoreBundle\Tests\Unit\Event;

use Mautic\CoreBundle\Event\CustomTemplateEvent;

class CustomTemplateEventTest extends \PHPUnit\Framework\TestCase
{
    public function testNullRequestDoesNotThrowException(): void
    {
        $event = new CustomTemplateEvent(null, 'test');
        $this->assertSame('test', $event->getTemplate());
    }

    public function testEmptyTemplateThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new CustomTemplateEvent();
    }
}
