<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests;

use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\FrequencyRule;
use Mautic\LeadBundle\Entity\Lead;

class LeadTest extends \PHPUnit_Framework_TestCase
{
    public function testPreferredChannels()
    {
        $frequencyRules = [
            'channel1' => [
                'frequencyNumber'  => '',
                'frequencyTime'    => '',
                'preferredChannel' => 0,
                'pauseFromDate'    => '2016-01-01',
                'pauseToDate'      => '2100-01-01',
            ],
            'channel2' => [
                'frequencyNumber'  => '',
                'frequencyTime'    => '',
                'preferredChannel' => 1,
                'pauseFromDate'    => '',
                'pauseToDate'      => '',
            ],
            'channel3' => [
                'frequencyNumber'  => '7',
                'frequencyTime'    => FrequencyRule::TIME_DAY, // 210
                'preferredChannel' => 0,
                'pauseFromDate'    => '',
                'pauseToDate'      => '',
            ],
            'channel4' => [
                'frequencyNumber'  => '',
                'frequencyTime'    => '',
                'preferredChannel' => 0,
                'pauseFromDate'    => '',
                'pauseToDate'      => '',
            ],
            'channel5' => [
                'frequencyNumber'  => '10',
                'frequencyTime'    => FrequencyRule::TIME_WEEK, // 40
                'preferredChannel' => 0,
                'pauseFromDate'    => '',
                'pauseToDate'      => '',
            ],
            'channel6' => [
                'frequencyNumber'  => '10',
                'frequencyTime'    => FrequencyRule::TIME_MONTH, // 10
                'preferredChannel' => 0,
                'pauseFromDate'    => '',
                'pauseToDate'      => '',
            ],
        ];

        $lead = new Lead();

        foreach ($frequencyRules as $channel => $rule) {
            $frequencyRule = (new FrequencyRule())
                ->setPreferredChannel($rule['preferredChannel'])
                ->setFrequencyNumber($rule['frequencyNumber'])
                ->setFrequencyTime($rule['frequencyTime'])
                ->setChannel($channel)
                ->setPauseFromDate(($rule['pauseFromDate']) ? new \DateTime($rule['pauseFromDate']) : null)
                ->setPauseToDate((($rule['pauseToDate']) ? new \DateTime($rule['pauseToDate']) : null));

            $lead->addFrequencyRule($frequencyRule);
        }

        $dnc = (new DoNotContact())->setChannel('channel4');
        $lead->addDoNotContactEntry($dnc);

        $channelRules = Lead::generateChannelRules($lead->getFrequencyRules()->toArray(), $lead->getDoNotContact()->toArray());
        $this->assertEquals(['channel2', 'channel3', 'channel5', 'channel6', 'channel1', 'channel4'], array_keys($channelRules));
    }

    public function testAdjustPoints()
    {
        $points = 501;
        $lead   = new Lead();
        $lead->adjustPoints($points);

        $this->assertEquals([
            'points' => [
                0 => 0,
                1 => $points,
            ],
        ], $lead->getChanges());
    }
}
