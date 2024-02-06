<?php

namespace Mautic\LeadBundle\Tests\Helper;

use Mautic\LeadBundle\Helper\IdentifyCompanyHelper;
use Mautic\LeadBundle\Model\CompanyModel;

class IdentifyCompanyHelperTest extends \PHPUnit\Framework\TestCase
{
    public function testDomainExistsRealDomain(): void
    {
        $helper     = new IdentifyCompanyHelper();
        $reflection = new \ReflectionClass(IdentifyCompanyHelper::class);
        $method     = $reflection->getMethod('domainExists');
        $method->setAccessible(true);
        $result = $method->invokeArgs($helper, ['hello@mautic.org']);

        $this->assertTrue(is_string($result));
        $this->assertGreaterThan(0, strlen($result));
    }

    public function testDomainExistsWithFakeDomain(): void
    {
        $helper     = new IdentifyCompanyHelper();
        $reflection = new \ReflectionClass(IdentifyCompanyHelper::class);
        $method     = $reflection->getMethod('domainExists');
        $method->setAccessible(true);
        $result = $method->invokeArgs($helper, ['hello@domain.fake']);

        $this->assertFalse($result);
    }

    public function testFindCompanyByName(): void
    {
        $company = [
            'company' => 'Mautic',
        ];

        $expected = [
            'companyname'    => 'Mautic',
        ];

        $model = $this->getMockBuilder(CompanyModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $model->expects($this->once())
            ->method('checkForDuplicateCompanies')
            ->willReturn([]);

        $model->expects($this->any())
            ->method('fetchCompanyFields')
            ->willReturn([['alias' => 'companyname']]);

        $helper     = new IdentifyCompanyHelper();
        $reflection = new \ReflectionClass(IdentifyCompanyHelper::class);
        $method     = $reflection->getMethod('findCompany');
        $method->setAccessible(true);
        [$resultCompany, $entities] = $method->invokeArgs($helper, [$company, $model]);

        $this->assertEquals($expected, $resultCompany);
    }

    public function testFindCompanyByNameWithValidEmail(): void
    {
        $company = [
            'company'      => 'Mautic',
            'companyemail' => 'hello@mautic.org',
        ];

        $expected = [
            'companyname'    => 'Mautic',
            'companyemail'   => 'hello@mautic.org',
        ];

        $model = $this->getMockBuilder(CompanyModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $model->expects($this->once())
            ->method('checkForDuplicateCompanies')
            ->willReturn([]);

        $model->expects($this->any())
            ->method('fetchCompanyFields')
            ->willReturn([['alias' => 'companyname']]);

        $helper     = new IdentifyCompanyHelper();
        $reflection = new \ReflectionClass(IdentifyCompanyHelper::class);
        $method     = $reflection->getMethod('findCompany');
        $method->setAccessible(true);
        [$resultCompany, $entities] = $method->invokeArgs($helper, [$company, $model]);

        $this->assertEquals($expected, $resultCompany);
    }

    public function testFindCompanyByNameWithValidEmailAndCustomWebsite(): void
    {
        $company = [
            'company'        => 'Mautic',
            'companyemail'   => 'hello@mautic.org',
            'companywebsite' => 'https://mautic.org',
        ];

        $expected = [
            'companyname'    => 'Mautic',
            'companywebsite' => 'https://mautic.org',
            'companyemail'   => 'hello@mautic.org',
        ];

        $model = $this->getMockBuilder(CompanyModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $model->expects($this->once())
            ->method('checkForDuplicateCompanies')
            ->willReturn([]);

        $model->expects($this->any())
            ->method('fetchCompanyFields')
            ->willReturn([['alias' => 'companyname']]);

        $helper     = new IdentifyCompanyHelper();
        $reflection = new \ReflectionClass(IdentifyCompanyHelper::class);
        $method     = $reflection->getMethod('findCompany');
        $method->setAccessible(true);
        [$resultCompany, $entities] = $method->invokeArgs($helper, [$company, $model]);

        $this->assertEquals($expected, $resultCompany);
    }
}
