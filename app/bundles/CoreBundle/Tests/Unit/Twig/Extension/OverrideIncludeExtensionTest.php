<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Twig\Extension;

use Mautic\CoreBundle\Twig\Extension\OverrideIncludeExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Markup;

final class OverrideIncludeExtensionTest extends TestCase
{
    private OverrideIncludeExtension $extension;

    private Environment $environment;

    protected function setUp(): void
    {
        $this->extension   = new OverrideIncludeExtension(new EventDispatcher(), new RequestStack());
        $this->environment = new Environment(new ArrayLoader([
            'first.html.twig'    => 'first content',
            'included.html.twig' => 'included content',
        ]));
    }

    public function testIncludeWithEventReturnsRenderedTemplate(): void
    {
        // Twig 3.28 returns the rendered output as a Twig\Markup instance instead of a string.
        $result = $this->extension->includeWithEvent($this->environment, [], 'included.html.twig');

        $this->assertInstanceOf(Markup::class, $result);
        $this->assertSame('included content', (string) $result);
    }

    public function testIncludeWithEventReturnsRenderedTemplateFromArray(): void
    {
        $result = $this->extension->includeWithEvent($this->environment, [], ['first.html.twig', 'included.html.twig']);

        $this->assertInstanceOf(Markup::class, $result);
        $this->assertSame('first content', (string) $result);
    }
}
