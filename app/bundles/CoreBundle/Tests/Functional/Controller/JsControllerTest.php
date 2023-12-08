<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;

/**
 * This test is breaking other tests, so running it in a separate process.
 *
 * @runTestsInSeparateProcesses
 *
 * @preserveGlobalState disabled
 */
final class JsControllerTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        $this->configParams['google_analytics_id']                   = 'G-F3825DS9CD';
        $this->configParams['google_analytics_trackingpage_enabled'] = true;
        $this->configParams['google_analytics_anonymize_ip']         = 'testIndexActionRendersSuccessfullyWithAnonymizeIp' === $this->getName();
        parent::setUp();
    }

    public function testIndexActionRendersSuccessfully(): void
    {
        $this->client->request('GET', '/mtc.js');
        self::assertResponseIsSuccessful();
        Assert::assertStringContainsString('https://www.googletagmanager.com/gtag/js?id=G-F3825DS9CD', $this->client->getResponse()->getContent());
        Assert::assertStringContainsString('gtag(\'config\',\'G-F3825DS9CD\')', $this->client->getResponse()->getContent());
    }

    public function testIndexActionRendersSuccessfullyWithAnonymizeIp(): void
    {
        $this->client->request('GET', '/mtc.js');
        self::assertResponseIsSuccessful();
        Assert::assertStringContainsString('https://www.googletagmanager.com/gtag/js?id=G-F3825DS9CD', $this->client->getResponse()->getContent());
        Assert::assertStringContainsString('gtag(\'config\',\'G-F3825DS9CD\',{"anonymize_ip":!0})', $this->client->getResponse()->getContent());
    }
}
