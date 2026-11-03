<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCompanyEnrichBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\Plugin;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use MauticPlugin\MauticCompanyEnrichBundle\Api\Client;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Routing\RouterInterface;

final class CompanyEnrichControllerTest extends MauticMysqlTestCase
{
    private int $calls = 0;
    private ?\Closure $duringRequest = null;
    private string $responseBody = '{"id":123,"first_name":"Returned","last_name":"Example","position":"Engineer","email":"never-overwrite@example.com"}';

    protected function setUp(): void
    {
        parent::setUp();
        $plugin = new Plugin();
        $plugin->setName('CompanyEnrich');
        $plugin->setBundle('MauticCompanyEnrichBundle');
        $this->em->persist($plugin);
        $integration = new Integration();
        $integration->setName('CompanyEnrich');
        $integration->setIsPublished(true);
        $integration->setApiKeys(['apikey' => 'fixture-companyenrich-key']);
        $integration->setPlugin($plugin);
        $this->em->persist($integration);
        self::getContainer()->get(IntegrationsHelper::class)->saveIntegrationConfiguration($integration);
        $this->em->clear();

        self::getContainer()->set(Client::class, new Client(new MockHttpClient(function ($method, $url, array $options): MockResponse {
            ++$this->calls;
            $this->assertContains('Authorization: Bearer fixture-companyenrich-key', $options['headers']);
            if (null !== $this->duringRequest) {
                ($this->duringRequest)();
            }

            return new MockResponse($this->responseBody, ['response_headers' => ['x-credit-cost: 1']]);
        })));
    }

    public function testApiKeyIsEncryptedInTheDatabase(): void
    {
        $route = self::getContainer()->get(RouterInterface::class)->generate('mautic_plugin_config', ['name' => 'CompanyEnrich']);
        $crawler = $this->client->request('GET', $route);
        $this->assertResponseIsSuccessful();
        $form = $crawler->selectButton('Save & Close')->form();
        $form['integration_config[apiKeys][apikey]']->setValue('fixture-companyenrich-key');
        $this->client->submit($form);
        $this->assertResponseIsSuccessful();
        $this->em->clear();
        $integration = $this->em->getRepository(Integration::class)->findOneBy(['name' => 'CompanyEnrich']);
        $this->assertInstanceOf(Integration::class, $integration);
        $this->assertTrue($integration->getIsPublished());
        $this->assertNotSame('fixture-companyenrich-key', $integration->getApiKeys()['apikey'] ?? null);
    }

    public function testUnauthorizedUserCannotRunResearchOrEnrichment(): void
    {
        $this->loginWithPermissions([]);
        $this->client->request('GET', '/s/companyenrich');
        $this->assertResponseStatusCodeSame(403);
        $this->client->request('POST', '/s/companyenrich/enrich/contact/1');
        $this->assertResponseStatusCodeSame(403);
        $this->assertSame(0, $this->calls);
    }

    public function testResearchPermissionDoesNotGrantRecordEditAccess(): void
    {
        $lead = new Lead();
        $lead->setEmail('readonly@example.com');
        $this->em->persist($lead);
        $this->em->flush();
        $this->loginWithPermissions(['plugin:plugins' => ['manage'], 'lead:leads' => ['viewown', 'viewother']]);
        $this->client->request('GET', '/s/companyenrich');
        $this->assertResponseIsSuccessful();
        $this->client->request('GET', '/s/companyenrich/enrich/contact/'.$lead->getId());
        $this->assertResponseStatusCodeSame(403);
        $this->client->request('POST', '/s/companyenrich/enrich/contact/'.$lead->getId());
        $this->assertResponseStatusCodeSame(403);
        $this->assertSame(0, $this->calls);
    }

    /** @param array<string, list<string>> $permissions */
    private function loginWithPermissions(array $permissions): User
    {
        $role = new Role();
        $role->setName('CompanyEnrich restricted');
        $role->setIsAdmin(false);
        foreach (self::getContainer()->get(CorePermissions::class)->generatePermissions($permissions) as $permission) {
            $role->addPermission($permission);
            $this->em->persist($permission);
        }
        $this->em->persist($role);
        $user = new User();
        $user->setFirstName('Restricted');
        $user->setLastName('User');
        $user->setUsername('companyenrich-restricted');
        $user->setEmail('restricted@example.com');
        $user->setPassword('unused-login-fixture');
        $user->setRole($role);
        $this->em->persist($user);
        $this->em->flush();
        $this->loginUser($user);

        return $user;
    }

