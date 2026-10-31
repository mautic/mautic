<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Controller;

use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadDevice;
use Mautic\PageBundle\Entity\Hit;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Request;

/**
 * The tracking pixel and the /mtc endpoint still answer a visitor Mautic doesn't track, but don't recognise them,
 * set no tracking cookie and store nothing that identifies them.
 */
final class UntrackedVisitorPixelFunctionalTest extends MauticMysqlTestCase
{
    private const TRACKING_ID = 'untrackedpixeldevice000';

    private const EXCLUDED_IP = '203.0.113.64';

    protected $useCleanupRollback = false;

    protected function setUp(): void
    {
        $this->configParams['do_not_track_ips'] = [self::EXCLUDED_IP];

        parent::setUp();
    }

    /**
     * @return iterable<string, array{array<string, string>}>
     */
    public static function privacySignalProvider(): iterable
    {
        yield 'Do Not Track' => [['HTTP_DNT' => '1']];
        yield 'Global Privacy Control' => [['HTTP_SEC_GPC' => '1']];
    }

    /**
     * @return iterable<string, array{string, string, array<string, string>}>
     */
    public static function endpointAndPrivacySignalProvider(): iterable
    {
        foreach (self::privacySignalProvider() as $signal => [$server]) {
            yield '/mtc, '.$signal => [Request::METHOD_GET, '/mtc', $server];
            yield '/mtc/event, '.$signal => [Request::METHOD_POST, '/mtc/event', $server];
        }
    }

    /**
     * @param array<string, string> $server
     */
    #[DataProvider('endpointAndPrivacySignalProvider')]
    public function testMtcDoesNotRecogniseAPreviouslyTrackedVisitor(string $method, string $uri, array $server): void
    {
        $contact = $this->createTrackedContact();
        $this->client->getCookieJar()->set(new Cookie('mautic_device_id', self::TRACKING_ID));
        $this->client->getCookieJar()->set(new Cookie('mtc_id', (string) $contact->getId()));

        $this->client->request($method, $uri, ['page_url' => 'https://example.com/'], [], $server + ['REMOTE_ADDR' => '203.0.113.60']);
        $this->assertResponseIsSuccessful();

        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertNull($response['id'] ?? null, 'the contact is not recognised');
        $this->assertNull($response['sid'] ?? null, 'the device is not recognised');
        $this->assertNull($response['device_id'] ?? null, 'the device is not recognised');
        $this->assertNoTrackingCookieSet();
        $this->assertIpAddressNotStored('203.0.113.60');
        $this->assertCount(0, $this->em->getRepository(Hit::class)->findAll());
    }

    /**
     * @param array<string, string> $server
     */
    #[DataProvider('endpointAndPrivacySignalProvider')]
    public function testMtcDoesNotCreateAContactForANewVisitor(string $method, string $uri, array $server): void
    {
        $this->logoutUser();
        $contacts = $this->em->getRepository(Lead::class)->count([]);

        $this->client->request($method, $uri, ['page_url' => 'https://example.com/'], [], $server + ['REMOTE_ADDR' => '203.0.113.61']);
        $this->assertResponseIsSuccessful();

        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertNull($response['id'] ?? null);
        $this->assertNoTrackingCookieSet();
        $this->assertIpAddressNotStored('203.0.113.61');
        $this->assertSame($contacts, $this->em->getRepository(Lead::class)->count([]));
    }

