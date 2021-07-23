<?php

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Tests\EventListener;

use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\EventListener\CampaignSubscriber;

class CampaignSubscriberTest extends \PHPUnit\Framework\TestCase
{
    public function testOnCampaignTriggerActionSuccess()
    {
        $campaignExecutionEvent = $this->getCampaignExecutionEvent();

        $campaignSubscriber = $this->getMockBuilder(CampaignSubscriber::class)
            ->onlyMethods(['pushToIntegration'])
            ->disableOriginalConstructor()
            ->getMock();
        $campaignSubscriber->method('pushToIntegration')->willReturn(true);
        $campaignSubscriber->onCampaignTriggerAction($campaignExecutionEvent);

        $this->assertTrue($campaignExecutionEvent->getResult());
    }

    public function testOnCampaignTriggerActionFailed()
    {
        $campaignExecutionEvent = $this->getCampaignExecutionEvent();

        $campaignSubscriber = $this->getMockBuilder(CampaignSubscriber::class)
            ->onlyMethods(['pushToIntegration'])
            ->disableOriginalConstructor()
            ->getMock();
        $campaignSubscriber->method('pushToIntegration')->willReturn(false);
        $campaignSubscriber->onCampaignTriggerAction($campaignExecutionEvent);

        $this->assertFalse($campaignExecutionEvent->getResult());
    }

    public function testOnCampaignTriggerActionFailedWithErrors()
    {
        $campaignExecutionEvent = $this->getCampaignExecutionEvent();

        $campaignSubscriber = $this->getMockBuilder(CampaignSubscriber::class)
            ->onlyMethods(['pushToIntegration'])
            ->disableOriginalConstructor()
            ->getMock();
        $campaignSubscriber->method('pushToIntegration')->willReturnCallback(
            function ($config, $lead, array &$errors) {
                $errors[] = 'error from response';
            });
        $campaignSubscriber->onCampaignTriggerAction($campaignExecutionEvent);

        $expected = [
            'failed' => 1,
            'reason' => 'error from response',
        ];
        $this->assertSame($expected, $campaignExecutionEvent->getResult());
    }

    protected function getCampaignExecutionEvent(): CampaignExecutionEvent
    {
        $contact = new Lead();
        $args    = [
            'lead'            => $contact,
            'event'           => ['properties' => []],
            'eventDetails'    => [],
            'eventSettings'   => [],
            'systemTriggered' => true,
        ];

        $campaignExecutionEvent = new CampaignExecutionEvent($args, true);

        return $campaignExecutionEvent;
    }
}
