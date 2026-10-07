<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Model;

use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Model\AbstractCommonModel;
use Mautic\CoreBundle\Model\IteratorExportDataModel;
use PHPUnit\Framework\MockObject\MockObject;

final class IteratorExportDataModelTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject&AbstractCommonModel
     */
    private MockObject $commonModel;

    private IteratorExportDataModel $iteratorExportDataModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commonModel      = $this->createMock(AbstractCommonModel::class);
        $args                   = ['limit' => 1000];
        $callback               = fn ($var) => $var;

        $this->iteratorExportDataModel = new IteratorExportDataModel($this->commonModel, $args, $callback);
    }

    public function testWorkflowWithItems(): void
    {
        $this->commonModel->expects($this->once())
            ->method('getEntities')
            ->with(['limit' => 1000, 'start' => 0, 'skipOrdering' => false])
            ->willReturn(['results' => [['a'], ['b']]]);

        $this->commonModel->method('getRepository')->willReturn($this->createStub(CommonRepository::class));

        $this->assertSame(0, $this->iteratorExportDataModel->key());
        $this->iteratorExportDataModel->rewind();
        $this->iteratorExportDataModel->next();
        $this->assertSame(1, $this->iteratorExportDataModel->key());
    }

    public function testWorkflowWithoutItems(): void
    {
        $this->commonModel->expects($this->once())
            ->method('getEntities')
            ->with(['limit' => 1000, 'start' => 0, 'skipOrdering' => false])
            ->willReturn(['results' => []]);

        $this->commonModel->method('getRepository')->willReturn($this->createStub(CommonRepository::class));

        $this->assertSame(0, $this->iteratorExportDataModel->key());
        $this->iteratorExportDataModel->rewind();
        $this->iteratorExportDataModel->next();
        $this->assertSame(1, $this->iteratorExportDataModel->key());
    }
}
