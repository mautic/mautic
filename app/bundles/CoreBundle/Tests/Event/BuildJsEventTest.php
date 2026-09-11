<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Event;

use Mautic\CoreBundle\Event\BuildJsEvent;
use Mautic\CoreBundle\Event\BuildJsScope;
use PHPUnit\Framework\Attributes\DataProvider;
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

    /**
     * @param BuildJsScope[] $acceptedScopes
     */
    #[DataProvider('scopeCombinationProvider')]
    public function testScopeCombinations(array $acceptedScopes, string $expectedJs): void
    {
        $event = new BuildJsEvent('header;', true, $acceptedScopes);
        $event->appendJsForScope('runtime;', BuildJsScope::RUNTIME);
        $event->appendJsForScope('essential;', BuildJsScope::ESSENTIAL);
        $event->appendJsForScope('tracking;', BuildJsScope::TRACKING);
        $event->appendJs('compatibility;');

        $this->assertSame($expectedJs, $event->getJs());
        $this->assertSame(in_array(BuildJsScope::RUNTIME, $acceptedScopes, true), $event->acceptsScope(BuildJsScope::RUNTIME));
        $this->assertSame(in_array(BuildJsScope::ESSENTIAL, $acceptedScopes, true), $event->acceptsScope(BuildJsScope::ESSENTIAL));
        $this->assertSame(in_array(BuildJsScope::TRACKING, $acceptedScopes, true), $event->acceptsScope(BuildJsScope::TRACKING));
    }

    public function testLegacyDefaultAcceptsAllScopes(): void
    {
        $event = new BuildJsEvent('header;', true);
        $event->appendJsForScope('runtime;', BuildJsScope::RUNTIME);
        $event->appendJsForScope('essential;', BuildJsScope::ESSENTIAL);
        $event->appendJsForScope('tracking;', BuildJsScope::TRACKING);
        $event->appendJs('compatibility;');

        $this->assertSame('header;runtime;essential;tracking;compatibility;', $event->getJs());
    }

    public function testDebugSectionCommentsAreAddedOnlyForAcceptedScopes(): void
    {
        $event = new BuildJsEvent('', true, [BuildJsScope::ESSENTIAL]);
        $event->appendJsForScope('runtime;', BuildJsScope::RUNTIME, 'Runtime');
        $event->appendJsForScope('essential;', BuildJsScope::ESSENTIAL, 'Essential');

        $this->assertStringNotContainsString('Runtime', $event->getJs());
        $this->assertStringContainsString('// Essential Start', $event->getJs());
        $this->assertStringContainsString('essential;', $event->getJs());
        $this->assertStringContainsString('// Essential End', $event->getJs());
    }

    public function testAcceptedScopedOutputIsMinifiedInProduction(): void
    {
        $event = new BuildJsEvent('', false, [BuildJsScope::ESSENTIAL]);
        $event->appendJsForScope("/** runtime */\nconsole.log('runtime');", BuildJsScope::RUNTIME);
        $event->appendJsForScope("/** essential */\nconsole.log('essential');", BuildJsScope::ESSENTIAL);

        $this->assertSame("console.log('essential')", $event->getJs());
    }

    /**
     * @return iterable<string, array{BuildJsScope[], string}>
     */
    public static function scopeCombinationProvider(): iterable
    {
        yield 'no scopes' => [[], 'header;'];
        yield 'runtime only' => [[BuildJsScope::RUNTIME], 'header;runtime;'];
        yield 'essential only' => [[BuildJsScope::ESSENTIAL], 'header;essential;'];
        yield 'tracking only' => [[BuildJsScope::TRACKING], 'header;tracking;compatibility;'];
        yield 'runtime and essential' => [[BuildJsScope::RUNTIME, BuildJsScope::ESSENTIAL], 'header;runtime;essential;'];
        yield 'runtime and tracking' => [[BuildJsScope::RUNTIME, BuildJsScope::TRACKING], 'header;runtime;tracking;compatibility;'];
        yield 'essential and tracking' => [[BuildJsScope::ESSENTIAL, BuildJsScope::TRACKING], 'header;essential;tracking;compatibility;'];
        yield 'all scopes' => [[BuildJsScope::RUNTIME, BuildJsScope::ESSENTIAL, BuildJsScope::TRACKING], 'header;runtime;essential;tracking;compatibility;'];
    }
}
