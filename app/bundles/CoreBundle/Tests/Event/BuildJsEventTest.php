<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Event;

use Mautic\CoreBundle\Event\BuildJsEvent;
use PHPUnit\Framework\TestCase;

final class BuildJsEventTest extends TestCase
{
    public const TEST_JS = <<<JS
/** some comment */
console.log('logging this');
JS;

    public function testMinificationIsONInProd(): void
    {
        $event = new BuildJsEvent(self::TEST_JS);
        $this->assertSame('console.log(\'logging this\')', $event->getJs());
    }

    public function testMinificationIsOffInDev(): void
    {
        $event = new BuildJsEvent(self::TEST_JS, true);
        $this->assertSame(self::TEST_JS, $event->getJs());
    }
}
