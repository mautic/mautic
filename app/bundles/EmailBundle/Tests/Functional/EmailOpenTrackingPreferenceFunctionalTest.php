<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Functional;

use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatDevice;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Helper\ContactTrackingPreferenceHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;

/**
 * The email open pixel follows the contact's own tracking choice on top of the request checks.
 */
final class EmailOpenTrackingPreferenceFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    public function testOpenIsNotRecordedForAContactWhoTurnedTrackingOff(): void
    {
        [$email, $stat] = $this->createSentEmail('pixel-off@example.com', false);

        $this->openEmail($stat, '203.0.113.70');

        $stat = $this->em->find(Stat::class, $stat->getId());
        $this->assertInstanceOf(Stat::class, $stat);
        $this->assertFalse($stat->getIsRead());
        $this->assertNotInstanceOf(\DateTimeInterface::class, $stat->getLastOpened());
        $this->assertNotInstanceOf(IpAddress::class, $stat->getIpAddress());
        $this->assertCount(0, $this->em->getRepository(StatDevice::class)->findBy(['stat' => $stat]));
        $this->assertSame(0, $this->getReadCount($email));
        $this->assertNotInstanceOf(IpAddress::class, $this->em->getRepository(IpAddress::class)->findOneBy(['ipAddress' => '203.0.113.70']), 'the contact\'s IP address is not stored');
    }

    /**
     * @return iterable<string, array{string, ?bool, string}>
     */
    public static function trackedContactProvider(): iterable
    {
        yield 'tracking allowed' => ['pixel-on@example.com', true, '203.0.113.71'];
        yield 'no choice made' => ['pixel-unset@example.com', null, '203.0.113.72'];
    }

    #[DataProvider('trackedContactProvider')]
    public function testOpenIsRecordedForAContactWhoAllowsTrackingOrHasNotChosen(string $address, ?bool $allowsTracking, string $ip): void
    {
        [$email, $stat] = $this->createSentEmail($address, $allowsTracking);

        $this->openEmail($stat, $ip);

        $stat = $this->em->find(Stat::class, $stat->getId());
        $this->assertInstanceOf(Stat::class, $stat);
        $this->assertTrue($stat->getIsRead());
        $this->assertInstanceOf(\DateTimeInterface::class, $stat->getLastOpened());
        $this->assertCount(1, $this->em->getRepository(StatDevice::class)->findBy(['stat' => $stat]));
        $this->assertSame(1, $this->getReadCount($email));
    }

    /**
     * @return array{Email, Stat}
     */
    private function createSentEmail(string $address, ?bool $allowsTracking): array
    {
        $contact = new Lead();
        $contact->setEmail($address);
        $this->em->persist($contact);

        $email = new Email();
        $email->setName('Tracking preference');
        $email->setSubject('Tracking preference');
        $email->setEmailType('template');
        $email->setIsPublished(true);
        $this->em->persist($email);

        $stat = new Stat();
        $stat->setEmail($email);
        $stat->setLead($contact);
        $stat->setEmailAddress($address);
        $stat->setTrackingHash(substr(md5($address), 0, 20));
        $stat->setDateSent((new \DateTime())->modify('-80 second'));
        $this->em->persist($stat);
        $this->em->flush();

        if (null !== $allowsTracking) {
            self::getContainer()->get(ContactTrackingPreferenceHelper::class)->setActivityTracking($contact, $allowsTracking, 'test');
        }

        $this->logoutUser();

        return [$email, $stat];
    }

    private function openEmail(Stat $stat, string $ip): void
    {
        $this->client->request(Request::METHOD_GET, '/email/'.$stat->getTrackingHash().'.gif', [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0) AppleWebKit/605.1.15 (KHTML, like Gecko)',
            'REMOTE_ADDR'     => $ip,
        ]);
        $this->assertResponseIsSuccessful('the image is served either way');
        $this->em->clear();
    }

    private function getReadCount(Email $email): int
    {
        $email = $this->em->find(Email::class, $email->getId());
        $this->assertInstanceOf(Email::class, $email);

        return (int) $email->getReadCount();
    }
}
