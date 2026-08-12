<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;

#[Group('database')]
final class ConfigControllerTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        $this->configParams['validate_remote_domains'] = 'validate remote domains enabled' === $this->dataName();

        parent::setUp();
    }

    /**
     * @return iterable<string, array{bool}>
     */
    public static function dataListOfRemoteDomainsVisibility(): iterable
    {
        yield 'validate remote domains disable' => [false];
        yield 'validate remote domains enabled' => [true];
    }

    #[DataProvider('dataListOfRemoteDomainsVisibility')]
    public function testListOfRemoteDomainsVisibility(bool $enabled): void
    {
        $this->client->request(Request::METHOD_GET, '/s/config/edit');
        self::assertResponseIsSuccessful();

        $content = $this->client->getResponse()->getContent();
        $label   = 'List of allowed remote domains (one per line)';

        if ($enabled) {
            $this->assertStringContainsString($label, (string) $content);
        } else {
            $this->assertStringNotContainsString($label, (string) $content);
        }
    }
}
