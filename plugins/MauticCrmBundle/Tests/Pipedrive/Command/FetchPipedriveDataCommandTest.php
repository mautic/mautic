<?php

namespace MauticPlugin\MauticCrmBundle\Tests\Pipedrive\Command;

use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyLead;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\Entity\IntegrationEntity;
use MauticPlugin\MauticCrmBundle\Entity\PipedriveOwner;
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

        $this->assertEquals(count($leads), 0);
        $this->assertEquals(count($companies), 0);
        $this->assertEquals(count($owners), 0);
        $this->assertEquals(count($companyLeads), 0);
        $this->assertEquals(count($integrationEntities), 0);
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

    private function executeCommand()
    {
        $kernel      = self::$container->get('kernel');
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => 'mautic:integration:pipedrive:fetch',
        ]);

        $output = new BufferedOutput();
        $application->run($input, $output);
    }
}
