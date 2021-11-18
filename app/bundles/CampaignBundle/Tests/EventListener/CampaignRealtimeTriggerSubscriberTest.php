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

namespace Mautic\CampaignBundle\Tests\EventListener;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Event\CampaignLeadChangeEvent;
use Mautic\CampaignBundle\EventListener\CampaignRealtimeTriggerSubscriber;
use Mautic\CampaignBundle\Executioner\KickoffExecutioner;
use Mautic\CampaignBundle\Membership\Action\Adder;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use PHPUnit\Framework\TestCase;

final class CampaignRealtimeTriggerSubscriberTest extends TestCase
{
    /**
     * @var KickoffExecutioner|MockObject
     */
    private $kickoffExecutioner;

    protected function setUp(): void
    {
        $this->kickoffExecutioner = $this->createMock(KickoffExecutioner::class);
    }

    /**
     * @dataProvider campaignRealtimeProvider
     */
    public function testOnCampaignLeadChangeRealtimeTrigger(bool $isTriggerRealtime, InvokedCount $invokedCount): void
    {
        $campaignRealtimeTriggerSubscriber = new CampaignRealtimeTriggerSubscriber($this->kickoffExecutioner);

        $campaign = new Campaign();
        $campaign->setTriggerRealtime($isTriggerRealtime);
        $lead = new Lead();

        $this->kickoffExecutioner->expects($invokedCount)->method('execute');

        $event = new CampaignLeadChangeEvent($campaign, $lead, Adder::NAME);
        $campaignRealtimeTriggerSubscriber->onCampaignLeadChange($event);
    }

    /**
     * @return array<array<int,
    bool|\PHPUnit\Framework\MockObject\Rule\InvokedCount>>
     */
    public function campaignRealtimeProvider(): array
    {
        return [
            [true, self::once()],
            [false, self::never()],
        ];
    }
}
