<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Tests\Service;

use Mautic\CampaignBundle\Entity\CampaignRepository;
use Mautic\CampaignBundle\Service\Campaign;
use Mautic\EmailBundle\Entity\EmailRepository;

class CampaignTest extends \PHPUnit_Framework_TestCase
{
    public function testHasUnpublishedEmail()
    {
        $campaignId         = 1;
        $campaignRepository = $this->createMock(CampaignRepository::class);
        $campaignRepository
            ->expects($this->once())
            ->method('fetchEmailIdsById')
            ->with($campaignId)
            ->willReturn([]);
        $emailRepository = $this->createMock(EmailRepository::class);
        $campaignService = new Campaign($campaignRepository, $emailRepository);

        $this->assertFalse($campaignService->hasUnpublishedEmail($campaignId));

        $emailIds             = [1, 2.3];
        $hasUnpublishedEmails = true;
        $campaignRepository   = $this->createMock(CampaignRepository::class);
        $campaignRepository
            ->expects($this->once())
            ->method('fetchEmailIdsById')
            ->with($campaignId)
            ->willReturn($emailIds);
        $emailRepository = $this->createMock(EmailRepository::class);
        $emailRepository
            ->expects($this->once())
            ->method('isOneUnpublished')
            ->with($emailIds)
            ->willReturn($hasUnpublishedEmails);
        $campaignService = new Campaign($campaignRepository, $emailRepository);
        $this->assertTrue($campaignService->hasUnpublishedEmail($campaignId));
    }
}
