<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Event;

use Mautic\CoreBundle\Event\CustomTemplateEvent;
use PHPUnit\Framework\TestCase;

final class CustomTemplateEventTest extends TestCase
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
