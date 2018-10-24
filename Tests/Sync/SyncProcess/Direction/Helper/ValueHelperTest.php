<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Tests\Sync\SyncProcess\Direction\Helper;


use MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping\ObjectMappingDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\FieldDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;
use MauticPlugin\IntegrationsBundle\Sync\SyncProcess\Direction\Helper\ValueHelper;
use Symfony\Component\Translation\TranslatorInterface;

class ValueHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    protected function setUp()
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->method('trans')
            ->willReturnCallback(
                function ($key) {
                    return $key;
                }
            );
    }

    public function testUnkonwnReturnedForMissingRequiredIntegrationValue()
    {
        $normalizedValueDAO = new NormalizedValueDAO(NormalizedValueDAO::STRING_TYPE, '');

        $newValue = $this->getValueHelper()->getValueForIntegration(
            $normalizedValueDAO,
            FieldDAO::FIELD_REQUIRED,
            ObjectMappingDAO::SYNC_TO_INTEGRATION
        );

        $this->assertEquals(
            'mautic.core.unknown',
            $newValue->getNormalizedValue()
        );
    }

    public function testUnkonwnIsNotReturnedForMissingNonRequiredIntegrationValue()
    {
        $normalizedValueDAO = new NormalizedValueDAO(NormalizedValueDAO::STRING_TYPE, '');

        $newValue = $this->getValueHelper()->getValueForIntegration(
            $normalizedValueDAO,
            FieldDAO::FIELD_CHANGED,
            ObjectMappingDAO::SYNC_TO_MAUTIC
        );

        $this->assertEquals(
            '',
            $newValue->getNormalizedValue()
        );
    }

    public function testUnkonwnIsNotReturnedForMissingOppositeSyncIntegrationValue()
    {
        $normalizedValueDAO = new NormalizedValueDAO(NormalizedValueDAO::STRING_TYPE, '');

        $newValue = $this->getValueHelper()->getValueForIntegration(
            $normalizedValueDAO,
            FieldDAO::FIELD_CHANGED,
            ObjectMappingDAO::SYNC_TO_INTEGRATION
        );

        $this->assertEquals(
            '',
            $newValue->getNormalizedValue()
        );
    }

    public function testUnkonwnReturnedForMissingRequiredMauticValue()
    {
        $normalizedValueDAO = new NormalizedValueDAO(NormalizedValueDAO::STRING_TYPE, '');

        $newValue = $this->getValueHelper()->getValueForMautic(
            $normalizedValueDAO,
            FieldDAO::FIELD_REQUIRED,
            ObjectMappingDAO::SYNC_TO_MAUTIC
        );

        $this->assertEquals(
            'mautic.core.unknown',
            $newValue->getNormalizedValue()
        );
    }

    public function testUnkonwnIsNotReturnedForMissingNonRequiredInternalValue()
    {
        $normalizedValueDAO = new NormalizedValueDAO(NormalizedValueDAO::STRING_TYPE, '');

        $newValue = $this->getValueHelper()->getValueForMautic(
            $normalizedValueDAO,
            FieldDAO::FIELD_CHANGED,
            ObjectMappingDAO::SYNC_TO_INTEGRATION
        );

        $this->assertEquals(
            '',
            $newValue->getNormalizedValue()
        );
    }

    public function testUnkonwnIsNotReturnedForMissingOppositeSyncInternalnValue()
    {
        $normalizedValueDAO = new NormalizedValueDAO(NormalizedValueDAO::STRING_TYPE, '');

        $newValue = $this->getValueHelper()->getValueForMautic(
            $normalizedValueDAO,
            FieldDAO::FIELD_CHANGED,
            ObjectMappingDAO::SYNC_TO_MAUTIC
        );

        $this->assertEquals(
            '',
            $newValue->getNormalizedValue()
        );
    }

    /**
     * @return ValueHelper
     */
    private function getValueHelper()
    {
        return new ValueHelper($this->translator);
    }
}