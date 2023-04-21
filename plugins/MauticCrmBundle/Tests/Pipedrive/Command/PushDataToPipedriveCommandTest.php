<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCrmBundle\Tests\Pipedrive\Command;

use GuzzleHttp\Psr7\Response;
use Mautic\PluginBundle\Entity\IntegrationEntity;
use MauticPlugin\MauticCrmBundle\Tests\Pipedrive\PipedriveTest;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class PushDataToPipedriveCommandTest extends PipedriveTest
{
    public function testCommandWithDisabledIntegration(): void
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

    public function testCommandWithDisabledCompanyFeature(): void
    {
        $this->mockHandler->append(new Response(SymfonyResponse::HTTP_OK, [], self::getData('Api/Post/persons/find')));
        $this->mockHandler->append(new Response(SymfonyResponse::HTTP_OK, [], self::getData('Api/Post/persons')));

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

    public function testCommandWithFeatureEnabled(): void
    {
        $this->mockHandler->append(new Response(SymfonyResponse::HTTP_OK, [], self::getData('Api/Post/organizations')));
        $this->mockHandler->append(new Response(SymfonyResponse::HTTP_OK, [], self::getData('Api/Post/persons/find')));
        $this->mockHandler->append(new Response(SymfonyResponse::HTTP_OK, [], self::getData('Api/Post/persons')));

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
