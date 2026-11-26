<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Controller;

use Mautic\CoreBundle\Helper\ClickthroughHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadDevice;
use Mautic\LeadBundle\Helper\ContactTrackingPreferenceHelper;
use Mautic\PageBundle\Entity\Redirect;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Request;

/**
 * A contact who turned tracking off gets no identifier back that would let Mautic or the tracking script recognise
 * them again, even when a cookie or an email link identifies them.
 */
final class OptedOutContactIdentifiersFunctionalTest extends MauticMysqlTestCase
{
    private const TRACKING_ID = 'optedoutcontactdevice00';

    private const TRACKING_COOKIES = ['mautic_device_id', 'mautic_referer_id', 'mtc_id'];

    protected $useCleanupRollback = false;

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function endpointProvider(): iterable
    {
        yield '/mtc' => [Request::METHOD_GET, '/mtc'];
        yield '/mtc/event' => [Request::METHOD_POST, '/mtc/event'];
    }

    #[DataProvider('endpointProvider')]
    public function testMtcGivesNoIdentifiersToAContactWhoTurnedTrackingOff(string $method, string $uri): void
    {
        $this->createContactWithTrackedDevice(false);
        $this->client->getCookieJar()->set(new Cookie('mautic_device_id', self::TRACKING_ID));

        $this->client->request($method, $uri, ['page_url' => 'https://example.com/']);
        $this->assertResponseIsSuccessful();

        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertNull($response['id'], 'the contact ID is not handed to the script');
        $this->assertNull($response['sid']);
        $this->assertNull($response['device_id'], 'the device ID is not handed to the script');
        $this->assertSame([], $this->getTrackingCookiesWritten());
    }

    #[DataProvider('endpointProvider')]
    public function testMtcStillRecognisesAContactWhoAllowsTracking(string $method, string $uri): void
    {
        $contact = $this->createContactWithTrackedDevice(true);
        $this->client->getCookieJar()->set(new Cookie('mautic_device_id', self::TRACKING_ID));

        $this->client->request($method, $uri, ['page_url' => 'https://example.com/']);
        $this->assertResponseIsSuccessful();

        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame($contact->getId(), $response['id']);
        $this->assertNotEmpty($response['device_id']);
    }

    public function testEmailLinkWritesNoTrackingCookieForAContactWhoTurnedTrackingOff(): void
    {
        $this->clickEmailLink($this->createContact(false));

        $this->assertResponseStatusCodeSame(302);
        $this->assertSame([], $this->getTrackingCookiesWritten());
    }

    public function testEmailLinkStillWritesTrackingCookiesForAContactWhoAllowsTracking(): void
    {
        $contact = $this->createContact(true);

        $this->clickEmailLink($contact);

        $this->assertResponseStatusCodeSame(302);
        $this->assertSame(self::TRACKING_COOKIES, $this->getTrackingCookiesWritten());

        $device = $this->em->getRepository(LeadDevice::class)->findOneBy(['lead' => $contact]);
        $this->assertInstanceOf(LeadDevice::class, $device);
        $this->assertNotNull($device->getTrackingId());
    }

    public function testAVisitorWithoutASavedContactAllowsTracking(): void
    {
        $this->assertTrue(self::getContainer()->get(ContactTrackingPreferenceHelper::class)->allowsActivityTracking(new Lead()));
    }

    private function createContact(bool $allowsTracking): Lead
    {
        $contact = new Lead();
        $contact->setEmail('opted-out@example.com');
        $this->em->persist($contact);
        $this->em->flush();

        self::getContainer()->get(ContactTrackingPreferenceHelper::class)->setActivityTracking($contact, $allowsTracking, 'test');

        $this->logoutUser();

        return $contact;
    }

    private function createContactWithTrackedDevice(bool $allowsTracking): Lead
    {
        $contact = $this->createContact($allowsTracking);

        $device = new LeadDevice();
        $device->setLead($contact);
        $device->setTrackingId(self::TRACKING_ID);
        $device->setDateAdded(new \DateTime());
        $device->setDevice('desktop');
        $this->em->persist($device);
        $this->em->flush();

        return $contact;
    }

    private function clickEmailLink(Lead $contact): void
    {
        $stat = new Stat();
        $stat->setTrackingHash('optedoutcontactstat0001');
        $stat->setDateSent(new \DateTime());
        $stat->setEmailAddress((string) $contact->getEmail());
        $stat->setLead($contact);
        $this->em->persist($stat);

        $redirect = new Redirect();
        $redirect->setUrl('https://example.com/');
        $redirect->setRedirectId('optedoutcontactredirect');
        $this->em->persist($redirect);
        $this->em->flush();

        $ct = ClickthroughHelper::encodeArrayForUrl([
            'source' => [],
            'email'  => null,
            'stat'   => $stat->getTrackingHash(),
            'lead'   => $contact->getId(),
        ]);

        $this->client->followRedirects(false);
        $this->client->request(Request::METHOD_GET, sprintf('/r/%s?ct=%s', $redirect->getRedirectId(), $ct));
    }

    /**
     * @return string[]
     */
    private function getTrackingCookiesWritten(): array
    {
        $written = [];
        foreach ($this->client->getResponse()->headers->getCookies() as $cookie) {
            // A deleted cookie comes with an empty value, only a written one counts
            if ('' !== (string) $cookie->getValue() && in_array($cookie->getName(), self::TRACKING_COOKIES, true)) {
                $written[] = $cookie->getName();
            }
        }
        sort($written);

        return $written;
    }
}
