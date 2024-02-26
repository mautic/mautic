<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Twig\Extension;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

class ConfigExtensionTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        $this->configParams['editor_fonts'] = [
            [
                'name' => 'Courier New',
                'font' => 'Courier New, Courier, monospace',
                'url'  => 'https://custom-font.test/courier.css',
            ],
            [
                'name' => 'Arial',
                'font' => 'Arial, Helvetica, sans-serif',
                'url'  => 'https://custom-font.test/arial.css',
            ],
        ];

        parent::setUp();
    }

    public function testSortedEditorFonts(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/');

        Assert::assertStringContainsString(
            '[{"name":"Arial","font":"Arial, Helvetica, sans-serif","url":"https:\/\/custom-font.test\/arial.css"},{"name":"Courier New","font":"Courier New, Courier, monospace","url":"https:\/\/custom-font.test\/courier.css"}];',
            $crawler->html()
        );
    }
}
