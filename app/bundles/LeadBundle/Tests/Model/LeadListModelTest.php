<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Model;

use Mautic\CoreBundle\Helper\Serializer;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Model\ListModel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;

final class LeadListModelTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ListModel&MockObject
     */
    private MockObject $fixture;

    protected function setUp(): void
    {
        $mockListModel = $this->getMockBuilder(ListModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEntities', 'getEntity'])
            ->getMock();

        $mockListModel
            ->method('getEntity')
            ->willReturnCallback(function ($id): MockObject {
                $mockEntity = $this->getMockBuilder(LeadList::class)
                    ->disableOriginalConstructor()
                    ->onlyMethods(['getName'])
                    ->getMock();

                $mockEntity->expects($this->once())
                    ->method('getName')
                    ->willReturn((string) $id);

                return $mockEntity;
            });

        $filters = 'a:1:{i:0;a:7:{s:4:"glue";s:3:"and";s:5:"field";s:8:"leadlist";s:6:"object";s:4:"lead";s:4:"type";s:8:"leadlist";s:6:"filter";a:2:{i:0;i:1;i:1;i:3;}s:7:"display";N;s:8:"operator";s:2:"in";}}';

        $filters4 = 'a:1:{i:0;a:7:{s:4:"glue";s:3:"and";s:5:"field";s:8:"leadlist";s:6:"object";s:4:"lead";s:4:"type";s:8:"leadlist";s:6:"filter";a:1:{i:0;i:3;}s:7:"display";N;s:8:"operator";s:2:"in";}}';

        $mockEntity = $this->createMock(LeadList::class);

        $mockEntity1 = clone $mockEntity;
        $mockEntity1->expects($this->once())
            ->method('getFilters')
            ->willReturn([]);
        $mockEntity1
            ->method('getId')
            ->willReturn(1);

        $mockEntity2 = clone $mockEntity;
        $mockEntity2->expects($this->once())
            ->method('getFilters')
            ->willReturn(Serializer::decode($filters));
        $mockEntity2
            ->method('getId')
            ->willReturn(2);

        $mockEntity3 = clone $mockEntity;
        $mockEntity3->expects($this->once())
            ->method('getFilters')
            ->willReturn([]);
        $mockEntity3
            ->method('getId')
            ->willReturn(3);

        $mockEntity4 = clone $mockEntity;
        $mockEntity4->expects($this->once())
            ->method('getFilters')
            ->willReturn(Serializer::decode($filters4));
        $mockEntity4
            ->method('getId')
            ->willReturn(4);

        $mockListModel->expects($this->once())
            ->method('getEntities')
            ->willReturn([
                1 => $mockEntity1,
                2 => $mockEntity2,
                3 => $mockEntity3,
                4 => $mockEntity4,
            ]);

        $this->fixture = $mockListModel;
    }

    /**
     * @param array<int, mixed> $arg
     * @param array<int, mixed> $expected
     */
    #[DataProvider('segmentTestDataProvider')]
    public function testSegmentsCanBeDeletedCorrecty(array $arg, array $expected, string $message): void
    {
        $result = $this->fixture->canNotBeDeleted($arg);

        $this->assertEquals($expected, $result, $message);
    }

    /**
     * @return \Iterator<int, array{array<int, mixed>, array<int, mixed>, string}>
     */
    public static function segmentTestDataProvider(): \Iterator
    {
        yield [
            [1],
            [1 => '1'],
            '2 is dependent on 1, so 1 cannot be deleted.',
        ];
        yield [
            [1, 3],
            [1 => '1', 3 => '3'],
            '2 is dependent on 1 & 3, so 1 & 3 cannot be deleted.',
        ];
        yield [
            [1, 2, 3, 4],
            [],
            'Since we are deleting all segments, it should not prevent any from being deleted.',
        ];
        yield [
            [2],
            [],
            'Segments without any other segment dependent on them should always be able to be deleted.',
        ];
    }
}
