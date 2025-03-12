<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Command;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\LeadList;

final class SegmentStatCommandTest extends MauticMysqlTestCase
{
    /**
     * @throws \Exception
     */
    public function testSegmentStatCommandWithOutSegment(): void
    {
        $output = $this->testSymfonyCommand('mautic:segments:stat');

        $this->assertStringContainsString('There is no segment to show!!', $output->getDisplay());
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testSegmentStatCommandWithSegment(): void
    {
        $segmentName = 'Segment For Campaign';
        $segment     = new LeadList();
        $segment->setName($segmentName);
        $segment->setPublicName($segmentName);
        $segment->setAlias(mb_strtolower($segmentName));
        $segment->setIsPublished(true);
        $this->em->persist($segment);
        $this->em->flush();

        $campaign = new Campaign();
        $campaign->setName('Campaign With LeadList');
        $campaign->addList($segment);

        $this->em->persist($campaign);
        $this->em->flush();

        $output = $this->testSymfonyCommand('mautic:segments:stat');

        // test table header
        $this->assertMatchesRegularExpression('/Title\s+Id\s+IsPublished\s+IsUsed/i', $output->getDisplay());
        // test table content
        $this->assertMatchesRegularExpression("/Segment For Campaign\s+{$segment->getId()}\s+{$segment->getIsPublished()}\s+1/i", $output->getDisplay());
    }
}
