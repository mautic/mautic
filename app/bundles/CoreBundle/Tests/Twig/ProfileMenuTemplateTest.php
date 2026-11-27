<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Twig;

use Knp\Menu\Matcher\MatcherInterface;
use Knp\Menu\MenuFactory;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class ProfileMenuTemplateTest extends TestCase
{
    private string $viewsDirectory;

    private Environment $twig;

    protected function setUp(): void
    {
        $this->viewsDirectory = dirname(__DIR__, 2).'/Resources/views';
        $this->twig           = new Environment(new FilesystemLoader($this->viewsDirectory), ['autoescape' => 'html']);

        $this->twig->addFilter(new TwigFilter('trans', static fn (string $value): string => match ($value) {
            'plugin.subscription.url' => 'https://example.test/account/subscriptions/',
            'plugin.subscription.manage' => 'Manage subscription',
            default => $value,
        }));
        $this->twig->addFilter(new TwigFilter('purify', static fn (string $value): string => $value));
        $this->twig->addFunction(new TwigFunction('buildMenuClasses', static fn (): array => []));
        $this->twig->addFunction(new TwigFunction(
            'parseMenuAttributes',
            static function (array $attributes): string {
                $html = '';
                foreach ($attributes as $name => $value) {
                    $html .= sprintf(' %s="%s"', $name, htmlspecialchars((string) $value, ENT_QUOTES));
                }

                return $html;
            },
            ['is_safe' => ['html']],
        ));
    }

    public function testInlineMenuTranslatesUriAndUsesStandardIconMarkup(): void
    {
        $menu = (new MenuFactory())->createItem('profile');
        $menu->addChild('plugin.subscription.manage', [
            'uri'            => 'plugin.subscription.url',
            'extras'         => ['iconClass' => 'ri-bank-card-line'],
            'linkAttributes' => ['target' => '_blank', 'rel' => 'noopener noreferrer'],
        ]);

        $output = $this->twig->render('Menu/profile_inline.html.twig', [
            'item'    => $menu,
            'matcher' => $this->createStub(MatcherInterface::class),
            'options' => ['depth' => 1, 'matchingDepth' => 0],
        ]);

        $this->assertStringContainsString('href="https://example.test/account/subscriptions/"', $output);
        $this->assertStringContainsString('<i class="ri-bank-card-line ri-lg"></i>', $output);
        $this->assertStringContainsString('Manage subscription', $output);
        $this->assertStringNotContainsString('class="fs-14 fa', $output);
    }

    public function testProfileMenuRendersExtensionsWithoutSeparator(): void
    {
        $template = file_get_contents($this->viewsDirectory.'/Menu/profile.html.twig');

        $this->assertIsString($template);
        $this->assertStringContainsString('{{ inline|raw }}', $template);
        $this->assertStringNotContainsString('<li role="separator" class="divider"></li>', $template);
    }
}
