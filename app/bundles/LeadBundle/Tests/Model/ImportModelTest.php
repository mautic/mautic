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

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\LeadBundle\Entity\Import;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Model\ImportModel;
use Mautic\LeadBundle\Model\LeadModel;

class ImportModelTest extends \PHPUnit_Framework_TestCase
{
    public function testInitEventLog()
    {
        $userId   = 4;
        $userName = 'John Doe';
        $fileName = 'import.csv';
        $line     = 104;
        $model    = $this->initImportModel();
        $entity   = new Import();
        $entity->setCreatedBy($userId)
            ->setCreatedByUser($userName)
            ->setOriginalFile($fileName);
        $log = $model->initEventLog($entity, $line);

        $this->assertTrue($log instanceof LeadEventLog);
        $this->assertSame($userId, $log->getUserId());
        $this->assertSame($userName, $log->getUserName());
        $this->assertSame('lead', $log->getBundle());
        $this->assertSame('import', $log->getObject());
        $this->assertSame(['line' => $line, 'file' => $fileName], $log->getProperties());
    }

    /**
     * Initialize the ImportModel object.
     *
     * @return ImportModel
     */
    protected function initImportModel()
    {
        $pathsHelper = $this->getMockBuilder(PathsHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $leadModel = $this->getMockBuilder(LeadModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $notificationModel = $this->getMockBuilder(NotificationModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $coreParametersHelper = $this->getMockBuilder(CoreParametersHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        return new ImportModel($pathsHelper, $leadModel, $notificationModel, $coreParametersHelper);
    }
}
