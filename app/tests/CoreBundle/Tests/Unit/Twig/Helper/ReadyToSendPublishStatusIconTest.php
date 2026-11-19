<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Twig\Helper;

use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class ReadyToSendPublishStatusIconTest extends TestCase
{
    public function testReadyToSendStatusRendersActiveToggle(): void
    {
        $loader = new FilesystemLoader(__DIR__.'/../../../../Resources/views/Helper');
        $twig   = new Environment($loader);
        $twig->addExtension(new TranslationExtension());
        $twig->addFilter(new TwigFilter('parse_str', static fn (string $query): array => []));
        $twig->addFunction(new TwigFunction('dateToFull', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('md5', md5(...)));

        $html = $twig->render('publishstatus_icon.html.twig', [
            'item'   => (object) ['id' => 42, 'publishStatus' => 'published'],
            'model'  => 'email',
            'status' => 'ready_to_send',
        ]);

        $this->assertStringContainsString('ri-toggle-fill text-success', $html);
        $this->assertStringContainsString('has-click-event', $html);
        $this->assertStringContainsString('data-status="ready_to_send"', $html);
        $this->assertStringContainsString('Mautic.togglePublishStatus', $html);
    }
}