    /**
     * @param array<string, string> $server
     */
    #[DataProvider('privacySignalProvider')]
    public function testPixelDoesNotRecogniseAPreviouslyTrackedVisitor(array $server): void
    {
        $this->createTrackedContact();
        $this->client->getCookieJar()->set(new Cookie('mautic_device_id', self::TRACKING_ID));

        $this->client->request(Request::METHOD_GET, '/mtracking.gif', ['page_url' => 'https://example.com/'], [], $server + ['REMOTE_ADDR' => '203.0.113.62']);
        $this->assertResponseIsSuccessful();

        $this->assertNoTrackingCookieSet();
        $this->assertIpAddressNotStored('203.0.113.62');
        $this->assertCount(0, $this->em->getRepository(Hit::class)->findAll());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function endpointProvider(): iterable
    {
        yield '/mtc' => [Request::METHOD_GET, '/mtc'];
        yield '/mtc/event' => [Request::METHOD_POST, '/mtc/event'];
    }

    #[DataProvider('endpointProvider')]
    public function testMtcStillRecognisesATrackedVisitor(string $method, string $uri): void
    {
        $contact = $this->createTrackedContact();
        $this->client->getCookieJar()->set(new Cookie('mautic_device_id', self::TRACKING_ID));

        $this->client->request($method, $uri, ['page_url' => 'https://example.com/'], [], ['REMOTE_ADDR' => '203.0.113.63']);
        $this->assertResponseIsSuccessful();

        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame($contact->getId(), $response['id']);
        $this->assertNotEmpty($response['device_id']);
    }

    /**
     * @return iterable<string, array{string, string, array<string, string>}>
     */
    public static function excludedVisitorProvider(): iterable
    {
        $bot = ['HTTP_USER_AGENT' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', 'REMOTE_ADDR' => '203.0.113.65'];

        yield '/mtc, excluded IP address' => [Request::METHOD_GET, '/mtc', ['REMOTE_ADDR' => self::EXCLUDED_IP]];
        yield '/mtc/event, excluded IP address' => [Request::METHOD_POST, '/mtc/event', ['REMOTE_ADDR' => self::EXCLUDED_IP]];
        yield '/mtc, bot' => [Request::METHOD_GET, '/mtc', $bot];
        yield '/mtc/event, bot' => [Request::METHOD_POST, '/mtc/event', $bot];
    }

    /**
     * Excluded addresses and bots aren't tracked either, so they aren't recognised from an earlier cookie.
     *
     * @param array<string, string> $server
     */
    #[DataProvider('excludedVisitorProvider')]
    public function testMtcDoesNotRecogniseAnExcludedVisitor(string $method, string $uri, array $server): void
    {
        $this->createTrackedContact();
        $this->client->getCookieJar()->set(new Cookie('mautic_device_id', self::TRACKING_ID));

        $this->client->request($method, $uri, ['page_url' => 'https://example.com/'], [], $server);
        $this->assertResponseIsSuccessful();

        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertNull($response['id'] ?? null);
        $this->assertNull($response['device_id'] ?? null);
        $this->assertNoTrackingCookieSet();
    }

    private function createTrackedContact(): Lead
    {
        $contact = new Lead();
        $contact->setEmail('pixel@example.com');
        $this->em->persist($contact);

        $device = new LeadDevice();
        $device->setLead($contact);
        $device->setTrackingId(self::TRACKING_ID);
        $device->setDateAdded(new \DateTime());
        $device->setDevice('desktop');
        $this->em->persist($device);
        $this->em->flush();

        $this->logoutUser();

        return $contact;
    }

    private function assertNoTrackingCookieSet(): void
    {
        $set = [];
        foreach ($this->client->getResponse()->headers->getCookies() as $cookie) {
            // Deleting a cookie is fine, writing one is not. A deleted cookie comes with an empty value.
            if ('' !== (string) $cookie->getValue()) {
                $set[] = $cookie->getName();
            }
        }

        $this->assertSame([], array_values(array_intersect(['mautic_device_id', 'mtc_id', 'mautic_referer_id', 'mautic_session_id'], $set)));
    }

    private function assertIpAddressNotStored(string $address): void
    {
        $this->assertNotInstanceOf(IpAddress::class, $this->em->getRepository(IpAddress::class)->findOneBy(['ipAddress' => $address]), 'the visitor\'s IP address is not stored');
    }
}
