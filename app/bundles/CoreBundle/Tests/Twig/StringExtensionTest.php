<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Twig;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Extra\String\StringExtension;
use Twig\Loader\ArrayLoader;
use Twig\TwigFilter;

final class StringExtensionTest extends TestCase
{
    private Environment $twig;

    private StringExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new StringExtension();

        $loader = new ArrayLoader([
            'upper'    => '{{ "Hello World"|u.upper }}',
            'truncate' => '{{ "This is a very long string"|u.truncate(10, "...") }}',
            'slug'     => '{{ "Hello World"|slug }}',
        ]);

        $this->twig = new Environment($loader);
        $this->twig->addExtension($this->extension);
    }

    public function testUFilterCreatesUnicodeString(): void
    {
        $result = $this->extension->createUnicodeString('Hello World');

        $this->assertSame('Hello World', $result->toString());
    }

    public function testUFilterInTwig(): void
    {
        $result = $this->twig->render('upper');

        $this->assertSame('HELLO WORLD', $result);
    }

    public function testTruncateInTwig(): void
    {
        $result = $this->twig->render('truncate');

        $this->assertSame('This is...', $result);
    }

    public function testSlugFilter(): void
    {
        $result = $this->twig->render('slug');

        $this->assertSame('Hello-World', $result);
    }

    public function testGetFiltersContainsUFilter(): void
    {
        $filterNames = array_map(static fn (TwigFilter $filter): string => $filter->getName(), $this->extension->getFilters());

        $this->assertContains('u', $filterNames);
    }
}
