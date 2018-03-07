<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Helper;

use Mautic\LeadBundle\Helper\IdentifyCompanyHelper;
use Mautic\LeadBundle\Model\CompanyModel;

class IdentifyCompanyHelperTest extends \PHPUnit_Framework_TestCase
{
    public function testDomainExistsRealDomain()
    {
        $helper     = new IdentifyCompanyHelper();
        $reflection = new \ReflectionClass(IdentifyCompanyHelper::class);
        $method     = $reflection->getMethod('domainExists');
        $method->setAccessible(true);
        $result = $method->invokeArgs($helper, ['hello@mautic.org']);

        $this->assertTrue(is_string($result));
        $this->assertGreaterThan(0, strlen($result));
    }

    public function testDomainExistsWithFakeDomain()
    {
        $helper     = new IdentifyCompanyHelper();
        $reflection = new \ReflectionClass(IdentifyCompanyHelper::class);
        $method     = $reflection->getMethod('domainExists');
        $method->setAccessible(true);
        $result = $method->invokeArgs($helper, ['hello@domain.fake']);

        $this->assertFalse($result);
    }

    public function testFindCompanyByName()
    {
        $company = [
            'company' => 'Mautic',
        ];

        $expected = [
            'company'        => 'Mautic',
            'companycity'    => '',
            'companystate'   => '',
            'companycountry' => '',
            'companyname'    => 'Mautic',
            'companywebsite' => null,
        ];

        $model = $this->getMockBuilder(CompanyModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $helper     = new IdentifyCompanyHelper();
        $reflection = new \ReflectionClass(IdentifyCompanyHelper::class);
        $method     = $reflection->getMethod('findCompany');
        $method->setAccessible(true);
        list($resultCompany, $entities) = $method->invokeArgs($helper, [$company, $model]);

        $this->assertEquals($expected, $resultCompany);
    }

    public function testFindCompanyByNameWithValidEmail()
    {
        $company = [
            'company'      => 'Mautic',
            'companyemail' => 'hello@mautic.org',
        ];

        $expected = [
            'company'        => 'Mautic',
            'companycity'    => '',
            'companystate'   => '',
            'companycountry' => '',
            'companyname'    => 'Mautic',
            'companywebsite' => 'mautic.org',
            'companyemail'   => 'hello@mautic.org',
        ];

        $model = $this->getMockBuilder(CompanyModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $helper     = new IdentifyCompanyHelper();
        $reflection = new \ReflectionClass(IdentifyCompanyHelper::class);
        $method     = $reflection->getMethod('findCompany');
        $method->setAccessible(true);
        list($resultCompany, $entities) = $method->invokeArgs($helper, [$company, $model]);

        $this->assertEquals($expected, $resultCompany);
    }

    public function testFindCompanyByNameWithValidEmailAndCustomWebsite()
    {
        $company = [
            'company'        => 'Mautic',
            'companyemail'   => 'hello@mautic.org',
            'companywebsite' => 'https://mautic.org',
        ];

        $expected = [
            'company'        => 'Mautic',
            'companycity'    => '',
            'companystate'   => '',
            'companycountry' => '',
            'companyname'    => 'Mautic',
            'companywebsite' => 'https://mautic.org',
            'companyemail'   => 'hello@mautic.org',
        ];

        $model = $this->getMockBuilder(CompanyModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $helper     = new IdentifyCompanyHelper();
        $reflection = new \ReflectionClass(IdentifyCompanyHelper::class);
        $method     = $reflection->getMethod('findCompany');
        $method->setAccessible(true);
        list($resultCompany, $entities) = $method->invokeArgs($helper, [$company, $model]);

        $this->assertEquals($expected, $resultCompany);
    }
}
