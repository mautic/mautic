<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Functional\EventListener;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

class EditorFontsSubscriberTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        $this->configParams['editor_fonts'] = [
            [
                'name' => 'Arial',
                'font' => 'Arial, Helvetica, sans-serif',
                'url'  => 'https://custom-font.test/arial.css',
            ],
            [
                'name' => 'Courier New',
                'font' => 'Courier New, Courier, monospace',
                'url'  => 'https://custom-font.test/courier.css',
            ],
        ];

        parent::setUp();
    }

    public function testEditorFontsAreLoadedWithDefinedConfigValues(): void
    {
        $crawler  = $this->client->request(Request::METHOD_GET, '/');
        $response = $crawler->html();

        Assert::assertTrue($this->client->getResponse()->isOk());

        Assert::assertStringContainsString(
            'https://custom-font.test/arial.css',
            $response
        );

        Assert::assertStringContainsString(
            'https://custom-font.test/courier.css',
            $response
        );
    }
}
