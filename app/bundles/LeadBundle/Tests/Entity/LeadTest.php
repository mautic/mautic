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

use Mautic\CoreBundle\Form\RequestTrait;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\FrequencyRule;
use Mautic\LeadBundle\Entity\Lead;

class LeadTest extends \PHPUnit_Framework_TestCase
{
    use RequestTrait;
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
        // new lead
        $this->adjustPointsTest(5, $this->getLeadChangedArray(0, 5), new Lead());
        $this->adjustPointsTest(5, $this->getLeadChangedArray(0, 5), new Lead(), 'plus');
        $this->adjustPointsTest(5, $this->getLeadChangedArray(0, -5), new Lead(), 'minus');
        $this->adjustPointsTest(5, [], new Lead(), 'times');
        $this->adjustPointsTest(5, [], new Lead(), 'divide');

        // existing lead
        $lead = new Lead();
        $lead->setPoints(5);

        $this->adjustPointsTest(5, $this->getLeadChangedArray(5, 10), $lead);
        $this->adjustPointsTest(5, $this->getLeadChangedArray(10, 15), $lead);
        $this->adjustPointsTest(10, $this->getLeadChangedArray(15, 150), $lead, 'times');
        $this->adjustPointsTest(10, $this->getLeadChangedArray(150, 15), $lead, 'divide');
    }

    public function testCustomFieldGetterSetters()
    {
        $lead = new Lead();

        $fields = [
            'core' => [
                'notes' => [
                    'alias' => 'notes',
                    'label' => 'Notes',
                    'type'  => 'textarea',
                    'value' => 'Blah blah blah',
                ],
                'test' => [
                    'alias' => 'test',
                    'label' => 'Test',
                    'type'  => 'textarea',
                    'value' => 'Test blah',
                ],
                'boolean' => [
                    'alias' => 'boolean',
                    'label' => 'Boolean',
                    'type'  => 'boolean',
                    'value' => false,
                ],
                'dateField' => [
                    'alias' => 'dateField',
                    'label' => 'Date Time',
                    'type'  => 'datetime',
                    'value' => '12-12-2017 23:00:00',
                ],
                'multiselect' => [
                    'alias' => 'multi',
                    'label' => 'Multi Select',
                    'type'  => 'multiselect',
                    'value' => ['a', 'b', 'c'],
                ],
            ],
        ];
        $data = [
            'notes'     => 'hello',
            'test'      => 'test',
            'boolean'   => 'yes',
            'dateField' => '12-12-2017 22:03:59',
            'multi'     => 'a|b',
        ];

        $this->cleanFields($data, $fields['core']['boolean']);

        $this->cleanFields($data, $fields['core']['dateField']);

        $this->cleanFields($data, $fields['core']['multiselect']);

        $testDateObject = new \DateTime('12-12-2017 22:03:59');

        $this->assertEquals($testDateObject->format('Y-m-d H:i'), $data['dateField']);
        $this->assertEquals((int) true, $data['boolean']);
        $this->assertEquals(['a', 'b'], $data['multi']);

        $lead->setFields($fields);

        // This should not killover with a segmentation fault due to a loop
        $lead->setNotes('hello');

        // Not using getNotes because it conflicts with an existing method and not sure what to do about that yet
        $lead->setTest('hello');
        $this->assertEquals('hello', $lead->getTest());
    }

    /**
     * @param $points
     * @param $expected
     * @param Lead $lead
     * @param bool $operator
     */
    private function adjustPointsTest($points, $expected, Lead $lead, $operator = false)
    {
        if ($operator) {
            $lead->adjustPoints($points, $operator);
        } else {
            $lead->adjustPoints($points);
        }

        $this->assertEquals($expected, $lead->getChanges());
    }

    /**
     * @param int $oldValue
     * @param int $newValue
     *
     * @return array
     */
    private function getLeadChangedArray($oldValue = 0, $newValue = 0)
    {
        return [
            'points' => [
                0 => $oldValue,
                1 => $newValue,
            ],
        ];
    }
}
