<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\PageBundle\Entity\Page;
use Symfony\Component\HttpFoundation\Request;

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

    #[\PHPUnit\Framework\Attributes\DataProvider('harmfulTwigPayloadsProvider')]
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
