<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\Command;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyLead;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use Mautic\LeadBundle\Entity\SegmentCompany;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class UpdateCompanySegmentsCommandTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;

    public function testUpdateCompanySegmentsCommandAddItemInNewSegment(): void
    {
        $companyGlobo  = $this->createCompany('Globo', 'contact@globo.com');
        $companySbt    = $this->createCompany('SBT', 'contact@sbt.com');
        $companyRecord = $this->createCompany('Record', 'contact@record.com');

        $leadOne   = $this->createLead('John Globo Doe', 'leadone@mautic.com');
        $leadTwo   = $this->createLead('Brian Doe', 'leadtwo@mautic.com');
        $leadThree = $this->createLead('Mat Doe', 'leadthree@mautic.com');

        $leadOne->setCompany($companySbt);
        $leadOne->setPrimaryCompany($companyGlobo);

        $leadTwo->setPrimaryCompany($companyRecord);

        $leadThree->setPrimaryCompany($companyRecord);
        $leadThree->setCompany($companyGlobo);

        $this->em->persist($leadOne);
        $this->em->persist($leadTwo);
        $this->em->persist($leadThree);
        $this->em->flush();

        $companySegmentOne    = $this->createCompanySegment('Test Segment 1', 'test_segment');
        $segmentCompanyOne    = $this->addCompanyToCompanySegment($companyGlobo, $companySegmentOne);
        $filters              = [
            'filters' => [
                'glue'       => 'and',
                'operator'   => 'in',
                'properties' => [
                    'filter' => [$companySegmentOne->getId()],
                ],
                'field'  => 'company_segments',
                'type'   => 'company_segments',
                'object' => 'company_segments',
            ],
        ];
        $companySegmentTwo             = $this->createCompanySegment('Test Segment 2', 'test_segment2', true, $filters);
        $resultSegmentCompaniesBefore  = $this->em->getRepository(SegmentCompany::class)->findAll();

        self::assertCount(1, $resultSegmentCompaniesBefore);

        $kernel        = static::getContainer()->get('kernel');
        assert($kernel instanceof \Symfony\Component\HttpKernel\KernelInterface);
        $application   = new Application($kernel);
        $application->setAutoExit(false);
        $command       = $application->find('mautic:company-segments:update');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['--bypass-locking' => true]);

        $resultSegmentCompaniesAfter = $this->em->getRepository(SegmentCompany::class)->findAll();
        self::assertCount(2, $resultSegmentCompaniesAfter);
        self::assertEquals($resultSegmentCompaniesAfter[0]->getCompany()->getId(), $resultSegmentCompaniesAfter[1]->getCompany()->getId());
        self::assertEquals($resultSegmentCompaniesAfter[1]->getCompanySegment()->getId(), $companySegmentTwo->getId());
    }

    public function testUpdateLeadSegmentsUsingExcludeACompanySegment(): void
    {
        $companyGlobo  = $this->createCompany('Globo', 'contact@globo.com');
        $companySbt    = $this->createCompany('SBT', 'contact@sbt.com');
        $companyRecord = $this->createCompany('Record', 'contact@record.com');

        $leadOne   = $this->createLead('John Globo Doe', 'leadone@mautic.com');
        $leadTwo   = $this->createLead('Brian Doe', 'leadtwo@mautic.com');
        $leadThree = $this->createLead('Mat Doe', 'leadthree@mautic.com');
        $leadFour  = $this->createLead('Braw Doe', 'leadfour@mautic.com');

        $companyLeadGloboLeadOne = $this->createCompanyLead($companyGlobo, $leadOne);
        $companyLeadGloboLeadTwo = $this->createCompanyLead($companyGlobo, $leadTwo);
        $companyLeadSbtLeadThree = $this->createCompanyLead($companySbt, $leadThree);
        $companyLeadSbtLeadFour  = $this->createCompanyLead($companySbt, $leadFour);

        $totalCompanyLeadsBefore = $this->em->getRepository(CompanyLead::class)->findAll();
        self::assertCount(4, $totalCompanyLeadsBefore);
        $companySegmentOne             = $this->createCompanySegment('Test Company Segment 1', 'test_comp_segment');
        $segmentCompanyOne             = $this->addCompanyToCompanySegment($companyGlobo, $companySegmentOne);
        $resultSegmentCompaniesBefore  = $this->em->getRepository(SegmentCompany::class)->findAll();
        self::assertCount(1, $resultSegmentCompaniesBefore);

        $filtersToLeadSegment = [
            [
                'glue'       => 'and',
                'operator'   => '!in',
                'properties' => [
                    'filter' => [$companySegmentOne->getId()],
                ],
                'field'  => 'company_segments',
                'type'   => 'company_segments',
                'object' => 'company_segments',
            ],
        ];

        // Start Lead Segments
        $leadSegmentOne                = $this->createLeadSegment('Test Segment 1', 'test_segment', true, $filtersToLeadSegment);
        $leadListModel                 = static::getContainer()->get('mautic.lead.model.list');
        assert($leadListModel instanceof \Mautic\LeadBundle\Model\ListModel);
        // Get total of lead in list ( segments )
        $leadListTotalBefore = $leadListModel->getListLeadRepository()->findAll();
        // result zero because was add in $leadSegmentOne
        self::assertCount(0, $leadListTotalBefore);

        // COMMAND MAUTIC SEG UPDATE
        $kernel        = static::getContainer()->get('kernel');
        assert($kernel instanceof \Symfony\Component\HttpKernel\KernelInterface);
        $application   = new Application($kernel);
        $application->setAutoExit(false);
        $command       = $application->find('mautic:segments:update');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['--bypass-locking' => true]);

        self::assertStringContainsString('2 total contact(s) to be added', $commandTester->getDisplay());

        $leadListTotalAfter = $leadListModel->getListLeadRepository()->findAll();
        self::assertCount(2, $leadListTotalAfter);
    }

    public function testUpdateCompanySegmentsWithLeadListFilter(): void
    {
        $companyWithLeadWithoutSegment  = $this->createCompany('noleadsegment', 'contact@globo.com');
        $companyWithLeadWithSegment1    = $this->createCompany('leadsegment1', 'contact@sbt.com');
        $companyWithLeadWithSegment2    = $this->createCompany('leadsegment2', 'contact@record.com');
        $companyWithoutLead             = $this->createCompany('companywithoutlead', 'companywithout@lead.com');

        $contactWithoutSegment   = $this->createLead('Nosegment', 'leadone@mautic.com');
        $contactWithSegment1     = $this->createLead('Segment1', 'leadtwo@mautic.com');
        $contactWithSegment2     = $this->createLead('Segment2', 'leadthree@mautic.com');

        $leadSegment1 = $this->createLeadSegment('Segment 1', 'segment_1');
        $leadSegment2 = $this->createLeadSegment('Segment 2', 'segment_2');

        $this->addLeadToSegment($contactWithSegment1, $leadSegment1);
        $this->addLeadToSegment($contactWithSegment2, $leadSegment2);

        $contactWithoutSegment   = $this->createCompanyLead($companyWithLeadWithoutSegment, $contactWithoutSegment);
        $contactWithSegment1     = $this->createCompanyLead($companyWithLeadWithSegment1, $contactWithSegment1);
        $contactWithSegment2     = $this->createCompanyLead($companyWithLeadWithSegment2, $contactWithSegment2);

        $this->em->persist($contactWithoutSegment);
        $this->em->persist($contactWithSegment1);
        $this->em->persist($contactWithSegment2);
        $this->em->flush();

        $filterSegment1              = [
            'filters' => [
                'glue'       => 'and',
                'operator'   => 'in',
                'properties' => [
                    'filter' => [$leadSegment1->getId()],
                ],
                'field'  => 'contactsegmentmembership',
                'type'   => 'leadlist',
                'object' => 'any_companycontact',
            ],
        ];
        $filterSegment2              = [
            'filters' => [
                'glue'       => 'and',
                'operator'   => 'in',
                'properties' => [
                    'filter' => [$leadSegment2->getId()],
                ],
                'field'  => 'contactsegmentmembership',
                'type'   => 'leadlist',
                'object' => 'any_companycontact',
            ],
        ];
        $filterEmptySegment           = [
            'filters' => [
                'glue'       => 'and',
                'operator'   => 'empty',
                'properties' => [
                    'filter' => null,
                ],
                'field'  => 'contactsegmentmembership',
                'type'   => 'leadlist',
                'object' => 'any_companycontact',
            ],
        ];
        $filterNotEmptySegment              = [
            'filters' => [
                'glue'       => 'and',
                'operator'   => '!empty',
                'properties' => [
                    'filter' => null,
                ],
                'field'  => 'contactsegmentmembership',
                'type'   => 'leadlist',
                'object' => 'any_companycontact',
            ],
        ];
        $companySegmentLeadList1        = $this->createCompanySegment('Lead List 1 Segment Filter', 'lead_list_1_segment_filter', true, $filterSegment1);
        $companySegmentLeadList2        = $this->createCompanySegment('Lead List 2 Segment Filter', 'lead_list_2_segment_filter', true, $filterSegment2);
        $companySegmentEmptyLeadList    = $this->createCompanySegment('Empty Lead Segments', 'empty_lead_segments', true, $filterEmptySegment);
        $companySegmentNotEmptyLeadList = $this->createCompanySegment('Not Empty Lead Segments', 'not_empty_lead_segments', true, $filterNotEmptySegment);

        $kernel        = static::getContainer()->get('kernel');
        assert($kernel instanceof \Symfony\Component\HttpKernel\KernelInterface);
        $application   = new Application($kernel);
        $application->setAutoExit(false);
        $command       = $application->find('mautic:company-segments:update');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['--bypass-locking' => true]);

        $companiesInSegment1 = $this->em->getRepository(SegmentCompany::class)
            ->findBy(['companySegment' => $companySegmentLeadList1]);
        self::assertCount(1, $companiesInSegment1);
        self::assertEquals('leadsegment1', $companiesInSegment1[0]->getCompany()->getName());

        $companiesInSegment2 = $this->em->getRepository(SegmentCompany::class)
            ->findBy(['companySegment' => $companySegmentLeadList2]);
        self::assertCount(1, $companiesInSegment2);
        self::assertEquals('leadsegment2', $companiesInSegment2[0]->getCompany()->getName());

        $companiesInEmptySegment = $this->em->getRepository(SegmentCompany::class)
            ->findBy(['companySegment' => $companySegmentEmptyLeadList]);
        $companyNames = array_map(fn ($cs) => $cs->getCompany()->getName(), $companiesInEmptySegment);
        self::assertCount(2, $companiesInEmptySegment);
        self::assertContains('noleadsegment', $companyNames);
        self::assertContains('companywithoutlead', $companyNames);

        $companiesInNotEmptySegment = $this->em->getRepository(SegmentCompany::class)
            ->findBy(['companySegment' => $companySegmentNotEmptyLeadList]);
        self::assertCount(2, $companiesInNotEmptySegment);
        $companyNames = array_map(fn ($cs) => $cs->getCompany()->getName(), $companiesInNotEmptySegment);
        self::assertContains('leadsegment1', $companyNames);
        self::assertContains('leadsegment2', $companyNames);
    }

    private function createLead(string $name, string $email, ?Company $companyName = null): Lead
    {
        $lead = new Lead();
        $lead->setFirstname($name);
        $lead->setLastname($name.' lastname');
        $lead->setEmail($email);
        if (null !== $companyName) {
            $lead->setCompany($companyName);
        }
        $this->em->persist($lead);
        $this->em->flush();

        return $lead;
    }

    /**
     * @param array<mixed> $filters
     */
    private function createLeadSegment(string $name, string $alias, bool $isPublished = true, array $filters = []): LeadList
    {
        $leadList = new LeadList();
        $leadList->setPublicName($name);
        $leadList->setName($name);
        $leadList->setAlias($alias);
        $leadList->setIsPublished($isPublished);
        if ([] !== $filters) {
            $leadList->setFilters($filters);
        }
        $this->em->persist($leadList);
        $this->em->flush();

        return $leadList;
    }

    private function addLeadToSegment(Lead $lead, LeadList $segment): void
    {
        $listLead = new ListLead();
        $listLead->setLead($lead);
        $listLead->setList($segment);
        $listLead->setDateAdded(new \DateTime());
        $listLead->setManuallyAdded(true);
        $listLead->setManuallyRemoved(false);
        $this->em->persist($listLead);
        $this->em->flush();
    }
}
