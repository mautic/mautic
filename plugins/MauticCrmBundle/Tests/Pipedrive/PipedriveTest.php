<?php

namespace MauticPlugin\MauticCrmBundle\Tests\Pipedrive;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\IntegrationEntity;
use Mautic\PluginBundle\Entity\Plugin;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use MauticPlugin\MauticCrmBundle\Entity\PipedriveOwner;
use MauticPlugin\MauticCrmBundle\Integration\PipedriveIntegration;

abstract class PipedriveTest extends MauticMysqlTestCase
{
    const WEBHOOK_USER     = 'user';
    const WEBHOOK_PASSWORD = 'pa$$word';

    public function setUp()
    {
        parent::setUp();

        $GLOBALS['requests'] = [];
    }

    public function tearDown()
    {
        unset($GLOBALS['requests']);

        parent::tearDown();
    }

    public static function getData($type)
    {
        return file_get_contents(dirname(__FILE__).sprintf('/Data/%s.json', $type));
    }

    protected function makeRequest($method, $json, $addCredential = true)
    {
        $headers = !$addCredential ? [] : [
            'PHP_AUTH_USER' => self::WEBHOOK_USER,
            'PHP_AUTH_PW'   => self::WEBHOOK_PASSWORD,
        ];

        $this->client->request($method, '/plugin/pipedrive/webhook', [], [], $headers, $json);
    }

    protected function installPipedriveIntegration($published = false, array $settings = [], array $apiKeys = [], array $features = ['push_lead'], $addCredential = true)
    {
        $plugin = new Plugin();
        $plugin->setName('CRM');
        $plugin->setDescription('Enables integration with Mautic supported CRMs.');
        $plugin->setBundle('MauticCrmBundle');
        $plugin->setVersion('1.0');
        $plugin->setAuthor('Mautic');

        $this->em->persist($plugin);

        $integration = new Integration();
        $integration->setName('Pipedrive');
        $integration->setIsPublished($published);
        $integration->setFeatureSettings($settings);
        $integration->setSupportedFeatures($features);
        $integration->setPlugin($plugin);

        $this->em->persist($integration);
        $this->em->flush();

        $integrationObject = $this->getIntegrationObject();

        if ($addCredential) {
            [
            $apiKeys = array_merge($apiKeys, [
                'user'     => self::WEBHOOK_USER,
                'password' => self::WEBHOOK_PASSWORD,
            ]),
        ];
        }

        $integrationObject->encryptAndSetApiKeys($apiKeys, $integration);

        $this->em->flush();
    }

    protected function createLead($companies = [], User $owner = null)
    {
        $lead = new Lead();
        $lead->setFirstname('Firstname');
        $lead->setLastname('Lastname');
        $lead->setEmail('test@test.com');
        $lead->setPhone('555-666-777');

        if ($owner) {
            $lead->setOwner($owner);
        }

        $this->em->persist($lead);
        $this->em->flush();

        $companyModel = $this->container->get('mautic.lead.model.company');

        if ($companies instanceof Company) {
            $companies = [$companies];
        }

        foreach ($companies as $company) {
            $companyModel->addLeadToCompany($company, $lead);
            $lead->setCompany($company->getName());
        }

        return $lead;
    }

    protected function createUser($isAdmin = true, $email = 'admin@admin.com', $username = 'admin')
    {
        $role = new Role();
        $role->setName('Test');
        $role->setDescription('Test 123');
        $role->isAdmin($isAdmin);
        $this->em->persist($role);

        $userModel = $this->client->getContainer()->get('mautic.model.factory')->getModel('user');

        $user = $userModel->getEntity();
        $user->setFirstName('Admin');
        $user->setLastName('User');
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setPassword(123456);
        $user->setRole($role);

        $userModel->saveEntity($user);

        return $user;
    }

    protected function createCompany($name = 'Company Name', $address = 'Wrocław, Poland')
    {
        $company = new Company();
        $company->setName($name);
        $company->setAddress1($address);

        $this->em->persist($company);
        $this->em->flush();

        return $company;
    }

    protected function createLeadIntegrationEntity($integrationEntityId, $internalEntityId)
    {
        $date = new \DateTime();

        $integrationEntity = new IntegrationEntity();

        $integrationEntity->setDateAdded($date);
        $integrationEntity->setLastSyncDate($date);
        $integrationEntity->setIntegration(PipedriveIntegration::INTEGRATION_NAME);
        $integrationEntity->setIntegrationEntity(PipedriveIntegration::PERSON_ENTITY_TYPE);
        $integrationEntity->setIntegrationEntityId($integrationEntityId);
        $integrationEntity->setInternalEntity(PipedriveIntegration::LEAD_ENTITY_TYPE);
        $integrationEntity->setInternalEntityId($internalEntityId);

        $this->em->persist($integrationEntity);
        $this->em->flush();

        return $integrationEntity;
    }

    protected function createCompanyIntegrationEntity($integrationEntityId, $internalEntityId)
    {
        $date = new \DateTime();

        $integrationEntity = new IntegrationEntity();

        $integrationEntity->setDateAdded($date);
        $integrationEntity->setLastSyncDate($date);
        $integrationEntity->setIntegration(PipedriveIntegration::INTEGRATION_NAME);
        $integrationEntity->setIntegrationEntity(PipedriveIntegration::ORGANIZATION_ENTITY_TYPE);
        $integrationEntity->setIntegrationEntityId($integrationEntityId);
        $integrationEntity->setInternalEntity(PipedriveIntegration::COMPANY_ENTITY_TYPE);
        $integrationEntity->setInternalEntityId($internalEntityId);

        $this->em->persist($integrationEntity);
        $this->em->flush();

        return $integrationEntity;
    }

    protected function getIntegrationObject()
    {
        $integrationHelper = $this->container->get('mautic.helper.integration');

        return $integrationHelper->getIntegrationObject(PipedriveIntegration::INTEGRATION_NAME);
    }

    protected function addPipedriveOwner($pipedriveOwnerId, $email)
    {
        $pipedriveOwner = new PipedriveOwner();
        $pipedriveOwner->setEmail($email);
        $pipedriveOwner->setOwnerId($pipedriveOwnerId);

        $this->em->persist($pipedriveOwner);
        $this->em->flush();

        return $pipedriveOwner;
    }

    protected function addOwnerToCompany(User $user, Company $company)
    {
        $company->setOwner($user);

        $this->em->persist($company);
        $this->em->flush();
    }
}
