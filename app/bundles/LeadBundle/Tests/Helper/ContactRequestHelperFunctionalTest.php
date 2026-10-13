<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Helper;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadDevice;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\PageBundle\Entity\Hit;
use Symfony\Component\BrowserKit\Cookie;

final class ContactRequestHelperFunctionalTest extends MauticMysqlTestCase
{
    private const SERVER = [
        'HTTP_CLIENT_IP'  => '124.56.35.14',
        'HTTP_USER_AGENT' => 'Functional tester',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->em->createQuery('UPDATE '.LeadField::class.' f SET f.isPubliclyUpdatable = true WHERE f.alias = :alias')
            ->setParameter('alias', 'email')
            ->execute();
        // The field list is result-cached and a bulk update does not invalidate it
        $this->em->getConfiguration()->getResultCache()?->clear();

        // Tracking requests come from anonymous visitors; drop the admin session
        $this->client->getCookieJar()->clear();
    }

    public function testAnonymousVisitorIsMergedIntoContactIdentifiedByEmail(): void
    {
        $contact = new Lead();
        $contact->setEmail('john@example.com');
        $this->em->persist($contact);
        $this->em->flush();

        $visitor = $this->track(['page_url' => 'https://example.com/one']);
        $this->assertNotSame($contact->getId(), $visitor['id']);

        $this->track(['page_url' => 'https://example.com/two', 'mautic_device_id' => $visitor['device_id']]);
        $identified = $this->track(['page_url' => 'https://example.com/three', 'mautic_device_id' => $visitor['device_id'], 'email' => 'john@example.com']);

        $this->assertSame($contact->getId(), $identified['id']);

        $this->em->clear();
        $this->assertNull($this->em->getRepository(Lead::class)->find($visitor['id']));

        $hitUrls = array_map(
            fn (Hit $hit): string => $hit->getUrl(),
            $this->em->getRepository(Hit::class)->findBy(['lead' => $contact->getId()], ['id' => 'ASC'])
        );
        $this->assertSame(['https://example.com/one', 'https://example.com/two', 'https://example.com/three'], $hitUrls);

        $device = $this->em->getRepository(LeadDevice::class)->findOneBy(['trackingId' => $visitor['device_id']]);
        $this->assertSame($contact->getId(), $device->getLead()->getId());
    }

    public function testContactSelectedByLegacyIdIsNotMerged(): void
    {
        $contact = new Lead();
        $contact->setEmail('john@example.com');
        $anonymous = new Lead();
        $this->em->persist($contact);
        $this->em->persist($anonymous);
        $this->em->flush();

        $this->client->getCookieJar()->set(new Cookie('mautic_session_id', 'legacy'));
        $identified = $this->track(['page_url' => 'https://example.com/', 'mtc_id' => $anonymous->getId(), 'email' => 'john@example.com']);

        $this->assertSame($contact->getId(), $identified['id']);

        $this->em->clear();
        $this->assertNotNull($this->em->getRepository(Lead::class)->find($anonymous->getId()));
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return array<string, mixed>
     */
    private function track(array $parameters): array
    {
        $this->client->request('POST', '/mtc/event', $parameters, [], self::SERVER);
        $this->assertResponseIsSuccessful();

        return json_decode((string) $this->client->getResponse()->getContent(), true);
    }
}
