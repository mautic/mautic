<?php

namespace Mautic\ConfigBundle\Tests\Service;

use Mautic\ConfigBundle\Service\ConfigChangeLogger;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;

class ConfigChangeLoggerTest extends \PHPUnit\Framework\TestCase
{
    public function testSetOriginalNormData()
    {
        $ipLookupHelper = $this->createMock(IpLookupHelper::class);
        $auditLogModel  = $this->createMock(AuditLogModel::class);
        $logger         = new ConfigChangeLogger($ipLookupHelper, $auditLogModel);

        $this->assertEquals($logger, $logger->setOriginalNormData([]));
    }

    public function testOriginalNormDataExpected()
    {
        $this->expectException(\RuntimeException::class);

        $ipLookupHelper = $this->createMock(IpLookupHelper::class);
        $ipLookupHelper->expects($this->never())->method('getIpAddressFromRequest');
        $auditLogModel = $this->createMock(AuditLogModel::class);
        $auditLogModel->expects($this->never())->method('writeToLog');
        $logger = new ConfigChangeLogger($ipLookupHelper, $auditLogModel);
        $logger->log([]);
    }

    public function testNothingToLog()
    {
        $ipLookupHelper = $this->createMock(IpLookupHelper::class);
        $ipLookupHelper->expects($this->never())->method('getIpAddressFromRequest');
        $auditLogModel = $this->createMock(AuditLogModel::class);
        $auditLogModel->expects($this->never())->method('writeToLog');
        $logger = new ConfigChangeLogger($ipLookupHelper, $auditLogModel);

        $originalData = $postData = [
            'bundle' => [
                'key' => 'value',
            ],
        ];

        $this->assertEquals($logger, $logger->setOriginalNormData($originalData));
        $logger->log($postData);
    }

    public function testLog()
    {
        $change = [
            'key2' => 'changedValue',
        ];

        $filterMe = [
            'transifex_password' => 'dhjsakjfda',
            'mailer_api_key'     => 'fsjkdah',
            'mailer_is_owner'    => 'lksajhd',
        ];

        $log     = [
            'bundle'    => 'config',
            'object'    => 'config',
            'objectId'  => 0,
            'action'    => 'update',
            'details'   => $change,
            'ipAddress' => null,
        ];

        $ipLookupHelper = $this->createMock(IpLookupHelper::class);
        $ipLookupHelper->expects($this->once())->method('getIpAddressFromRequest');
        $auditLogModel = $this->createMock(AuditLogModel::class);
        $auditLogModel->expects($this->once())->method('writeToLog')->with($log);
        $logger = new ConfigChangeLogger($ipLookupHelper, $auditLogModel);

        $originalData = [
            'bundle' => [
                'key' => 'value',
            ],
            'bundle2' => [
                'parameters' => [
                    'key2' => 'value2',
                ],
            ],
        ];

        $postData = [
            'bundle' => [
                'key' => 'value',
            ],
            'bundle2' => array_merge($change, $filterMe),
        ];

        $this->assertEquals($logger, $logger->setOriginalNormData($originalData));
        $logger->log($postData);
    }
}
