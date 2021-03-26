<?php

namespace MauticPlugin\MauticCrmBundle\Tests\Pipedrive\Command;

use Mautic\PluginBundle\Entity\IntegrationEntity;
use MauticPlugin\MauticCrmBundle\Tests\Pipedrive\PipedriveTest;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class PushDataToPipedriveCommandTest extends PipedriveTest
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
                'url'   => 'Api/Post',
                'token' => 'token',
            ]
        );

        $this->createLead();

        $this->executeCommand();

        $integrationEntities = $this->em->getRepository(IntegrationEntity::class)->findAll();

        $this->assertEquals(count($integrationEntities), 0);
    }

    public function testCommandWithDisabledComanyFeature()
    {
        $this->installPipedriveIntegration(true,
            [
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
                'url'   => 'Api/Post',
                'token' => 'token',
            ]
        );

        $this->createLead();
        $this->createCompany();

        $this->executeCommand();

        $integrationEntities = $this->em->getRepository(IntegrationEntity::class)->findAll();

        $this->assertEquals(count($integrationEntities), 1);
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
                'url'   => 'Api/Post',
                'token' => 'token',
            ]
        );

        $this->createLead();
        $this->createCompany();

        $this->executeCommand();

        $integrationEntities = $this->em->getRepository(IntegrationEntity::class)->findAll();

        $this->assertEquals(count($integrationEntities), 2);
    }

    private function executeCommand()
    {
        $kernel      = self::$container->get('kernel');
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => 'mautic:integration:pipedrive:push',
        ]);

        $output = new BufferedOutput();
        $application->run($input, $output);
    }
}
