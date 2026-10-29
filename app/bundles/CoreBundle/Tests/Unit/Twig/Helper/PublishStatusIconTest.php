<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Twig\Helper;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class PublishStatusIconTest extends TestCase
{
    private Environment $twig;

    protected function setUp(): void
    {
        $loader     = new FilesystemLoader(__DIR__.'/../../../../Resources/views/Helper');
        $this->twig = new Environment($loader);
        $this->twig->addExtension(new TranslationExtension());
        $this->twig->addFilter(new TwigFilter('parse_str', static fn (string $query): array => []));
        $this->twig->addFunction(new TwigFunction('dateToFull', static fn (): string => ''));
        $this->twig->addFunction(new TwigFunction('md5', md5(...)));
    }

    #[DataProvider('emailStatusProvider')]
    public function testEmailStatusRendersVisibleToggle(string $status, string $icon): void
    {
        $html = $this->twig->render('publishstatus_icon.html.twig', [
            'item'   => (object) ['id' => 42, 'publishStatus' => 'published'],
            'model'  => 'email',
            'status' => $status,
        ]);

        self::assertStringContainsString($icon, $html);
        self::assertStringContainsString('has-click-event', $html);
        self::assertStringContainsString(sprintf('data-status="%s"', $status), $html);
        self::assertStringContainsString('Mautic.togglePublishStatus', $html);
    }

    /**
     * @return iterable<string, array{status: string, icon: string}>
     */
    public static function emailStatusProvider(): iterable
    {
        yield 'sending' => [
            'status' => 'sending',
            'icon'   => 'ri-send-plane-line text-info',
        ];

        yield 'sent' => [
            'status' => 'sent',
            'icon'   => 'ri-checkbox-circle-line text-info',
        ];
    }
}
