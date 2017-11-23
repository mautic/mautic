<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Tests\Integration;

use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PluginBundle\Integration\AbstractIntegration;

class AbstractIntegrationTest extends \PHPUnit_Framework_TestCase
{
    public function testPopulatedLeadDataReturnsIntAndNotDncEntityForMauticContactIsContactableByEmail()
    {
        $integration = $this->getMockBuilder(AbstractIntegration::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['convertLeadFieldKey', 'getLeadDoNotContact', 'populateLeadData', 'setTranslator', 'setLeadModel'])
            ->getMock();

        $translator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $integration->setTranslator($translator);

        $leadModel = $this->getMockBuilder(LeadModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $integration->setLeadModel($leadModel);

        $config = [
            'leadFields' => [
                'dnc' => 'mauticContactIsContactableByEmail',
            ],
        ];

        $integration->method('getAvailableLeadFields')
            ->willReturn(
                [
                    'dnc' => [
                        'type'     => 'bool',
                        'required' => false,
                        'label'    => 'DNC',
                    ],
                ]
            );
        $matched = $integration->populateLeadData(['id' => 1], $config);

        $this->assertEquals(
            [
                'dnc' => 0,
            ],
            $matched
        );
    }
}
