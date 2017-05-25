<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Tests\Event;

use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Tests\CampaignTestAbstract;
use Mautic\CoreBundle\Translation\Translator;

class CampaignBuilderEventTest extends CampaignTestAbstract
{
    public function testAddGetDecision()
    {
        $decisionKey = 'email.open';
        $decision    = [
            'label'                  => 'mautic.email.campaign.event.open',
            'description'            => 'mautic.email.campaign.event.open_descr',
            'eventName'              => 'mautic.email.on_campaign_trigger_decision',
            'connectionRestrictions' => [
                'source' => [
                    'action' => [
                        'email.send',
                    ],
                ],
            ],
        ];
        $event = $this->initEvent();
        $event->addDecision(
            $decisionKey,
            $decision
        );

        $decisions = $event->getDecisions();
        $this->assertSame([$decisionKey => $decision], $decisions);
    }

    public function testEventDecisionSort()
    {
        $decisionKey = 'email.open';
        $decision    = [
            'label'                  => 'mautic.email.campaign.event.open',
            'description'            => 'mautic.email.campaign.event.open_descr',
            'eventName'              => 'mautic.email.on_campaign_trigger_decision',
            'connectionRestrictions' => [
                'source' => [
                    'action' => [
                        'email.send',
                    ],
                ],
            ],
        ];
        $event = $this->initEvent();

        // add 3 unsorted decisions
        $event->addDecision('email.open1', $decision);
        $decision['label'] = 'mautic.email.campaign.event.open.3';
        $event->addDecision('email.open3', $decision);
        $decision['label'] = 'mautic.email.campaign.event.open.2';
        $event->addDecision('email.open2', $decision);

        $decisions = $event->getDecisions();

        $shouldBe = 1;
        foreach ($decisions as $key => $resultDecision) {
            $this->assertSame('email.open'.$shouldBe, $key);
            ++$shouldBe;
        }
    }

    protected function initEvent()
    {
        $translator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $translator->expects($this->any())
            ->method('trans')
            ->will($this->returnCallback(function () {
                $args = func_get_args();

                return $args[0];
            }));

        return new CampaignBuilderEvent($translator);
    }
}
