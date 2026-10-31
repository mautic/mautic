<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Ensures the GrapesJS builder can still render a theme template through the
 * sandboxed Twig environment after the allowlist introduced for
 * GHSA-h7pm-79gc-8867. builderAction() renders the theme (which extends the
 * core theme base) inside the sandbox with a live entity and framework globals
 * in scope; this is the request that populates the builder iframe, so a
 * sandbox regression here would break the editor.
 */
final class GrapesJsBuilderRenderTest extends MauticMysqlTestCase
{
    public function testBuilderRendersPageThemeThroughSandbox(): void
    {
        $this->client->request(
            Request::METHOD_GET,
            '/s/grapesjsbuilder/page/new',
            ['template' => 'blank']
        );

        $this->assertResponseIsSuccessful();
        // The rendered blank theme content that GrapesJS loads into the iframe.
        $this->assertStringContainsString('Hello there!', (string) $this->client->getResponse()->getContent());
    }
}
