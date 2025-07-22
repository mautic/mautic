<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\PageBundle\Entity\Page;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

final class DeviceTrackingServiceClearCookiesTest extends MauticMysqlTestCase
{
    /**
     * @return array<string, array{bool}>
     */
    public function blockedTrackingCookieDataProvider(): array
    {
        return [
            'with blocked tracking cookie'    => [true],
            'without blocked tracking cookie' => [false],
        ];
    }

    /**
     * @dataProvider blockedTrackingCookieDataProvider
     */
    public function testClearTrackingCookiesBehavior(bool $shouldClearCookies): void
    {
        $this->logoutUser();

        $page = new Page();
        $page->setIsPublished(true);
        $page->setTitle('Test Page for Clear Tracking Cookies');
        $page->setAlias('test-clear-cookies');
        $page->setCustomHtml('<html><body><h1>Test Page</h1></body></html>');
        $this->em->persist($page);
        $this->em->flush();

        if ($shouldClearCookies) {
            $this->client->getCookieJar()->set(new \Symfony\Component\BrowserKit\Cookie('Blocked-Tracking', '1'));
        }

        $this->client->request(Request::METHOD_GET, '/test-clear-cookies');
        $this->assertResponseIsSuccessful();

        $deviceIdCookieCleared = false;
        $mtcIdCookieCleared    = false;

        foreach ($this->client->getResponse()->headers->getCookies() as $cookie) {
            // Check if tracking cookies are being deleted (empty value + past expiration)
            $cookieIsDeleted = '' === $cookie->getValue() && $cookie->getExpiresTime() < time();

            if ('mautic_device_id' === $cookie->getName() && $cookieIsDeleted) {
                $deviceIdCookieCleared = true;
            }

            if ('mtc_id' === $cookie->getName() && $cookieIsDeleted) {
                $mtcIdCookieCleared = true;
            }
        }

        Assert::assertSame($shouldClearCookies, $deviceIdCookieCleared);
        Assert::assertSame($shouldClearCookies, $mtcIdCookieCleared);
    }
}
