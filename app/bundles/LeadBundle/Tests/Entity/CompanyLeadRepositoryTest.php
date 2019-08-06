<?php

namespace Mautic\LeadBundle\Tests\Entity;

use Mautic\LeadBundle\Entity\CompanyLeadRepository;
use Mautic\LeadBundle\Exception\PrimaryCompanyNotFoundException;

class CompanyLeadRepositoryTest extends \PHPUnit_Framework_TestCase
{
    public function testGetPrimaryCompanyByLeadId()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|CompanyLeadRepository $repoMock */
        $repoMock = $this->getMockBuilder(CompanyLeadRepository::class)
            ->setMethodsExcept(['getPrimaryCompanyByLeadId'])
            ->disableOriginalConstructor()
            ->getMock();

        $repoMock->expects($this->at(0))
            ->method('getCompaniesByLeadId')
            ->willReturn([
                [
                    'company_name' => 'ACME #1'
                ]
            ]);

        $repoMock->expects($this->at(1))
            ->method('getCompaniesByLeadId')
            ->willReturn([
                [
                    'company_name' => 'ACME #1'
                ],
                [
                    'company_name' => 'ACME #2',
                    'is_primary' => true
                ]
            ]);

        $repoMock->expects($this->exactly(2))->method('getCompaniesByLeadId');

        $this->expectException(PrimaryCompanyNotFoundException::class);
        $first = $repoMock->getPrimaryCompanyByLeadId(1);

        $primary = $repoMock->getPrimaryCompanyByLeadId(2);
        $this->assertEquals(
            [
                'company_name' => 'ACME #2',
                'is_primary' => true
            ],
            $primary
        );
    }
}
