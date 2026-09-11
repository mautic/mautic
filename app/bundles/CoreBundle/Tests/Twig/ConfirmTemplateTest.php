<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Twig;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;

final class ConfirmTemplateTest extends TestCase
{
    private Environment $twig;

    protected function setUp(): void
    {
        $loader = new FilesystemLoader(dirname(__DIR__, 2).'/Resources/views');
        $this->twig = new Environment($loader);

        $this->twig->addFilter(new TwigFilter('trans', static fn (string $value): string => $value));
    }

    public function testCancelTextCanBeRenderedWithUnicodeWithoutJsEscapes(): void
    {
        $template = $this->twig->load('Helper/confirm.html.twig');
        $output   = $template->render([
            'message'       => 'Удаление контакта',
            'confirmAction' => 'javascript:void(0);',
            'confirmText'   => 'Удалить',
            'btnText'       => 'Удалить',
            'cancelText'    => 'Отменить',
        ]);

        $this->assertStringContainsString('data-cancel-text="&#x041E;&#x0442;&#x043C;&#x0435;&#x043D;&#x0438;&#x0442;&#x044C;"', $output);
        $this->assertStringNotContainsString('\\u041E', $output);
    }

    public function testCancelTextIsHtmlAttributeEscapedForSpecialCharacters(): void
    {
        $template = $this->twig->load('Helper/confirm.html.twig');
        $output   = $template->render([
            'message'       => 'Удаление контакта',
            'confirmAction' => 'javascript:void(0);',
            'confirmText'   => 'Удалить',
            'btnText'       => 'Удалить',
            'cancelText'    => 'Отменить <script>alert(1)</script>',
        ]);

        $this->assertStringContainsString('data-cancel-text="&#x041E;&#x0442;&#x043C;&#x0435;&#x043D;&#x0438;&#x0442;&#x044C;&#x20;&lt;script&gt;alert&#x28;1&#x29;&lt;&#x2F;script&gt;"', $output);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $output);
    }
}
