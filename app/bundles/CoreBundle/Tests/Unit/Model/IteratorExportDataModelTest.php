<?php

namespace Mautic\CoreBundle\Tests\Unit\Model;

use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Model\AbstractCommonModel;
use Mautic\CoreBundle\Model\IteratorExportDataModel;
use PHPUnit\Framework\MockObject\MockObject;

class IteratorExportDataModelTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|AbstractCommonModel
     */
    private $commonModel;

    /**
     * @var MockObject|CommonRepository
     */
    private $commonRepository;

    /**
     * @var IteratorExportDataModel
     */
    private $iteratorExportDataModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commonModel      = $this->createMock(AbstractCommonModel::class);
        $this->commonRepository = $this->createMock(CommonRepository::class);
        $args                   = ['limit' => 1000];
        $callback               = function ($var) {
            return $var;
        };

        $this->iteratorExportDataModel = new IteratorExportDataModel($this->commonModel, $args, $callback);
    }

    public function testWorkflowWithItems(): void
    {
        $this->commonModel->expects($this->once())
            ->method('getEntities')
            ->with(['limit' => 1000, 'start' => 0])
            ->willReturn(['results' => [['a'], ['b']]]);

        $this->commonModel->method('getRepository')->willReturn($this->commonRepository);

        $this->assertSame(0, $this->iteratorExportDataModel->key());
        $this->iteratorExportDataModel->rewind();
        $this->iteratorExportDataModel->next();
        $this->assertSame(1, $this->iteratorExportDataModel->key());
    }

    public function testWorkflowWithoutItems(): void
    {
        $this->commonModel->expects($this->once())
            ->method('getEntities')
            ->with(['limit' => 1000, 'start' => 0])
            ->willReturn(['results' => []]);

        $this->commonModel->method('getRepository')->willReturn($this->commonRepository);

        $this->assertSame(0, $this->iteratorExportDataModel->key());
        $this->iteratorExportDataModel->rewind();
        $this->iteratorExportDataModel->next();
        $this->assertSame(1, $this->iteratorExportDataModel->key());
    }
}
