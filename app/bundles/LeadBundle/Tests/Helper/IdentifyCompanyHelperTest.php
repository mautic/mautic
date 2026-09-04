<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Helper;

use Mautic\LeadBundle\Helper\IdentifyCompanyHelper;
use Mautic\LeadBundle\Model\CompanyModel;

final class IdentifyCompanyHelperTest extends \PHPUnit\Framework\TestCase
{
    public function testFindCompanyByName(): void
    {
        $company = [
            'company' => 'Mautic',
        ];

        $expected = [
            'companyname'    => 'Mautic',
        ];

        $model = $this->createMock(CompanyModel::class);

        $model->expects($this->once())
            ->method('checkForDuplicateCompanies')
            ->willReturn([]);

        $model
            ->method('fetchCompanyFields')
            ->willReturn([['alias' => 'companyname']]);

        $helper                     = new IdentifyCompanyHelper($model);
        [$resultCompany, $entities] = $helper->findCompany($company);

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

        $model = $this->createMock(CompanyModel::class);

        $model->expects($this->once())
            ->method('checkForDuplicateCompanies')
            ->willReturn([]);

        $model
            ->method('fetchCompanyFields')
            ->willReturn([['alias' => 'companyname']]);

        $helper                     = new IdentifyCompanyHelper($model);
        [$resultCompany, $entities] = $helper->findCompany($company);

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

        $model = $this->createMock(CompanyModel::class);

        $model->expects($this->once())
            ->method('checkForDuplicateCompanies')
            ->willReturn([]);

        $model
            ->method('fetchCompanyFields')
            ->willReturn([['alias' => 'companyname']]);

        $helper                     = new IdentifyCompanyHelper($model);
        [$resultCompany, $entities] = $helper->findCompany($company);

        $this->assertEquals($expected, $resultCompany);
    }
}
