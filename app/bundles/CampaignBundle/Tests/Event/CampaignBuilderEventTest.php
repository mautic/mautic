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
    const SOMESTRING = 'somestring';

    public function testAddGetDecision()
    {
        $decisionKey = 'email.open';
        $decision    = [
            'label'                  => self::SOMESTRING,
            'description'            => self::SOMESTRING,
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
        // echo "<pre>";var_dump($decisions);die("</pre>");
    }

    protected function initEvent()
    {
        $translator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $translator->expects($this->any())
            ->method('trans')
            ->will($this->returnValue(self::SOMESTRING));

        return new CampaignBuilderEvent($translator);
    }
}
