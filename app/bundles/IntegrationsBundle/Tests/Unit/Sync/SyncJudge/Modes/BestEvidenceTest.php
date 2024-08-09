<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Sync\SyncJudge\Modes;

use Mautic\IntegrationsBundle\Sync\DAO\Sync\InformationChangeRequestDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;
use Mautic\IntegrationsBundle\Sync\Exception\ConflictUnresolvedException;
use Mautic\IntegrationsBundle\Sync\SyncJudge\Modes\BestEvidence;
use PHPUnit\Framework\TestCase;

class BestEvidenceTest extends TestCase
{
    public function testLeftWinnerWithCertainChangeDateTime(): void
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

        $winner = BestEvidence::adjudicate($leftChangeRequest, $rightChangeRequest);

        $this->assertEquals($leftChangeRequest, $winner);
    }

    public function testRightWinnerWithCertainChangeDateTime(): void
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

        $winner = BestEvidence::adjudicate($leftChangeRequest, $rightChangeRequest);

        $this->assertEquals($rightChangeRequest, $winner);
    }

    public function testLeftWinnerWithPossibleChangeDateTime(): void
    {
        $leftChangeRequest = new InformationChangeRequestDAO(
            'Test',
            'Object',
            1,
            'field',
            new NormalizedValueDAO(NormalizedValueDAO::TEXT_TYPE, 'test')
        );
        $leftChangeRequest->setPossibleChangeDateTime(new \DateTimeImmutable('2018-10-08 00:01:00'));

        $rightChangeRequest = new InformationChangeRequestDAO(
            'Test',
            'Object',
            1,
            'field',
            new NormalizedValueDAO(NormalizedValueDAO::TEXT_TYPE, 'test')
        );
        $rightChangeRequest->setPossibleChangeDateTime(new \DateTimeImmutable('2018-10-08 00:00:00'));

        $winner = BestEvidence::adjudicate($leftChangeRequest, $rightChangeRequest);

        $this->assertEquals($leftChangeRequest, $winner);
    }

    public function testRightWinnerWithPossibleChangeDateTime(): void
    {
        $leftChangeRequest = new InformationChangeRequestDAO(
            'Test',
            'Object',
            1,
            'field',
            new NormalizedValueDAO(NormalizedValueDAO::TEXT_TYPE, 'test')
        );
        $leftChangeRequest->setPossibleChangeDateTime(new \DateTimeImmutable('2018-10-08 00:00:00'));

        $rightChangeRequest = new InformationChangeRequestDAO(
            'Test',
            'Object',
            1,
            'field',
            new NormalizedValueDAO(NormalizedValueDAO::TEXT_TYPE, 'test')
        );
        $rightChangeRequest->setPossibleChangeDateTime(new \DateTimeImmutable('2018-10-08 00:01:00'));

        $winner = BestEvidence::adjudicate($leftChangeRequest, $rightChangeRequest);

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
        $leftChangeRequest->setPossibleChangeDateTime(new \DateTimeImmutable('2018-10-08 00:00:00'));

        $rightChangeRequest = new InformationChangeRequestDAO(
            'Test',
            'Object',
            1,
            'field',
            new NormalizedValueDAO(NormalizedValueDAO::TEXT_TYPE, 'test')
        );
        $rightChangeRequest->setPossibleChangeDateTime(new \DateTimeImmutable('2018-10-08 00:00:00'));

        $this->expectException(ConflictUnresolvedException::class);
        BestEvidence::adjudicate($leftChangeRequest, $rightChangeRequest);
    }

    public function testUnresolvedConflictExceptionThrownWhenLeftPossibleChangeDateTimeIsNull(): void
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
        $rightChangeRequest->setPossibleChangeDateTime(new \DateTimeImmutable('2018-10-08 00:00:00'));

        $this->expectException(ConflictUnresolvedException::class);
        BestEvidence::adjudicate($leftChangeRequest, $rightChangeRequest);
    }

    public function testUnresolvedConflictExceptionThrownWhenRightPossibleChangeDateTimeIsNull(): void
    {
        $leftChangeRequest = new InformationChangeRequestDAO(
            'Test',
            'Object',
            1,
            'field',
            new NormalizedValueDAO(NormalizedValueDAO::TEXT_TYPE, 'test')
        );
        $leftChangeRequest->setPossibleChangeDateTime(new \DateTimeImmutable('2018-10-08 00:00:00'));

        $rightChangeRequest = new InformationChangeRequestDAO(
            'Test',
            'Object',
            1,
            'field',
            new NormalizedValueDAO(NormalizedValueDAO::TEXT_TYPE, 'test')
        );

        $this->expectException(ConflictUnresolvedException::class);
        BestEvidence::adjudicate($leftChangeRequest, $rightChangeRequest);
    }
}
