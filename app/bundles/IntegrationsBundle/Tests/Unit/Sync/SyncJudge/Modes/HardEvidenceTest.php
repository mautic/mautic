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

namespace MauticPlugin\IntegrationsBundle\Tests\Unit\Sync\SyncJudge\Modes;

use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\InformationChangeRequestDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;
use MauticPlugin\IntegrationsBundle\Sync\Exception\ConflictUnresolvedException;
use MauticPlugin\IntegrationsBundle\Sync\SyncJudge\Modes\HardEvidence;

class HardEvidenceTest extends \PHPUnit_Framework_TestCase
{
    public function testLeftWinner(): void
    {
        $leftChangeRequest = new InformationChangeRequestDAO(
            'Test',
            'Object',
            1,
            'field',
            new NormalizedValueDAO(NormalizedValueDAO::TEXT_TYPE, 'test')
        );
        $leftChangeRequest->setCertainChangeDateTime(new \DateTimeImmutable('2018-10-08 00:01:00'));

        $rightChangeRequest = new InformationChangeRequestDAO(
            'Test',
            'Object',
            1,
            'field',
            new NormalizedValueDAO(NormalizedValueDAO::TEXT_TYPE, 'test')
        );
        $rightChangeRequest->setCertainChangeDateTime(new \DateTimeImmutable('2018-10-08 00:00:00'));

        $winner = HardEvidence::adjudicate($leftChangeRequest, $rightChangeRequest);

        $this->assertEquals($leftChangeRequest, $winner);
    }

    public function testRightWinner(): void
    {
        $leftChangeRequest = new InformationChangeRequestDAO(
            'Test',
            'Object',
            1,
            'field',
            new NormalizedValueDAO(NormalizedValueDAO::TEXT_TYPE, 'test')
        );
        $leftChangeRequest->setCertainChangeDateTime(new \DateTimeImmutable('2018-10-08 00:00:00'));

        $rightChangeRequest = new InformationChangeRequestDAO(
            'Test',
            'Object',
            1,
            'field',
            new NormalizedValueDAO(NormalizedValueDAO::TEXT_TYPE, 'test')
        );
        $rightChangeRequest->setCertainChangeDateTime(new \DateTimeImmutable('2018-10-08 00:01:00'));

        $winner = HardEvidence::adjudicate($leftChangeRequest, $rightChangeRequest);

        $this->assertEquals($rightChangeRequest, $winner);
    }

    public function testUnresolvedConflictExceptionThrownIfEqual(): void
    {
        $leftChangeRequest = new InformationChangeRequestDAO(
            'Test',
            'Object',
            1,
            'field',
            new NormalizedValueDAO(NormalizedValueDAO::TEXT_TYPE, 'test')
        );
        $leftChangeRequest->setCertainChangeDateTime(new \DateTimeImmutable('2018-10-08 00:00:00'));

        $rightChangeRequest = new InformationChangeRequestDAO(
            'Test',
            'Object',
            1,
            'field',
            new NormalizedValueDAO(NormalizedValueDAO::TEXT_TYPE, 'test')
        );
        $rightChangeRequest->setCertainChangeDateTime(new \DateTimeImmutable('2018-10-08 00:00:00'));

        $this->expectException(ConflictUnresolvedException::class);
        HardEvidence::adjudicate($leftChangeRequest, $rightChangeRequest);
    }

    public function testUnresolvedConflictExceptionThrownWhenLeftCertainChangeDateTimeIsNull(): void
    {
        $leftChangeRequest = new InformationChangeRequestDAO(
            'Test',
            'Object',
            1,
            'field',
            new NormalizedValueDAO(NormalizedValueDAO::TEXT_TYPE, 'test')
        );

        $rightChangeRequest = new InformationChangeRequestDAO(
            'Test',
            'Object',
            1,
            'field',
            new NormalizedValueDAO(NormalizedValueDAO::TEXT_TYPE, 'test')
        );
        $rightChangeRequest->setCertainChangeDateTime(new \DateTimeImmutable('2018-10-08 00:00:00'));

        $this->expectException(ConflictUnresolvedException::class);
        HardEvidence::adjudicate($leftChangeRequest, $rightChangeRequest);
    }

    public function testUnresolvedConflictExceptionThrownWhenRightCertainChangeDateTimeIsNull(): void
    {
        $leftChangeRequest = new InformationChangeRequestDAO(
            'Test',
            'Object',
            1,
            'field',
            new NormalizedValueDAO(NormalizedValueDAO::TEXT_TYPE, 'test')
        );
        $leftChangeRequest->setCertainChangeDateTime(new \DateTimeImmutable('2018-10-08 00:00:00'));

        $rightChangeRequest = new InformationChangeRequestDAO(
            'Test',
            'Object',
            1,
            'field',
            new NormalizedValueDAO(NormalizedValueDAO::TEXT_TYPE, 'test')
        );

        $this->expectException(ConflictUnresolvedException::class);
        HardEvidence::adjudicate($leftChangeRequest, $rightChangeRequest);
    }
}
