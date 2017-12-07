<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests;

use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\CoreBundle\Tests\CommonMocks;
use Mautic\LeadBundle\Entity\Import;
use Mautic\LeadBundle\Entity\ImportRepository;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Entity\LeadEventLogRepository;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\ImportModel;
use Mautic\LeadBundle\Model\LeadModel;

abstract class StandardImportTestHelper extends CommonMocks
{
    protected static $csvPath;
    protected static $initialList = [
        ['email', 'firstname', 'lastname'],
        ['john@doe.email', 'John', 'Doe'],
        ['bad.@doe.email', 'Bad', 'Doe'],
        ['donald@doe.email', 'Don', 'Doe'],
        [''],
        ['ella@doe.email', 'Ella', 'Doe'],
    ];

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $tmpFile = tempnam(sys_get_temp_dir(), 'mautic_import_test_');
        $file    = fopen($tmpFile, 'w');

        foreach (self::$initialList as $line) {
            fputcsv($file, $line);
        }

        fclose($file);

        self::$csvPath = $tmpFile;
    }

    public static function tearDownAfterClass()
    {
        if (file_exists(self::$csvPath)) {
            unlink(self::$csvPath);
        }

        parent::tearDownAfterClass();
    }

    protected function initImportEntity(array $methods = null)
    {
        $entity = $this->getMockBuilder(Import::class)
            ->setMethods($methods)
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
    protected function initImportModel()
    {
        $translator           = $this->getTranslatorMock();
        $pathsHelper          = $this->getPathsHelperMock();
        $entityManager        = $this->getEntityManagerMock();
        $coreParametersHelper = $this->getCoreParametersHelperMock();

        $logRepository = $this->getMockBuilder(LeadEventLogRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $importRepository = $this->getMockBuilder(ImportRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager->expects($this->any())
            ->method('getRepository')
            ->will(
                $this->returnValueMap(
                    [
                        ['MauticLeadBundle:LeadEventLog', $logRepository],
                        ['MauticLeadBundle:Import', $importRepository],
                    ]
                )
            );

        $leadModel = $this->getMockBuilder(LeadModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $leadModel->setEntityManager($entityManager);

        $leadModel->expects($this->any())
            ->method('getEventLogRepository')
            ->will($this->returnValue($logRepository));

        $companyModel = $this->getMockBuilder(CompanyModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $companyModel->setEntityManager($entityManager);

        $notificationModel = $this->getMockBuilder(NotificationModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $notificationModel->setEntityManager($entityManager);

        $importModel = new ImportModel($pathsHelper, $leadModel, $notificationModel, $coreParametersHelper, $companyModel);
        $importModel->setEntityManager($entityManager);
        $importModel->setTranslator($translator);

        return $importModel;
    }
}
