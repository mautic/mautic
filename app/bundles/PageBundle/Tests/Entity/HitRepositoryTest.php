<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Entity;

use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\HitRepository;
use Mautic\PageBundle\Entity\Page;
use PHPUnit\Framework\Assert;

final class HitRepositoryTest extends MauticMysqlTestCase
{
    private HitRepository $hitRepository;

    private IpAddress $ipAddress;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hitRepository = $this->em->getRepository(Hit::class);
    }

    public function testGetLatestHitDateByLead(): void
    {
        Assert::assertNull($this->hitRepository->getLatestHitDateByLead(1, 'someId'));
        Assert::assertNull($this->hitRepository->getLatestHitDateByLead(1));

        $leadOne  = $this->createLead();
        $leadTwo  = $this->createLead();
        $this->createHit($leadOne, $dateOne = new \DateTime('-10 second'), 'one-first');
        $this->createHit($leadOne, new \DateTime('-20 second'), 'one-first');
        $this->createHit($leadOne, $dateThree = new \DateTime('-5 second'), 'one-second');
        $this->createHit($leadTwo, new \DateTime('-50 second'), 'two-first');
        $this->createHit($leadTwo, $dateFive = new \DateTime('-40 second'), 'two-first');
        $this->em->flush();

        $this->assertHitDate($dateOne, $leadOne, 'one-first');
        $this->assertHitDate($dateThree, $leadOne, 'one-second');
        $this->assertHitDate($dateFive, $leadTwo, 'two-first');
        $this->assertHitDate($dateThree, $leadOne, null);
        $this->assertHitDate($dateFive, $leadTwo, null);

        Assert::assertNull($this->hitRepository->getLatestHitDateByLead((int) $leadOne->getId(), 'two-first'));
        Assert::assertNull($this->hitRepository->getLatestHitDateByLead((int) $leadTwo->getId(), 'one-second'));
    }

    public function testGetEmailClickthroughHitCount(): void
    {
        $this->assertEmpty($this->hitRepository->getEmailClickthroughHitCount([]));

        $emailOne   = $this->createEmail();
        $emailTwo   = $this->createEmail();
        $emailThree = $this->createEmail();

        $leadOne  = $this->createLead();
        $leadTwo  = $this->createLead();
        $this->createHit($leadOne, new \DateTime('-10 second'), 'one-first', $emailOne);
        $this->createHit($leadOne, new \DateTime('-20 second'), 'one-first', $emailTwo);
        $this->createHit($leadOne, new \DateTime('-5 second'), 'one-second', $emailThree);
        $this->createHit($leadTwo, new \DateTime('-50 second'), 'two-first', $emailOne);
        $this->createHit($leadTwo, new \DateTime('-40 second'), 'two-first', $emailTwo);
        $this->em->flush();

        $this->em->clear();

        $counts = $this->hitRepository->getEmailClickthroughHitCount([$emailOne->getId(), $emailTwo->getId(), $emailThree->getId()]);

        $this->assertNotEmpty($counts);
        $this->assertSame('2', $counts[$emailOne->getId()]);
        $this->assertSame('2', $counts[$emailTwo->getId()]);
        $this->assertSame('1', $counts[$emailThree->getId()]);
    }

    public function testGetEmailClickthroughHitCountRespectsToDateFilter(): void
    {
        $lead  = $this->createLead();
        $email = new Email();
        $email->setName('Email A/B');
        $email->setSubject('Email A/B Subject');
        $email->setEmailType('list');
        $this->em->persist($email);

        $this->createEmailHit($lead, $email, new \DateTime('2026-03-10 12:00:00', new \DateTimeZone('UTC')), 'in-range', 200);
        $this->createEmailHit($lead, $email, new \DateTime('2026-03-30 12:00:00', new \DateTimeZone('UTC')), 'in-range-2', 200);
        $this->createEmailHit($lead, $email, new \DateTime('2026-04-10 12:00:00', new \DateTimeZone('UTC')), 'after-range', 200);
        $this->createEmailHit($lead, $email, new \DateTime('2026-03-15 12:00:00', new \DateTimeZone('UTC')), 'wrong-code', 404);
        $this->em->flush();

        $result = $this->hitRepository->getEmailClickthroughHitCount(
            [(int) $email->getId()],
            new \DateTime('2026-03-01 00:00:00', new \DateTimeZone('UTC')),
            200,
            new \DateTime('2026-03-31 23:59:59', new \DateTimeZone('UTC'))
        );

        Assert::assertArrayHasKey((string) $email->getId(), $result);
        Assert::assertSame('2', (string) $result[(string) $email->getId()]);
    }

    public function testGetDwellTimesForPages(): void
    {
        $this->assertEmpty($this->hitRepository->getDwellTimesForPages([], []));

        $pageOne   = $this->createPage();
        $pageTwo   = $this->createPage();
        $pageThree = $this->createPage();

        $leadOne  = $this->createLead();
        $leadTwo  = $this->createLead();
        $this->createHit($leadOne, new \DateTime('-10 second'), 'one-first', $pageOne);
        $this->createHit($leadOne, new \DateTime('-20 second'), 'one-first', $pageTwo);
        $this->createHit($leadOne, new \DateTime('-5 second'), 'one-second', $pageThree);
        $this->createHit($leadTwo, new \DateTime('-50 second'), 'two-first', $pageOne);
        $this->createHit($leadTwo, new \DateTime('-40 second'), 'two-first', $pageTwo);
        $this->em->flush();

        $this->em->clear();

        $counts = $this->hitRepository->getDwellTimesForPages([$pageOne->getId(), $pageTwo->getId(), $pageThree->getId()], []);

        $this->assertNotEmpty($counts);
        $this->assertSame(2, $counts[$pageOne->getId()]['count']);
        $this->assertSame(2, $counts[$pageTwo->getId()]['count']);
        $this->assertSame(1, $counts[$pageThree->getId()]['count']);
    }

    private function assertHitDate(\DateTime $expectedHitDate, Lead $lead, ?string $trackingId): void
    {
        $hitDate = $this->hitRepository->getLatestHitDateByLead((int) $lead->getId(), $trackingId);

        Assert::assertInstanceOf(\DateTime::class, $hitDate);
        Assert::assertSame($expectedHitDate->getTimestamp(), $hitDate->getTimestamp());
    }

    /**
     * @param Email|Page|null $entity
     */
    private function createHit(Lead $lead, \DateTime $dateHit, string $trackingId, $entity = null): Hit
    {
        $hit = new Hit();
        $hit->setLead($lead);
        $hit->setIpAddress($this->getIpAddress());
        $hit->setDateHit($dateHit);
        $hit->setTrackingId($trackingId);
        $hit->setCode(200);

        if ($entity instanceof Email) {
            $hit->setEmail($entity);
            $hit->setSource('email');
        }

        if ($entity instanceof Page) {
            $hit->setPage($entity);
            $hit->setSource('page');
        }

        $this->em->persist($hit);

        return $hit;
    }

    private function createLead(): Lead
    {
        $lead = new Lead();
        $this->em->persist($lead);

        return $lead;
    }

    private function createEmailHit(Lead $lead, Email $email, \DateTime $dateHit, string $trackingId, int $code): void
    {
        $hit = new Hit();
        $hit->setLead($lead);
        $hit->setEmail($email);
        $hit->setIpAddress($this->getIpAddress());
        $hit->setDateHit($dateHit);
        $hit->setTrackingId($trackingId);
        $hit->setCode($code);
        $this->em->persist($hit);
    }

    private function getIpAddress(): IpAddress
    {
        if (!isset($this->ipAddress)) {
            $this->ipAddress = new IpAddress('127.0.0.1');
        }

        return $this->ipAddress;
    }

    private function createEmail(): Email
    {
        $email = new Email();
        $email->setName('Test');
        $email->setCustomHtml('');
        $email->setEmailType('template');

        $this->em->persist($email);

        return $email;
    }

    private function createPage(): Page
    {
        $page = new Page();
        $page->setTitle('Test');
        $page->setAlias('test');
        $page->setCustomHtml('');
        $this->em->persist($page);

        return $page;
    }
}