    public function testAccessRevokedDuringLookupReturnsForbiddenWithoutUpdatingProfile(): void
    {
        $owner = $this->loginWithPermissions(['plugin:plugins' => ['manage'], 'lead:leads' => ['viewown', 'editown']]);
        $lead = new Lead();
        $lead->setEmail('owned@example.com');
        $lead->setOwner($owner);
        $this->em->persist($lead);
        $this->em->flush();
        $id = $lead->getId();
        $table = $this->em->getClassMetadata(Lead::class)->getTableName();
        $this->duringRequest = function () use ($table, $id): void {
            $this->connection->update($table, ['owner_id' => null, 'created_by' => null], ['id' => $id]);
        };
        $crawler = $this->client->request('GET', '/s/companyenrich/enrich/contact/'.$id);
        $this->assertResponseIsSuccessful();
        $this->client->submit($crawler->selectButton('Fill missing profile fields')->form());
        $this->assertResponseStatusCodeSame(403);
        $this->assertEmpty($this->connection->fetchOne('SELECT lastname FROM '.$table.' WHERE id = ?', [$id]));
        $this->assertSame(1, $this->calls);
    }

    public function testConcurrentIdentityChangePreventsApplyingTheOldProfile(): void
    {
        $lead = new Lead();
        $lead->setEmail('before@example.com');
        $this->em->persist($lead);
        $this->em->flush();
        $id = $lead->getId();
        $table = $this->em->getClassMetadata(Lead::class)->getTableName();
        $this->duringRequest = function () use ($table, $id): void {
            $this->connection->update($table, ['email' => 'after@example.com'], ['id' => $id]);
        };
        $crawler = $this->client->request('GET', '/s/companyenrich/enrich/contact/'.$id);
        $this->client->submit($crawler->selectButton('Fill missing profile fields')->form());
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('The record changed during research', (string) $this->client->getResponse()->getContent());
        $this->em->clear();
        $saved = $this->em->find(Lead::class, $id);
        $this->assertInstanceOf(Lead::class, $saved);
        $this->assertSame('after@example.com', $saved->getEmail());
        $this->assertEmpty($saved->getLastname());
        $this->assertSame(1, $this->calls);
    }

    public function testResearchPageLoadAndInvalidCsrfNeverSpendCredits(): void
    {
        $this->client->request('GET', '/s/companyenrich');
        $this->assertResponseIsSuccessful();
        $this->assertSame(0, $this->calls);
        $this->client->request('POST', '/s/companyenrich', ['research' => ['operation' => 'enrich_company', 'query' => '{"domain":"example.com"}', 'body' => '{}', '_token' => 'invalid']]);
        $this->assertSame(0, $this->calls);
    }

    public function testContactEnrichmentPreservesExistingValuesAndEmail(): void
    {
        $lead = new Lead();
        $lead->setEmail('existing@example.com');
        $lead->setFirstname('Existing');
        $this->em->persist($lead);
        $this->em->flush();
        $id = $lead->getId();
        $crawler = $this->client->request('GET', '/s/companyenrich/enrich/contact/'.$id);
        $this->assertResponseIsSuccessful();
        $this->assertSame(0, $this->calls);
        $this->client->submit($crawler->selectButton('Fill missing profile fields')->form());
        $this->assertResponseIsSuccessful();
        $this->em->clear();
        $saved = $this->em->find(Lead::class, $id);
        $this->assertInstanceOf(Lead::class, $saved);
        $this->assertSame('existing@example.com', $saved->getEmail());
        $this->assertSame('Existing', $saved->getFirstname());
        $this->assertSame('Example', $saved->getLastname());
        $this->assertSame(1, $this->calls);
    }

    public function testMissingRecordDoesNotSpendCredits(): void
    {
        $this->client->request('POST', '/s/companyenrich/enrich/contact/999999999');
        $this->assertResponseStatusCodeSame(404);
        $this->assertSame(0, $this->calls);
    }

    public function testCompanyEnrichmentFillsDescriptionAndPreservesNameAndWebsite(): void
    {
        $company = new Company();
        $company->setName('Existing company');
        $company->setWebsite('https://example.com/about');
        self::getContainer()->get(CompanyModel::class)->saveEntity($company);
        $id = $company->getId();
        $this->responseBody = '{"id":"00000000-0000-4000-8000-000000000001","name":"Returned name","description":"Company description","domain":"different.example"}';
        $crawler = $this->client->request('GET', '/s/companyenrich/enrich/company/'.$id);
        $this->assertResponseIsSuccessful();
        $this->assertSame(0, $this->calls);
        $this->client->submit($crawler->selectButton('Fill missing profile fields')->form());
        $this->assertResponseIsSuccessful();
        $this->em->clear();
        $saved = self::getContainer()->get(CompanyModel::class)->getEntity($id);
        $this->assertSame('Existing company', $saved->getName());
        $this->assertSame('https://example.com/about', $saved->getWebsite());
        $this->assertSame('Company description', $saved->getDescription());
        $this->assertSame(1, $this->calls);
    }
}
