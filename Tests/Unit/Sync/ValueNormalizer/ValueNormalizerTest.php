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

namespace MauticPlugin\IntegrationsBundle\Tests\Unit\Sync\ValueNormalizer;

use DateTimeInterface;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;
use MauticPlugin\IntegrationsBundle\Sync\ValueNormalizer\ValueNormalizer;

class ValueNormalizerTest extends \PHPUnit_Framework_TestCase
{
    public function testNullDateTimeValue(): void
    {
        $valueNormalizer    = new ValueNormalizer();
        $normalizedValueDAO = $valueNormalizer->normalizeForMautic(NormalizedValueDAO::DATETIME_TYPE, null);

        $this->assertInstanceOf(DateTimeInterface::class, $normalizedValueDAO->getNormalizedValue());
        $this->assertNull($normalizedValueDAO->getOriginalValue());
    }
}
