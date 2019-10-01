<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Tests\Unit\Sync\SyncProcess\Direction\Helper;

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

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->method('trans')
            ->willReturnCallback(
                function ($key) {
                    return $key;
                }
            );
    }

    public function testUnkonwnReturnedForMissingRequiredIntegrationValue(): void
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

    public function testUnkonwnIsNotReturnedForMissingNonRequiredIntegrationValue(): void
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

    public function testUnkonwnIsNotReturnedForMissingOppositeSyncIntegrationValue(): void
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

    public function testUnkonwnReturnedForMissingRequiredMauticValue(): void
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

    public function testUnkonwnIsNotReturnedForMissingNonRequiredInternalValue(): void
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

    public function testUnkonwnIsNotReturnedForMissingOppositeSyncInternalnValue(): void
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
