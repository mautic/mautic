<?php

namespace MauticPlugin\MauticCrmBundle\Tests\Pipedrive\Command;

use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyLead;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\Entity\IntegrationEntity;
use MauticPlugin\MauticCrmBundle\Entity\PipedriveDeal;
use MauticPlugin\MauticCrmBundle\Entity\PipedriveOwner;
use MauticPlugin\MauticCrmBundle\Entity\PipedrivePipeline;
use MauticPlugin\MauticCrmBundle\Entity\PipedriveProduct;
use MauticPlugin\MauticCrmBundle\Entity\PipedriveStage;
use MauticPlugin\MauticCrmBundle\Tests\Pipedrive\PipedriveTest;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class FetchPipedriveDataCommandTest extends PipedriveTest
{
    public function testCommandWithdisableIntegration()
    {
        $this->installPipedriveIntegration(false,
            [
                'objects' => [
                    'company',
                ],
                'leadFields' => [
                    'first_name' => 'firstname',
                    'last_name'  => 'lastname',
                    'email'      => 'email',
                    'phone'      => 'phone',
                ],
                'companyFields' => [
                    'name'    => 'companyname',
                    'address' => 'companyaddress1',
                ],
            ], [
                'url'   => 'Api/Get',
                'token' => 'token',
            ]
        );

        $this->executeCommand();

        $owners              = $this->em->getRepository(PipedriveOwner::class)->findAll();
        $companies           = $this->em->getRepository(Company::class)->findAll();
        $leads               = $this->em->getRepository(Lead::class)->findAll();
        $companyLeads        = $this->em->getRepository(CompanyLead::class)->findAll();
        $integrationEntities = $this->em->getRepository(IntegrationEntity::class)->findAll();
        $deals               = $this->em->getRepository(PipedriveDeal::class)->findAll();
        $pipelines           = $this->em->getRepository(PipedrivePipeline::class)->findAll();
        $stages              = $this->em->getRepository(PipedriveStage::class)->findAll();
        $products            = $this->em->getRepository(PipedriveProduct::class)->findAll();

        $this->assertEquals(count($leads), 0);
        $this->assertEquals(count($companies), 0);
        $this->assertEquals(count($owners), 0);
        $this->assertEquals(count($companyLeads), 0);
        $this->assertEquals(count($integrationEntities), 0);
        $this->assertEquals(count($pipelines), 0);
        $this->assertEquals(count($stages), 0);
        $this->assertEquals(count($products), 0);
        $this->assertEquals(count($deals), 0);
    }

    public function testCommand()
    {
        $this->installPipedriveIntegration(true,
            [
                'objects' => [
                    'company',
                ],
                'leadFields' => [
                    'first_name' => 'firstname',
                    'last_name'  => 'lastname',
                    'email'      => 'email',
                    'phone'      => 'phone',
                ],
                'companyFields' => [
                    'name'    => 'companyname',
                    'address' => 'companyaddress1',
                ],
            ], [
                'url'   => 'Api/Get',
                'token' => 'token',
            ]
        );

        $this->executeCommand();

        $owners              = $this->em->getRepository(PipedriveOwner::class)->findAll();
        $companies           = $this->em->getRepository(Company::class)->findAll();
        $leads               = $this->em->getRepository(Lead::class)->findAll();
        $companyLeads        = $this->em->getRepository(CompanyLead::class)->findAll();
        $integrationEntities = $this->em->getRepository(IntegrationEntity::class)->findAll();

        $this->assertEquals(count($leads), 2);
        $this->assertEquals(count($companies), 2);
        $this->assertEquals(count($owners), 2);
        $this->assertEquals(count($companyLeads), 2);
        $this->assertEquals(count($integrationEntities), 4);

        $this->assertEquals($leads[0]->getCompany(), $companies[0]->getName());
        $this->assertEquals($leads[0]->getName(), 'Daniel Danielski');
        $this->assertEquals($leads[1]->getCompany(), $companies[1]->getName());
        $this->assertEquals($leads[1]->getName(), 'Abc User');

        $this->assertEquals($companyLeads[0]->getCompany()->getName(), $companies[0]->getName());
        $this->assertEquals($companyLeads[1]->getCompany()->getName(), $companies[1]->getName());
    }

    public function testCommandWithoutDealSupport()
    {
        $this->installPipedriveIntegration(true,
            [
                'objects' => [
                ],
                'leadFields' => [
                    'first_name' => 'firstname',
                    'last_name'  => 'lastname',
                    'email'      => 'email',
                    'phone'      => 'phone',
                ],
                'companyFields' => [
                    'name'    => 'companyname',
                    'address' => 'companyaddress1',
                ],
            ], [
                'url'   => 'Api/Get',
                'token' => 'token',
            ]
        );

        $this->executeCommand();

        $deals               = $this->em->getRepository(PipedriveDeal::class)->findAll();
        $pipelines           = $this->em->getRepository(PipedrivePipeline::class)->findAll();
        $stages              = $this->em->getRepository(PipedriveStage::class)->findAll();
        $products            = $this->em->getRepository(PipedriveProduct::class)->findAll();
        $integrationEntities = $this->em->getRepository(IntegrationEntity::class)->findAll();

        $this->assertEquals(count($pipelines), 0);
        $this->assertEquals(count($stages), 0);
        $this->assertEquals(count($products), 0);
        $this->assertEquals(count($deals), 0);

    }



    public function testCommandWithDealSupport()
    {
        $this->installPipedriveIntegration(true,
            [
                'objects' => [
                    'company',
                    'deal',
                ],
                'leadFields' => [
                    'first_name' => 'firstname',
                    'last_name'  => 'lastname',
                    'email'      => 'email',
                    'phone'      => 'phone',
                ],
                'companyFields' => [
                    'name'    => 'companyname',
                    'address' => 'companyaddress1',
                ],
            ], [
                'url'   => 'Api/Get',
                'token' => 'token',
            ]
        );

        $this->executeCommand();

        $deals               = $this->em->getRepository(PipedriveDeal::class)->findAll();
        $pipelines           = $this->em->getRepository(PipedrivePipeline::class)->findAll();
        $stages              = $this->em->getRepository(PipedriveStage::class)->findAll();
        $products            = $this->em->getRepository(PipedriveProduct::class)->findAll();

        $this->assertEquals(count($pipelines), 3);
        $this->assertEquals(count($stages), 6);
        $this->assertEquals(count($products), 4);
        $this->assertEquals(count($deals), 2);

        $this->assertEquals($pipelines[0]->getName(), 'Pipeline');
        $this->assertEquals($pipelines[0]->isActive(), true);
        $this->assertEquals($pipelines[1]->getName(), 'Pipeline 2');
        $this->assertEquals($pipelines[1]->isActive(), false);
        $this->assertEquals($pipelines[1]->getPipelineId(), 2);

        $this->assertEquals($stages[0]->getName(), 'First Contact');
        $this->assertInstanceOf(
            'MauticPlugin\MauticCrmBundle\Entity\PipedrivePipeline',
            $stages[0]->getPipeline(),
            'orphan stage ?'
        );
        $this->assertEquals($stages[0]->getPipeline()->getName(), 'Pipeline');
        $this->assertEquals($stages[5]->getPipeline()->getName(), 'Pipeline 2');
        $this->assertEquals($stages[2]->getOrder(), 3);
        $this->assertEquals($stages[2]->isActive(), true);
        $this->assertEquals($stages[3]->isActive(), false);

        $this->assertEquals($products[0]->getName(), 'Product 1');
        $this->assertEquals($products[0]->getProductId(), 1);
        $this->assertEquals($products[0]->isActive(), true);
        $this->assertEquals($products[0]->isSelectable(), true);
        $this->assertEquals($products[2]->isActive(), false);
        $this->assertEquals($products[2]->isSelectable(), false);
    }


    private function executeCommand()
    {
        $kernel      = $this->container->get('kernel');
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => 'mautic:integration:pipedrive:fetch',
        ]);

        $output = new BufferedOutput();
        $application->run($input, $output);
    }
}
