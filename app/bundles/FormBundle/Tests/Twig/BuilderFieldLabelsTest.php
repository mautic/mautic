<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Twig;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;

final class BuilderFieldLabelsTest extends TestCase
{
    #[DataProvider('showWhenValueExistsProvider')]
    public function testHideWhenValueExistsLabel(bool|int|string|null $showWhenValueExists, bool $expectLabel): void
    {
        $loader = new FilesystemLoader();
        $loader->addPath(__DIR__.'/../../Resources/views', 'MauticForm');
        $loader->addPath(__DIR__.'/../../../CoreBundle/Resources/views', 'MauticCore');

        $twig = new Environment($loader);
        $twig->addExtension(new TranslationExtension());
        $twig->addFilter(new TwigFilter('purify', static fn (string $value): string => $value));

        $template = file_get_contents(__DIR__.'/../../Resources/views/Builder/_labels.html.twig');
        $this->assertIsString($template);

        $html = $twig->createTemplate($template)->render([
            'field' => [
                'type'                => 'text',
                'properties'          => [],
                'validation'          => [],
                'alwaysDisplay'       => false,
                'showWhenValueExists' => $showWhenValueExists,
            ],
            'isConditional' => false,
            'mappedFields'  => [],
        ]);

        if ($expectLabel) {
            $this->assertStringContainsString('ri-eye-close-fill', $html);
        } else {
            $this->assertStringNotContainsString('ri-eye-close-fill', $html);
        }
    }

    /**
     * @return iterable<string, array{bool|int|string|null, bool}>
     */
    public static function showWhenValueExistsProvider(): iterable
    {
        yield 'unset' => [null, false];
        yield 'boolean false' => [false, true];
        yield 'integer zero' => [0, true];
        yield 'string zero' => ['0', true];
        yield 'enabled' => [true, false];
    }
}
