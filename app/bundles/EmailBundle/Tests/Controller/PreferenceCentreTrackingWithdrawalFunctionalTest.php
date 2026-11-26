<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Helper\MailHashHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadDevice;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Cookie as ResponseCookie;
use Symfony\Component\HttpFoundation\Request;

/**
 * A contact who turns tracking off in the preference centre is forgotten: the tracking cookies expire and the device IDs
 * kept by a browser no longer lead back to them.
 */
final class PreferenceCentreTrackingWithdrawalFunctionalTest extends MauticMysqlTestCase
{
    private const FIELD         = 'lead_contact_frequency_rules[allow_activity_tracking]';
    private const EMAIL_ADDRESS = 'withdrawal@example.com';
    private const TRACKING_ID   = 'withdrawaldevice0000001';
    private const OTHER_DEVICE  = 'withdrawaldevice0000002';

    protected $useCleanupRollback = false;

    protected function setUp(): void
    {
        $this->configParams['show_contact_preferences'] = 1;

        parent::setUp();
    }

    public function testTurningTrackingOffExpiresTheCookiesAndForgetsEveryDevice(): void
    {
        [$contact, $stat] = $this->createTrackedContactWithSentEmail();
        $this->client->getCookieJar()->set(new Cookie('mautic_device_id', self::TRACKING_ID));
        $this->client->getCookieJar()->set(new Cookie('mtc_id', (string) $contact->getId()));
        $this->client->getCookieJar()->set(new Cookie('mautic_referer_id', '1'));
        $this->client->getCookieJar()->set(new Cookie('mautic_session_id', 'legacysession'));
        $this->client->getCookieJar()->set(new Cookie('legacysession', (string) $contact->getId()));

        $heldByTheBrowser = $this->submitTrackingChoice($stat, '0');

        foreach (['mautic_device_id', 'mtc_id', 'mautic_referer_id', 'mautic_session_id', 'legacysession'] as $name) {
            $cookie = $this->findResponseCookie($name);
            $this->assertInstanceOf(ResponseCookie::class, $cookie, $name.' is sent back');
            $this->assertTrue($cookie->isCleared(), $name.' is expired');
            $this->assertSame(ResponseCookie::SAMESITE_NONE, $cookie->getSameSite(), $name.' also expires for a request sent by another site');
        }

        $this->assertNotNull($heldByTheBrowser);
        $this->assertSame([], array_intersect([self::TRACKING_ID, self::OTHER_DEVICE, $heldByTheBrowser], $this->getTrackingIds($contact)), 'no device keeps an ID a browser holds');
    }

    /**
     * @return iterable<string, array{array<string, string>, bool}>
     */
    public static function oldDeviceIdProvider(): iterable
    {
        yield 'kept in a cookie' => [[], true];
        yield 'kept in localStorage, sent by the tracking script' => [['mautic_device_id' => self::TRACKING_ID], false];
        yield 'of another device of the contact' => [['mautic_device_id' => self::OTHER_DEVICE], false];
    }

    /**
     * @param array<string, string> $parameters
     */
    #[DataProvider('oldDeviceIdProvider')]
    public function testAnOldDeviceIdNoLongerLeadsBackToTheContact(array $parameters, bool $asCookie): void
    {
        [$contact, $stat] = $this->createTrackedContactWithSentEmail();
        $this->submitTrackingChoice($stat, '0');

        $this->em->clear();
        $this->assertNotInstanceOf(LeadDevice::class, $this->em->getRepository(LeadDevice::class)->findOneBy(['trackingId' => $parameters['mautic_device_id'] ?? self::TRACKING_ID]), 'the ID belongs to no device');
        $this->assertNotSame($contact->getId(), $this->getContactIdRecognisedBy($parameters, $asCookie));
    }

    public function testTurningTrackingBackOnDoesNotRestoreTheOldIdentifiers(): void
    {
        [$contact, $stat] = $this->createTrackedContactWithSentEmail();
        $this->submitTrackingChoice($stat, '0');
        $this->submitTrackingChoice($stat, '1');

        $this->assertSame([], array_intersect([self::TRACKING_ID, self::OTHER_DEVICE], $this->getTrackingIds($contact)));

        $this->assertNotSame($contact->getId(), $this->getContactIdRecognisedBy([], true));
    }

    public function testAContactWhoKeepsTrackingOnIsStillRecognised(): void
    {
        [$contact, $stat] = $this->createTrackedContactWithSentEmail();
        $this->submitTrackingChoice($stat, '1');

        $this->assertSame($contact->getId(), $this->getContactIdRecognisedBy([], true));
    }

    /**
     * @return array{Lead, Stat}
     */
    private function createTrackedContactWithSentEmail(): array
    {
        $contact = new Lead();
        $contact->setEmail(self::EMAIL_ADDRESS);
        $this->em->persist($contact);

        foreach ([self::TRACKING_ID => 'desktop', self::OTHER_DEVICE => 'smartphone'] as $trackingId => $type) {
            $device = new LeadDevice();
            $device->setLead($contact);
            $device->setTrackingId($trackingId);
            $device->setDateAdded(new \DateTime());
            $device->setDevice($type);
            $this->em->persist($device);
        }

        $email = new Email();
        $email->setName('Tracking withdrawal');
        $email->setSubject('Tracking withdrawal');
        $email->setEmailType('template');
        $email->setTemplate('blank');
        $this->em->persist($email);

        $stat = new Stat();
        $stat->setTrackingHash('tracking_withdrawal_hash');
        $stat->setEmailAddress(self::EMAIL_ADDRESS);
        $stat->setLead($contact);
        $stat->setDateSent((new \DateTime())->modify('-80 second'));
        $stat->setEmail($email);
        $this->em->persist($stat);
        $this->em->flush();

        $this->logoutUser();

        return [$contact, $stat];
    }

    /**
     * @return string|null the device ID the browser held when it sent the choice
     */
    private function submitTrackingChoice(Stat $stat, string $choice): ?string
    {
        $hash    = self::getContainer()->get(MailHashHelper::class)->getEmailHash((string) $stat->getEmailAddress());
        $crawler = $this->client->request(Request::METHOD_GET, "/email/unsubscribe/{$stat->getTrackingHash()}/{$stat->getEmailAddress()}/{$hash}");
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('form')->form();
        $form[self::FIELD]->setValue($choice);
        $heldByTheBrowser = $this->client->getCookieJar()->get('mautic_device_id')?->getValue();
        $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        return $heldByTheBrowser;
    }

    /**
     * @return array<int, string|null>
     */
    private function getTrackingIds(Lead $contact): array
    {
        $this->em->clear();

        return array_map(
            static fn (LeadDevice $device): ?string => $device->getTrackingId(),
            $this->em->getRepository(LeadDevice::class)->findBy(['lead' => $contact->getId()])
        );
    }

    /**
     * Asks Mautic who the browser is, as the tracking script does, from a browser that holds nothing but the old device ID.
     *
     * @param array<string, string> $parameters
     */
    private function getContactIdRecognisedBy(array $parameters, bool $asCookie): ?int
    {
        // A new browser, so nothing a service kept from the requests before is used
        $this->client->enableReboot();
        $this->client->getCookieJar()->clear();
        if ($asCookie) {
            $this->client->getCookieJar()->set(new Cookie('mautic_device_id', self::TRACKING_ID));
        }

        $this->client->request(Request::METHOD_GET, '/mtc', $parameters);
        $this->assertResponseIsSuccessful();

        return json_decode((string) $this->client->getResponse()->getContent(), true)['id'];
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
