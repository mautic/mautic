<?php

declare(strict_types=1);

namespace Mautic\ConfigBundle\Tests\Controller;

use Mautic\ConfigBundle\Model\SysinfoModel;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Component\HttpFoundation\Request;

final class SysinfoControllerTest extends MauticMysqlTestCase
{
    public function testDbInfoIsShown(): void
    {
        $sysinfoModel = static::getContainer()->get(SysinfoModel::class);
        $this->assertInstanceOf(SysinfoModel::class, $sysinfoModel);
        $dbInfo = $sysinfoModel->getDbInfo();

        // Request sysinfo page
        $crawler = $this->client->request(Request::METHOD_GET, '/s/sysinfo');
        self::assertResponseIsSuccessful();

        $dbVersion       = $crawler->filterXPath("//td[@id='dbinfo-version']")->text();
        $dbDriver        = $crawler->filterXPath("//td[@id='dbinfo-driver']")->text();
        $dbPlatform      = $crawler->filterXPath("//td[@id='dbinfo-platform']")->text();
        $recommendations = $crawler->filter('#recommendations');

        $this->assertSame($dbInfo['version'], $dbVersion);
        $this->assertSame($dbInfo['driver'], $dbDriver);
        $this->assertSame($dbInfo['platform'], $dbPlatform);
        $this->assertGreaterThan(0, $recommendations->count());
    }
}
