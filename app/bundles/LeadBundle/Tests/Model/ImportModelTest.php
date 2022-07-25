<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Model;

use Doctrine\ORM\ORMException;
use Mautic\LeadBundle\Entity\Import;
use Mautic\LeadBundle\Entity\ImportRepository;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Event\ImportProcessEvent;
use Mautic\LeadBundle\Exception\ImportDelayedException;
use Mautic\LeadBundle\Exception\ImportFailedException;
use Mautic\LeadBundle\Helper\Progress;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\ImportModel;
use Mautic\LeadBundle\Tests\StandardImportTestHelper;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ImportModelTest extends StandardImportTestHelper
{
    public function testInitEventLog(): void
    {
        $userId   = 4;
        $userName = 'John Doe';
        $fileName = 'import.csv';
        $line     = 104;
        $model    = $this->initImportModel();
        $entity   = $this->initImportEntity();
        $entity->setCreatedBy($userId)
            ->setCreatedByUser($userName)
            ->setOriginalFile($fileName);
        $log = $model->initEventLog($entity, $line);

        Assert::assertInstanceOf(LeadEventLog::class, $log);
        Assert::assertSame($userId, $log->getUserId());
        Assert::assertSame($userName, $log->getUserName());
        Assert::assertSame('lead', $log->getBundle());
        Assert::assertSame('import', $log->getObject());
        Assert::assertSame(['line' => $line, 'file' => $fileName], $log->getProperties());
    }

    public function testProcess(): void
    {
        /** @var EventDispatcherInterface|MockObject $dispatcher */
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $dispatcher->expects($this->exactly(4))
            ->method('dispatch')
            ->with(
                LeadEvents::IMPORT_ON_PROCESS,
                $this->callback(function (ImportProcessEvent $event) {
                    // Emulate a subscriber.
                    $event->setWasMerged(false);

                    return true;
                })
            );

        $model = $this->initImportModel();
        $model->setDispatcher($dispatcher);
        $entity = $this->initImportEntity();
        $entity->start();
        $model->process($entity, new Progress());
        $entity->end();

        Assert::assertEquals(100, $entity->getProgressPercentage());
        Assert::assertSame(4, $entity->getInsertedCount());
        Assert::assertSame(2, $entity->getIgnoredCount());
        Assert::assertSame(Import::IMPORTED, $entity->getStatus());
    }

    public function testCheckParallelImportLimitWhenMore(): void
    {
        $model  = $this->getMockBuilder(ImportModel::class)
            ->onlyMethods(['getParallelImportLimit', 'getRepository'])
            ->disableOriginalConstructor()
            ->getMock();

        $model->expects($this->once())
            ->method('getParallelImportLimit')
            ->will($this->returnValue(4));

        $repository = $this->getMockBuilder(ImportRepository::class)
            ->onlyMethods(['countImportsWithStatuses'])
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->once())
            ->method('countImportsWithStatuses')
            ->will($this->returnValue(5));

        $model->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($repository));

        $result = $model->checkParallelImportLimit();

        Assert::assertFalse($result);
    }

    public function testCheckParallelImportLimitWhenEqual(): void
    {
        $model  = $this->getMockBuilder(ImportModel::class)
            ->onlyMethods(['getParallelImportLimit', 'getRepository'])
            ->disableOriginalConstructor()
            ->getMock();

        $model->expects($this->once())
            ->method('getParallelImportLimit')
            ->will($this->returnValue(4));

        $repository = $this->getMockBuilder(ImportRepository::class)
            ->onlyMethods(['countImportsWithStatuses'])
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->once())
            ->method('countImportsWithStatuses')
            ->will($this->returnValue(4));

        $model->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($repository));

        $result = $model->checkParallelImportLimit();

        Assert::assertFalse($result);
    }

    public function testCheckParallelImportLimitWhenLess(): void
    {
        $model  = $this->getMockBuilder(ImportModel::class)
            ->onlyMethods(['getParallelImportLimit', 'getRepository'])
            ->disableOriginalConstructor()
            ->getMock();

        $model->expects($this->once())
            ->method('getParallelImportLimit')
            ->will($this->returnValue(6));

        $repository = $this->getMockBuilder(ImportRepository::class)
            ->onlyMethods(['countImportsWithStatuses'])
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->once())
            ->method('countImportsWithStatuses')
            ->will($this->returnValue(5));

        $model->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($repository));

        $result = $model->checkParallelImportLimit();

        Assert::assertTrue($result);
    }

    public function testBeginImportWhenParallelLimitHit(): void
    {
        $model = $this->getMockBuilder(ImportModel::class)
            ->onlyMethods(['checkParallelImportLimit', 'setGhostImportsAsFailed', 'saveEntity', 'getParallelImportLimit'])
            ->disableOriginalConstructor()
            ->getMock();

        $model->method('checkParallelImportLimit')
            ->will($this->returnValue(false));

        $model->expects($this->once())
            ->method('getParallelImportLimit')
            ->will($this->returnValue(1));

        $model->setTranslator($this->getTranslatorMock());

        $entity = $this->initImportEntity(['canProceed']);

        $entity->method('canProceed')
            ->will($this->returnValue(true));

        try {
            $model->beginImport($entity, new Progress());
            $this->fail();
        } catch (ImportDelayedException $e) {
            // This is expected
        }

        Assert::assertEquals(0, $entity->getProgressPercentage());
        Assert::assertSame(0, $entity->getInsertedCount());
        Assert::assertSame(0, $entity->getIgnoredCount());
        Assert::assertSame(Import::DELAYED, $entity->getStatus());

        $model->expects($this->never())->method('saveEntity');
    }

    public function testBeginImportWhenDatabaseException(): void
    {
        $model = $this->getMockBuilder(ImportModel::class)
            ->onlyMethods(['checkParallelImportLimit', 'setGhostImportsAsFailed', 'saveEntity', 'logDebug', 'process'])
            ->disableOriginalConstructor()
            ->getMock();

        $model->expects($this->once())
            ->method('checkParallelImportLimit')
            ->will($this->returnValue(true));

        $model->expects($this->once())
            ->method('process')
            ->will($this->throwException(new ORMException()));

        $model->setTranslator($this->getTranslatorMock());

        $entity = $this->initImportEntity(['canProceed']);

        $entity->method('canProceed')
            ->will($this->returnValue(true));

        try {
            $model->beginImport($entity, new Progress());
            $this->fail();
        } catch (ImportFailedException $e) {
            // This is expected
        }

        Assert::assertEquals(0, $entity->getProgressPercentage());
        Assert::assertSame(0, $entity->getInsertedCount());
        Assert::assertSame(0, $entity->getIgnoredCount());
        Assert::assertSame(Import::DELAYED, $entity->getStatus());

        $model->expects($this->never())->method('saveEntity');
    }

    public function testIsEmptyCsvRow(): void
    {
        $model    = $this->initImportModel();
        $testData = [
            [
                'row' => '',
                'res' => true,
            ],
            [
                'row' => [],
                'res' => true,
            ],
            [
                'row' => [null],
                'res' => true,
            ],
            [
                'row' => [''],
                'res' => true,
            ],
            [
                'row' => ['John'],
                'res' => false,
            ],
            [
                'row' => ['John', 'Doe'],
                'res' => false,
            ],
        ];

        foreach ($testData as $test) {
            Assert::assertSame(
                $test['res'],
                $model->isEmptyCsvRow($test['row']),
                'Failed on row '.var_export($test['row'], true)
            );
        }
    }

    public function testTrimArrayValues(): void
    {
        $model    = $this->initImportModel();
        $testData = [
            [
                'row' => ['John '],
                'res' => ['John'],
            ],
            [
                'row' => ['  John  ', ' Do  e '],
                'res' => ['John', 'Do  e'],
            ],
            [
                'row' => ['key' => '  John  ', 2 => ' Do  e '],
                'res' => ['key' => 'John', 2 => 'Do  e'],
            ],
        ];

        foreach ($testData as $test) {
            Assert::assertSame(
                $test['res'],
                $model->trimArrayValues($test['row']),
                'Failed on row '.var_export($test['row'], true)
            );
        }
    }

    public function testHasMoreValuesThanColumns(): void
    {
        $model    = $this->initImportModel();
        $columns  = 3;
        $testData = [
            [
                'row' => ['John'],
                'mod' => ['John', '', ''],
                'res' => false,
            ],
            [
                'row' => ['John', 'Doe'],
                'mod' => ['John', 'Doe', ''],
                'res' => false,
            ],
            [
                'row' => ['key' => 'John', 2 => 'Doe', 'stuff'],
                'mod' => ['key' => 'John', 2 => 'Doe', 'stuff'],
                'res' => false,
            ],
            [
                'row' => ['key' => 'John', 2 => 'Doe', 'stuff', 'this is too much'],
                'mod' => ['key' => 'John', 2 => 'Doe', 'stuff', 'this is too much'],
                'res' => true,
            ],
        ];

        foreach ($testData as $test) {
            $res = $model->hasMoreValuesThanColumns($test['row'], $columns);
            Assert::assertSame(
                $test['res'],
                $res,
                'Failed on row '.var_export($test['row'], true)
            );
            Assert::assertSame($test['mod'], $test['row']);
        }
    }

    public function testLimit(): void
    {
        $model = $this->initImportModel();

        $import = new Import();
        $import->setFilePath(self::$largeCsvPath)
            ->setLineCount(511)
            ->setHeaders(self::$initialList[0])
            ->setParserConfig(
                [
                    'batchlimit' => 10,
                    'delimiter'  => ',',
                    'enclosure'  => '"',
                    'escape'     => '/',
                ]
            );

        $import->start();
        $progress = new Progress();
        // Each batch should have the last line imported recorded as limit + 1
        $model->process($import, $progress, 100);
        Assert::assertEquals(101, $import->getLastLineImported());
        $model->process($import, $progress, 100);
        Assert::assertEquals(201, $import->getLastLineImported());
        $model->process($import, $progress, 100);
        Assert::assertEquals(301, $import->getLastLineImported());
        $model->process($import, $progress, 100);
        Assert::assertEquals(401, $import->getLastLineImported());
        $model->process($import, $progress, 100);
        Assert::assertEquals(501, $import->getLastLineImported());
        $model->process($import, $progress, 100);

        // 512 is an empty line in the CSV
        Assert::assertEquals(512, $import->getLastLineImported());

        // Excluding the header but including the empty row in 512, there are 511 rows
        Assert::assertEquals(511, $import->getProcessedRows());

        $import->end();
    }

    public function testMacLineEndings(): void
    {
        $oldCsv = self::$csvPath;

        // Generate a new CSV
        self::generateSmallCSV();

        $csv = file_get_contents(self::$csvPath);
        $csv = str_replace("\n", "\r", $csv);
        file_put_contents(self::$csvPath, $csv);

        $this->testProcess();

        @unlink(self::$csvPath);

        self::$csvPath = $oldCsv;
    }

    public function testItLogsDBErrorIfTheEntityManagerIsClosed(): void
    {
        $this->generateSmallCSV();
        $dispatcher    = $this->createMock(EventDispatcherInterface::class);
        $entityManager = $this->getEntityManagerMock();

        $entityManager->expects($this->any())
            ->method('isOpen')
            ->willReturn(false);

        $this->expectException(ORMException::class);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->willThrowException(new ORMException('Some DB error'));

        $importModel = $this->initImportModel();
        $importModel->setDispatcher($dispatcher);
        $import = $this->initImportEntity();
        $importModel->setEntityManager($entityManager);
        $import->start();
        $importModel->process($import, new Progress());
        $import->end();

        Assert::assertSame(Import::FAILED, $import->getStatus());
    }
}
