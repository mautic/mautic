<?php

declare(strict_types=1);

namespace Mautic\StageBundle\Tests\Unit\Model;

use Mautic\StageBundle\Entity\Stage;
use Mautic\StageBundle\Model\StageModel;
use PHPUnit\Framework\TestCase;

final class StageModelTest extends TestCase
{
    public function testContactCountsPreserveStageIdsAndPaginationArguments(): void
    {
        $firstStage = $this->createMock(Stage::class);
        $firstStage->method('getId')->willReturn(12);
        $secondStage = $this->createMock(Stage::class);
        $secondStage->method('getId')->willReturn(34);

        $paginator = new class([[0 => $firstStage, 'contactCount' => '2'], [0 => $secondStage, 'contactCount' => '0']]) extends \ArrayIterator {
            public function count(): int
            {
                return 25;
            }
        };

        $model = $this->getMockBuilder(StageModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEntities'])
            ->getMock();
        $model->expects(self::once())
            ->method('getEntities')
            ->with(['start' => 10, 'limit' => 2, 'withContactCount' => true])
            ->willReturn($paginator);

        self::assertSame(
            [[$firstStage, $secondStage], [12 => 2, 34 => 0], 25],
            $model->getEntitiesWithContactCounts(['start' => 10, 'limit' => 2]),
        );
    }

    public function testContactCountsReturnEmptyResultsWhenThereAreNoStages(): void
    {
        $model = $this->getMockBuilder(StageModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEntities'])
            ->getMock();
        $model->expects(self::once())
            ->method('getEntities')
            ->with(['withContactCount' => true])
            ->willReturn(new \ArrayIterator());

        self::assertSame([[], [], 0], $model->getEntitiesWithContactCounts());
    }
}
