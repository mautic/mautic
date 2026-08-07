<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Twig\Helper;

use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

final class TableHeaderTest extends TestCase
{
    private Environment $twig;

    protected function setUp(): void
    {
        $loader     = new FilesystemLoader(__DIR__.'/../../../../Resources/views/Helper');
        $this->twig = new Environment($loader);
        $this->twig->addExtension(new TranslationExtension());

        // Stubs for functions only used in the untested `checkall` branch of
        // tableheader.html.twig, required so the whole template can compile.
        foreach (['buttonReset', 'restore', 'buttonAdd', 'translatorHasId', 'buttonsRender', 'path'] as $function) {
            $this->twig->addFunction(new TwigFunction($function, static fn (...$args): string => ''));
        }
    }

    /**
     * @param array<string, mixed> $context
     */
    private function render(array $context): string
    {
        $context += [
            'app' => new class() {
                public object $session;

                public function __construct()
                {
                    $this->session = new class() {
                        public function get(string $key, mixed $default = null): mixed
                        {
                            return $default;
                        }
                    };
                }
            },
        ];

        return $this->twig->render('tableheader.html.twig', $context);
    }

    public function testTooltipIsRenderedOnNonSortableHeaderWithoutSessionVar(): void
    {
        $html = $this->render([
            'text'    => 'Test Column',
            'tooltip' => 'Tooltip',
        ]);

        $this->assertStringContainsString('data-toggle="tooltip"', $html);
        $this->assertStringContainsString('data-original-title="Tooltip"', $html);
    }

    public function testNoTooltipMarkupWhenTooltipIsNotProvidedWithoutSessionVar(): void
    {
        $html = $this->render([
            'text' => 'Test Column',
        ]);

        $this->assertStringNotContainsString('data-toggle="tooltip"', $html);
    }

    public function testTooltipIsRenderedOnSortableHeader(): void
    {
        $html = $this->render([
            'text'       => 'Test Column',
            'sessionVar' => 'test.sessionvar',
            'orderBy'    => 'test_field',
            'tooltip'    => 'SortMe',
        ]);

        $this->assertStringContainsString('class="fw-b" data-toggle="tooltip"', $html);
        $this->assertStringContainsString('data-original-title="SortMe"', $html);
    }

    public function testTooltipIsRenderedOnNonSortableHeaderWithSessionVar(): void
    {
        $html = $this->render([
            'text'       => 'Test Column',
            'sessionVar' => 'test.sessionvar',
            'tooltip'    => 'StaticColumn',
        ]);

        $this->assertStringContainsString('class="pa-md" data-toggle="tooltip"', $html);
        $this->assertStringContainsString('data-original-title="StaticColumn"', $html);
    }

    public function testTooltipAttributeValueIsEscaped(): void
    {
        $html = $this->render([
            'text'    => 'Test Column',
            'tooltip' => '"><script>alert(1)</script>',
        ]);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertMatchesRegularExpression('/<span[^>]*data-original-title="[^"]*"[^>]*>Test Column<\/span>/', $html);
    }
}
