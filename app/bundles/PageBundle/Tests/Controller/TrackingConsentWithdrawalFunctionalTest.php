<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadDevice;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Cookie as ResponseCookie;
use Symfony\Component\HttpFoundation\Request;

/**
 * A consent tool withdraws tracking consent through /mtc/withdraw: Mautic expires its tracking cookies and forgets
 * the device of that browser, and only that one.
 */
final class TrackingConsentWithdrawalFunctionalTest extends MauticMysqlTestCase
{
    private const BROWSER_DEVICE = 'consentwithdrawal000001';
    private const STORED_COPY    = 'consentwithdrawal000002';
    private const OTHER_DEVICE   = 'consentwithdrawal000003';

    protected $useCleanupRollback = false;

    /**
     * @return iterable<string, array{string|null, string|null, string[]}>
     */
    public static function heldDeviceIdProvider(): iterable
    {
        yield 'in the cookie' => [self::BROWSER_DEVICE, null, [self::BROWSER_DEVICE]];
        yield 'in localStorage, sent by the tracking script' => [null, self::STORED_COPY, [self::STORED_COPY]];
        yield 'a different one in each' => [self::BROWSER_DEVICE, self::STORED_COPY, [self::BROWSER_DEVICE, self::STORED_COPY]];
        yield 'nowhere' => [null, null, []];
    }

    /**
     * @param string[] $forgotten
     */
    #[DataProvider('heldDeviceIdProvider')]
    public function testWithdrawalForgetsTheDevicesOfThisBrowserOnly(?string $cookie, ?string $parameter, array $forgotten): void
    {
        $contact = $this->createContactWithDevices();
        if (null !== $cookie) {
            $this->client->getCookieJar()->set(new Cookie('mautic_device_id', $cookie));
        }
        $this->client->getCookieJar()->set(new Cookie('mtc_id', (string) $contact->getId()));
        $this->client->getCookieJar()->set(new Cookie('mautic_referer_id', '1'));
        $contacts = $this->em->getRepository(Lead::class)->count([]);

        $this->client->request(Request::METHOD_POST, '/mtc/withdraw', null === $parameter ? [] : ['mautic_device_id' => $parameter]);

        $this->assertResponseIsSuccessful();
        $this->assertSame(['success' => 1], json_decode((string) $this->client->getResponse()->getContent(), true));

        foreach (['mautic_device_id', 'mtc_id', 'mautic_referer_id', 'mautic_session_id'] as $name) {
            $expired = $this->findResponseCookie($name);
            $this->assertInstanceOf(ResponseCookie::class, $expired, $name.' is sent back');
            $this->assertTrue($expired->isCleared(), $name.' is expired');
            $this->assertSame(ResponseCookie::SAMESITE_NONE, $expired->getSameSite(), $name.' also expires for a request sent by another site');
        }

        $kept = array_values(array_diff([self::BROWSER_DEVICE, self::OTHER_DEVICE, self::STORED_COPY], $forgotten));
        sort($kept);
        $this->assertSame($kept, $this->getTrackingIds($contact));
        $this->assertSame($contacts, $this->em->getRepository(Lead::class)->count([]), 'no contact is created for the request');
    }

    public function testAForgottenDeviceIdNoLongerLeadsBackToTheContact(): void
    {
        $contact = $this->createContactWithDevices();
        $this->client->getCookieJar()->set(new Cookie('mautic_device_id', self::BROWSER_DEVICE));
        $this->client->request(Request::METHOD_POST, '/mtc/withdraw');
        $this->assertResponseIsSuccessful();

        // A new browser, so nothing a service kept from the request before is used
        $this->client->enableReboot();
        $this->client->getCookieJar()->clear();
        $this->client->getCookieJar()->set(new Cookie('mautic_device_id', self::BROWSER_DEVICE));
        $this->client->request(Request::METHOD_GET, '/mtc');
        $this->assertResponseIsSuccessful();

        $this->assertNotSame($contact->getId(), json_decode((string) $this->client->getResponse()->getContent(), true)['id']);
    }

    public function testAContactIdAloneForgetsNothing(): void
    {
        $contact = $this->createContactWithDevices();
        $this->client->getCookieJar()->set(new Cookie('mtc_id', (string) $contact->getId()));

        $this->client->request(Request::METHOD_POST, '/mtc/withdraw', ['mtc_id' => $contact->getId()]);

        $this->assertResponseIsSuccessful();
        $this->assertSame([self::BROWSER_DEVICE, self::STORED_COPY, self::OTHER_DEVICE], $this->getTrackingIds($contact));
    }

    public function testAGetRequestForgetsNothing(): void
    {
        $contact = $this->createContactWithDevices();
        $this->client->getCookieJar()->set(new Cookie('mautic_device_id', self::BROWSER_DEVICE));

        $this->client->request(Request::METHOD_GET, '/mtc/withdraw');

        $this->assertResponseStatusCodeSame(404);
        $this->assertContains(self::BROWSER_DEVICE, $this->getTrackingIds($contact));
    }

    private function createContactWithDevices(): Lead
    {
        $contact = new Lead();
        $contact->setEmail('consent-withdrawal@example.com');
        $this->em->persist($contact);

        foreach ([self::BROWSER_DEVICE => 'desktop', self::STORED_COPY => 'tablet', self::OTHER_DEVICE => 'smartphone'] as $trackingId => $type) {
            $device = new LeadDevice();
            $device->setLead($contact);
            $device->setTrackingId($trackingId);
            $device->setDateAdded(new \DateTime());
            $device->setDevice($type);
            $this->em->persist($device);
        }

        $this->em->flush();
        $this->logoutUser();

        return $contact;
    }

    /**
     * @return string[] the tracking IDs the contact's devices still have
     */
    private function getTrackingIds(Lead $contact): array
    {
        $this->em->clear();

        $trackingIds = [];
        foreach ($this->em->getRepository(LeadDevice::class)->findBy(['lead' => $contact->getId()]) as $device) {
            if (null !== $device->getTrackingId()) {
                $trackingIds[] = $device->getTrackingId();
            }
        }
        sort($trackingIds);

        return $trackingIds;
    }

    private function findResponseCookie(string $name): ?ResponseCookie
    {
        foreach ($this->client->getResponse()->headers->getCookies() as $cookie) {
            if ($name === $cookie->getName()) {
                return $cookie;
            }
        }

        return null;
    }
}
