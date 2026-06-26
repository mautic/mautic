<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Tests\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\Entity\IntegrationEntity;
use Mautic\PluginBundle\Entity\IntegrationEntityRepository;
use PHPUnit\Framework\Assert;

/**
 * IntegrationRepository.
 */
class IntegrationEntityRepositoryTest extends MauticMysqlTestCase
{
    public const INTEGRATION        = 'someIntegration';
    public const INTEGRATION_ENTITY = 'someIntegrationEntity';
    public const INTERNAL_ENTITY    = 'lead';

    /**
     * @var string
     */
    private $prefix;

    /**
     * @var IntegrationEntityRepository
     */
    private $integrationEntityRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prefix                      = static::getContainer()->getParameter('mautic.db_table_prefix');
        $this->integrationEntityRepository = $this->em->getRepository(IntegrationEntity::class);
    }

    public function testThatGetIntegrationsEntityIdReturnsCorrectValues(): void
    {
        $now                 = new \DateTimeImmutable();
        $integrationEntityId = random_int(1, 1000);
        $internalEntityId    = random_int(1, 1000);

        $this->connection->insert($this->prefix.'integration_entity', [
            'date_added'            => $now->format('Y-m-d H:i:s'),
            'integration'           => 'someIntegration',
            'integration_entity'    => 'someIntegrationEntity',
            'integration_entity_id' => $integrationEntityId,
            'internal_entity'       => 'someInternalEntity',
            'internal_entity_id'    => $internalEntityId,
            'last_sync_date'        => null,
            'internal'              => 'someInternalValue',
        ]);

        $results = $this->integrationEntityRepository->getIntegrationsEntityId(
            'someIntegration',
            'someIntegrationEntity',
            'someInternalEntity',
            [$internalEntityId],
            null,
            null,
            false,
            0,
            0
        );

        Assert::assertCount(1, $results);
        Assert::assertSame($integrationEntityId, (int) $results[0]['integration_entity_id']);
        Assert::assertSame($internalEntityId, (int) $results[0]['internal_entity_id']);
    }

    public function testFindLeadsToUpdate(): void
    {
        $integrationEntityId = random_int(1, 1000);
        // Create lead
        $lead = $this->createLead('test@example.com', 'TestName');

        $this->createIntegrationEntity($integrationEntityId, $lead->getId());

        $results = $this->integrationEntityRepository->findLeadsToUpdate(
            self::INTEGRATION,
            self::INTERNAL_ENTITY,
            'l.firstName',
            1,
            null,
            null,
            [self::INTEGRATION_ENTITY],
            [99999]
        );

        $this->assertCount(1, $results[self::INTEGRATION_ENTITY], 'Excluding the random integration id.');

        $results = $this->integrationEntityRepository->findLeadsToUpdate(
            self::INTEGRATION,
            self::INTERNAL_ENTITY,
            'l.firstName',
            1,
            null,
            null,
            [self::INTEGRATION_ENTITY],
            [$integrationEntityId]
        );

        $this->assertCount(0, $results[self::INTEGRATION_ENTITY], 'Excluding the existing integration id.');
    }

    public function testGetIntegrationEntityByLead(): void
    {
        $integrationEntityId = random_int(1, 1000);
        // Create lead
        $lead = $this->createLead('test@example.com', 'TestName');

        $this->createIntegrationEntity($integrationEntityId, $lead->getId());

        $results = $this->integrationEntityRepository->getIntegrationEntityByLead($lead->getId(), self::INTEGRATION);
        $this->assertNotEmpty($results);
        $this->assertCount(1, $results);
    }

    public function testGetIntegrationEntityByLeadWhenNoIntegrationNamePassed(): void
    {
        $prefix = static::getContainer()->getParameter('mautic.db_table_prefix');

        $this->connection->executeQuery('SET FOREIGN_KEY_CHECKS=0;');
        $this->connection->executeQuery("INSERT INTO {$prefix}plugin_integration_settings(plugin_id, name, is_published, api_keys) VALUES (:id, :name, :isPublished, '')", ['id' => 1, 'name' => self::INTEGRATION, 'isPublished' => 1]);
        $this->connection->executeQuery('SET FOREIGN_KEY_CHECKS=1;');

        $integrationEntityId = random_int(1, 1000);
        // Create lead
        $lead = $this->createLead('test@example.com', 'TestName');

        $this->createIntegrationEntity($integrationEntityId, $lead->getId());

        $results = $this->integrationEntityRepository->getIntegrationEntityByLead($lead->getId());
        $this->assertNotEmpty($results);
        $this->assertCount(1, $results);
    }

    public function testMarkAsDeleted(): void
    {
        $lead = $this->createLead('test@example.com', 'TestName');

        $integrationEntityId = random_int(1, 1000);
        $this->createIntegrationEntity($integrationEntityId, $lead->getId());

        $this->assertCount(1, $this->integrationEntityRepository->findAll());
        $this->em->clear();

        $this->integrationEntityRepository->markAsDeleted([$integrationEntityId], self::INTEGRATION, self::INTERNAL_ENTITY);

        $this->assertCount(0, $this->integrationEntityRepository->findBy(['internalEntity' =>  self::INTERNAL_ENTITY]));
        $this->assertCount(1, $this->integrationEntityRepository->findBy(['internalEntity' => sprintf('%s-deleted', self::INTERNAL_ENTITY)]));
    }

    private function createLead(string $email, string $firstName): Lead
    {
        $lead = new Lead();
        $lead->setEmail($email);
        $lead->setFirstname($firstName);
        $lead->setDateModified(new \DateTime());

        $this->em->persist($lead);
        $this->em->flush();

        return $lead;
    }

    /**
     * @param mixed $integrationEntityId
     * @param mixed $internalEntityId
     */
    protected function createIntegrationEntity($integrationEntityId, $internalEntityId): void
    {
        $date = new \DateTime();

        $integrationEntity = new IntegrationEntity();

        $integrationEntity->setDateAdded($date->modify('-5 min'));
        $integrationEntity->setLastSyncDate($date->modify('-5 min'));
        $integrationEntity->setIntegration(self::INTEGRATION);
        $integrationEntity->setIntegrationEntity(self::INTEGRATION_ENTITY);
        $integrationEntity->setIntegrationEntityId((string) $integrationEntityId);
        $integrationEntity->setInternalEntity(self::INTERNAL_ENTITY);
        $integrationEntity->setInternalEntityId($internalEntityId);
        $integrationEntity->setChanges([
            'firstname' => 'Some',
        ]);

        $this->em->persist($integrationEntity);
        $this->em->flush();
    }
}
