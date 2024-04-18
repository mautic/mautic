<?php

namespace Mautic\LeadBundle\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\CoreBundle\ProcessSignal\ProcessSignalService;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Tests\CommonMocks;
use Mautic\LeadBundle\Entity\Import;
use Mautic\LeadBundle\Entity\ImportRepository;
use Mautic\LeadBundle\Entity\LeadEventLogRepository;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\ImportModel;
use Mautic\LeadBundle\Model\LeadModel;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

abstract class StandardImportTestHelper extends CommonMocks
{
    protected $eventEntities = [];

    protected static $csvPath;

    protected static $largeCsvPath;

    /**
     * @var MockObject&EventDispatcherInterface
     */
    protected MockObject $dispatcher;

    /**
     * @var MockObject&EntityManagerInterface
     */
    protected MockObject $entityManager;

    protected static $initialList = [
        ['email', 'firstname', 'lastname'],
        ['john@doe.email', 'John', 'Doe'],
        ['bad.@doe.email', 'Bad', 'Doe'],
        ['donald@doe.email', 'Don', 'Doe'],
        [''],
        ['ella@doe.email', 'Ella', 'Doe'],
    ];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        static::generateSmallCSV();
        static::generateLargeCSV();
    }

    public static function tearDownAfterClass(): void
    {
        if (file_exists(self::$csvPath)) {
            unlink(self::$csvPath);
        }

        if (file_exists(self::$largeCsvPath)) {
            unlink(self::$largeCsvPath);
        }

        parent::tearDownAfterClass();
    }

    public static function generateSmallCSV(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'mautic_import_test_');
        $file    = fopen($tmpFile, 'w');

        foreach (self::$initialList as $line) {
            fputcsv($file, $line);
        }

        fclose($file);
        self::$csvPath = $tmpFile;
    }

    public static function generateLargeCSV(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'mautic_import_large_test_');
        $file    = fopen($tmpFile, 'w');
        fputcsv($file, ['email', 'firstname', 'lastname']);
        $counter = 510;
        while ($counter) {
            fputcsv($file, [uniqid().'@gmail.com', uniqid(), uniqid()]);

            --$counter;
        }

        fclose($file);
        self::$largeCsvPath = $tmpFile;
    }

    public function setUp(): void
    {
        defined('MAUTIC_ENV') or define('MAUTIC_ENV', 'test');

        $this->eventEntities = [];
    }

    /**
     * @return Import&MockObject
     */
    protected function initImportEntity(array $methods = null)
    {
        /** @var Import&MockObject $entity */
        $entity = $this->getMockBuilder(Import::class)
            ->onlyMethods($methods ?? [])
            ->getMock();

        $entity->setFilePath(self::$csvPath)
            ->setLineCount(count(self::$initialList))
            ->setHeaders(self::$initialList[0])
            ->setParserConfig(
                [
                    'batchlimit' => 10,
                    'delimiter'  => ',',
                    'enclosure'  => '"',
                    'escape'     => '/',
                ]
            );

        return $entity;
    }

    /**
     * Initialize the ImportModel object.
     *
     * @return ImportModel
     */
    protected function initImportModel(bool $entityManagerOpen = true)
    {
        $translator           = $this->getTranslatorMock();
        $pathsHelper          = $this->getPathsHelperMock();
        $this->entityManager  = $this->getEntityManagerMock();
        $coreParametersHelper = $this->getCoreParametersHelperMock();

        /** @var MockObject&UserHelper */
        $userHelper = $this->createMock(UserHelper::class);

        /** @var MockObject&LeadEventLogRepository */
        $logRepository = $this->createMock(LeadEventLogRepository::class);

        /** @var MockObject&ImportRepository */
        $importRepository = $this->createMock(ImportRepository::class);

        $importRepository->method('getValue')
            ->willReturn(true);

        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->will(
                $this->returnValueMap(
                    [
                        [\Mautic\LeadBundle\Entity\LeadEventLog::class, $logRepository],
                        [Import::class, $importRepository],
                    ]
                )
            );

        $this->entityManager->expects($this->any())
            ->method('isOpen')
            ->willReturn($entityManagerOpen);

        /** @var MockObject&LeadModel $leadModel */
        $leadModel = $this->getMockBuilder(LeadModel::class)
            ->disableOriginalConstructor()
            ->setConstructorArgs([16 => $this->entityManager])
            ->getMock();

        $leadModel->expects($this->any())
            ->method('getEventLogRepository')
            ->willReturn($logRepository);

        /** @var MockObject&CompanyModel $companyModel */
        $companyModel = $this->getMockBuilder(CompanyModel::class)
            ->disableOriginalConstructor()
            ->setConstructorArgs([3 => $this->entityManager])
            ->getMock();

        /** @var MockObject&NotificationModel $notificationModel */
        $notificationModel = $this->getMockBuilder(NotificationModel::class)
            ->disableOriginalConstructor()
            ->setConstructorArgs([3 => $this->entityManager])
            ->getMock();

        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);

        $importModel = new ImportModel(
            $pathsHelper,
            $leadModel,
            $notificationModel,
            $coreParametersHelper,
            $companyModel,
            $this->entityManager,
            $this->createMock(CorePermissions::class),
            $this->dispatcher,
            $this->createMock(UrlGeneratorInterface::class),
            $translator,
            $userHelper,
            $this->createMock(LoggerInterface::class),
            new ProcessSignalService()
        );

        return $importModel;
    }
}
