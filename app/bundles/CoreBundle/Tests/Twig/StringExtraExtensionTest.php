<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Twig;

use Mautic\CoreBundle\Twig\Extension\StringExtraExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\String\UnicodeString;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

final class StringExtraExtensionTest extends TestCase
{
    private Environment $twig;
    private StringExtraExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new StringExtraExtension();

        $loader = new ArrayLoader([
            'test'     => '{{ "Hello World"|u.upper }}',
            'truncate' => '{{ "This is a very long string"|u.truncate(10, "...") }}',
        ]);

        $this->twig = new Environment($loader);
        $this->twig->addExtension($this->extension);
    }

    public function testUFilterCreatesUnicodeString(): void
    {
        $result = $this->extension->createUnicodeString('Hello World');

        $this->assertInstanceOf(UnicodeString::class, $result);
        $this->assertEquals('Hello World', $result->toString());
    }

    public function testUFilterInTwig(): void
    {
        $result = $this->twig->render('test');

        $this->assertEquals('HELLO WORLD', $result);
    }

    public function testTruncateInTwig(): void
    {
        $result = $this->twig->render('truncate');

        $this->assertEquals('This is...', $result);
    }

    public function testGetFilters(): void
    {
        $filters = $this->extension->getFilters();

        $this->assertCount(1, $filters);
        $this->assertEquals('u', $filters[0]->getName());
    }
}
