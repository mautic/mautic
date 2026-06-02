<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;

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
        Assert::assertTrue($this->client->getResponse()->isOk());

        $content = $this->client->getResponse()->getContent();
        $label   = 'List of allowed remote domains (one per line)';

        if ($enabled) {
            Assert::assertStringContainsString($label, $content);
        } else {
            Assert::assertStringNotContainsString($label, $content);
        }
    }
}
