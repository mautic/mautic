<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Tests\Entity;

use DateTime;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\HitRepository;
use PHPUnit\Framework\Assert;

class HitRepositoryTest extends MauticMysqlTestCase
{
    /**
     * @var HitRepository
     */
    private $hitRepository;

    /**
     * @var IpAddress
     */
    private $ipAddress;

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
        $this->createHit($leadOne, $dateOne = new DateTime('-10 second'), 'one-first');
        $this->createHit($leadOne, new DateTime('-20 second'), 'one-first');
        $this->createHit($leadOne, $dateThree = new DateTime('-5 second'), 'one-second');
        $this->createHit($leadTwo, new DateTime('-50 second'), 'two-first');
        $this->createHit($leadTwo, $dateFive = new DateTime('-40 second'), 'two-first');
        $this->em->flush();

        $this->assertHitDate($dateOne, $leadOne, 'one-first');
        $this->assertHitDate($dateThree, $leadOne, 'one-second');
        $this->assertHitDate($dateFive, $leadTwo, 'two-first');
        $this->assertHitDate($dateThree, $leadOne, null);
        $this->assertHitDate($dateFive, $leadTwo, null);

        Assert::assertNull($this->hitRepository->getLatestHitDateByLead((int) $leadOne->getId(), 'two-first'));
        Assert::assertNull($this->hitRepository->getLatestHitDateByLead((int) $leadTwo->getId(), 'one-second'));
    }

    private function createHit(Lead $lead, DateTime $dateHit, string $trackingId): void
    {
        $hit = new Hit();
        $hit->setLead($lead);
        $hit->setIpAddress($this->getIpAddress());
        $hit->setDateHit($dateHit);
        $hit->setTrackingId($trackingId);
        $hit->setCode(200);
        $this->em->persist($hit);
    }

    private function createLead(): Lead
    {
        $lead = new Lead();
        $this->em->persist($lead);

        return $lead;
    }

    private function getIpAddress(): IpAddress
    {
        if (!isset($this->ipAddress)) {
            $this->ipAddress = new IpAddress('127.0.0.1');
        }

        return $this->ipAddress;
    }

    private function assertHitDate(DateTime $expectedHitDate, Lead $lead, ?string $trackingId): void
    {
        $hitDate = $this->hitRepository->getLatestHitDateByLead((int) $lead->getId(), $trackingId);

        Assert::assertInstanceOf(DateTime::class, $hitDate);
        Assert::assertSame($expectedHitDate->getTimestamp(), $hitDate->getTimestamp());
    }
}
