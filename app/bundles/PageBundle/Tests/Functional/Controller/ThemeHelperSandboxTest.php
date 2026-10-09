<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Helper\ThemeHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\PageBundle\Entity\Page;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Twig\Error\RuntimeError;

/**
 * Functional test ensuring that malicious Twig constructs in theme templates
 * are blocked by the sandbox and do not result in RCE or data leakage.
 *
 * Covers the attack surface described in GHSA-9fx4-7cmj-47vg:
 * - map/filter/reduce with PHP function callbacks (RCE vector)
 * - configGetParameter() for credential/secret leakage
 * - source() for arbitrary file read
 */
final class ThemeHelperSandboxTest extends MauticMysqlTestCase
{
    private string $themesDir;

    private static int $callbackInvocations = 0;

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function safeCollectionFiltersProvider(): iterable
    {
        yield 'sort without callback' => ['[3, 1, 2]|sort|join', '123'];
        yield 'sort with arrow function' => ['[3, 1, 2]|sort((a, b) => a <=> b)|join', '123'];
        yield 'map' => ['[1, 2, 3]|map(x => x * 2)|join', '246'];
        yield 'filter' => ['[1, 2, 3]|filter(x => x > 1)|join', '23'];
        yield 'reduce' => ['[1, 2, 3]|reduce((carry, x) => carry + x, 0)', '6'];
        yield 'find' => ['[1, 2, 3]|find(x => x > 1)', '2'];
    }

    #[DataProvider('safeCollectionFiltersProvider')]
    public function testSafeCollectionFiltersRenderOnPagePreview(string $expression, string $expected): void
    {
        $themeName = $this->createMaliciousTheme('{% block content %}<p id="collection-result">{{ '.$expression.' }}</p>{% endblock %}');
        $page      = $this->createPage($themeName);

        $this->client->request(Request::METHOD_GET, '/page/preview/'.$page->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextSame('#collection-result', $expected);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function callbackFiltersProvider(): iterable
    {
        foreach (['map', 'filter', 'reduce', 'sort', 'find'] as $filter) {
            yield $filter => [$filter];
        }
    }

    #[DataProvider('callbackFiltersProvider')]
    public function testPhpCallbacksAreRejectedBeforeExecution(string $filter): void
    {
        self::$callbackInvocations = 0;
        $this->assertIsCallable(self::class.'::recordCallbackInvocation');
        $callback    = json_encode(self::class.'::recordCallbackInvocation', JSON_THROW_ON_ERROR);
        $themeName   = $this->createMaliciousTheme('{% block content %}{{ [1, 2]|'.$filter.'('.$callback.') }}{% endblock %}');
        $themeHelper = self::getContainer()->get(ThemeHelper::class);

        try {
            $themeHelper->renderThemeTemplate('@themes/'.$themeName.'/html/page.html.twig', []);
            $this->fail('A PHP callable must be rejected by the theme sandbox.');
        } catch (RuntimeError $exception) {
            $this->assertStringContainsString('must be a Closure in sandbox mode', $exception->getMessage());
        } finally {
            $this->assertSame(0, self::$callbackInvocations, 'The callback must never execute, even if rendering subsequently fails.');
        }
    }

    public static function recordCallbackInvocation(mixed ...$arguments): int
    {
        ++self::$callbackInvocations;

        return 1;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->themesDir = self::getContainer()->getParameter('kernel.project_dir').'/themes';

        foreach (glob($this->themesDir.'/sandbox_test_*') ?: [] as $dir) {
            $this->removeDirectory($dir);
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function harmfulTwigPayloadsProvider(): iterable
    {
        yield 'RCE via map with system callback' => [
            "{% block content %}<pre>{{ ['id']|map('system')|join }}</pre>{% endblock %}",
        ];

        yield 'RCE via filter with system callback' => [
            "{% block content %}<pre>{{ ['id']|filter('system')|join }}</pre>{% endblock %}",
        ];

        yield 'RCE via reduce with system callback' => [
            "{% block content %}<pre>{{ ['id']|reduce('system') }}</pre>{% endblock %}",
        ];

        yield 'RCE via sort with system callback' => [
            "{% block content %}<pre>{{ ['id', 0]|sort('system')|join }}</pre>{% endblock %}",
        ];

        yield 'RCE via find with system callback' => [
            "{% block content %}<pre>{{ ['id', 0]|find('system') }}</pre>{% endblock %}",
        ];

        yield 'credential leak via configGetParameter db_password' => [
            "{% block content %}<pre>{{ configGetParameter('db_password') }}</pre>{% endblock %}",
        ];

        yield 'secret leak via configGetParameter mautic.secret_key' => [
            "{% block content %}<pre>{{ configGetParameter('mautic.secret_key') }}</pre>{% endblock %}",
        ];

        yield 'arbitrary file read via source filter' => [
            "{% block content %}<pre>{{ source('/etc/passwd') }}</pre>{% endblock %}",
        ];
    }

    #[DataProvider('harmfulTwigPayloadsProvider')]
    public function testHarmfulTwigPayloadsAreBlockedOnPagePreview(string $payload): void
    {
        $themeName = $this->createMaliciousTheme($payload);
        $page      = $this->createPage($themeName);

        $this->client->request(Request::METHOD_GET, '/page/preview/'.$page->getId());

        $content = (string) $this->client->getResponse()->getContent();

        $this->assertStringNotContainsString('uid=', $content, 'RCE output must not appear in response');
        $this->assertStringNotContainsString('root:', $content, 'File read output must not appear in response');
        $this->assertStringNotContainsString('db_password', $content, 'DB password must not be leaked');
    }

    public function testSafeThemeTemplateRendersSuccessfully(): void
    {
        $themeName = $this->createMaliciousTheme(
            '{% block content %}<p>Hello Mautic</p>{% endblock %}'
        );
        $page = $this->createPage($themeName);

        $this->client->request(Request::METHOD_GET, '/page/preview/'.$page->getId());

        $response = $this->client->getResponse();

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString(
            'Hello Mautic',
            (string) $response->getContent()
        );
    }

    private function createMaliciousTheme(string $pageContent): string
    {
        $name = 'sandbox_test_'.uniqid();
        $dir  = $this->themesDir.'/'.$name;

        mkdir($dir.'/html', 0777, true);

        file_put_contents($dir.'/config.json', json_encode([
            'name'     => $name,
            'author'   => 'Test',
            'builder'  => ['legacy'],
            'features' => ['page'],
        ]));

        file_put_contents(
            $dir.'/html/base.html.twig',
            '<!DOCTYPE html><html><body>{% block content %}{% endblock %}</body></html>'
        );

        file_put_contents(
            $dir.'/html/page.html.twig',
            "{% extends '@themes/".$name."/html/base.html.twig' %}\n".$pageContent
        );

        return $name;
    }

    private function createPage(string $themeName): Page
    {
        $page = new Page();
        $page->setTitle('SSTI Test Page');
        $page->setAlias('ssti-test-'.uniqid());
        $page->setTemplate($themeName);
        $page->setIsPublished(true);
        $page->setPublicPreview(true);
        $page->setContent(['main' => 'test content']);

        $this->em->persist($page);
        $this->em->flush();

        return $page;
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $item) {
            if ('.' === $item || '..' === $item) {
                continue;
            }
            $path = $dir.'/'.$item;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($dir);
    }
}
