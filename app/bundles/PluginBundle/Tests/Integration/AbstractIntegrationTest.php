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

use Mautic\PluginBundle\Integration\AbstractIntegration;

class AbstractIntegrationTest extends AbstractIntegrationTestCase
{
    public function testPopulatedLeadDataReturnsIntAndNotDncEntityForMauticContactIsContactableByEmail()
    {
        $integration = $this->getMockBuilder(AbstractIntegration::class)
            ->setConstructorArgs([
                $this->dispatcher,
                $this->cache,
                $this->em,
                $this->session,
                $this->request,
                $this->router,
                $this->translator,
                $this->logger,
                $this->encryptionHelper,
                $this->leadModel,
                $this->companyModel,
                $this->pathsHelper,
                $this->notificationModel,
                $this->fieldModel,
                $this->integrationEntityModel,
            ])
            ->setMethodsExcept(['convertLeadFieldKey', 'getLeadDoNotContact', 'populateLeadData', 'setTranslator', 'setLeadModel'])
            ->getMock();

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
