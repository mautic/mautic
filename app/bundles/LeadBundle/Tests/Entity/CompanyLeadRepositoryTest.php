<?php

namespace Mautic\LeadBundle\Tests\Entity;

use Mautic\LeadBundle\Entity\CompanyLeadRepository;
use Mautic\LeadBundle\Exception\PrimaryCompanyNotFoundException;

class CompanyLeadRepositoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|CompanyLeadRepository */
    private $repoMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->repoMock = $this->getMockBuilder(CompanyLeadRepository::class)
            ->onlyMethods(['getCompaniesByLeadId'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testGetPrimaryCompanyByLeadIdThrowsExceptionIfPrimaryIsMissing(): void
    {
        $this->repoMock->expects($this->once())
            ->method('getCompaniesByLeadId')
            ->willReturn([
                [
                    'company_name' => 'ACME #1',
                    'is_primary'   => false,
                ],
            ]);

        $this->expectException(PrimaryCompanyNotFoundException::class);
        $this->repoMock->getPrimaryCompanyByLeadId(1);
    }

    public function testGetPrimaryCompanyByLeadIdReturnsCorrectRecord(): void
    {
        $this->repoMock->expects($this->once())
            ->method('getCompaniesByLeadId')
            ->willReturn([
                [
                    'company_name' => 'ACME #1',
                    'is_primary'   => false,
                ],
                [
                    'company_name' => 'ACME #2',
                    'is_primary'   => true,
                ],
                [
                    'company_name' => 'ACME #3',
                    'is_primary'   => false,
                ],
            ]);

        $primary = $this->repoMock->getPrimaryCompanyByLeadId(1);

        $this->assertEquals(
            [
                'company_name' => 'ACME #2',
                'is_primary'   => true,
            ],
            $primary
        );
    }
}
