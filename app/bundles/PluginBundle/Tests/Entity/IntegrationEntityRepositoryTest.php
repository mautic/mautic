<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Tests\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\PluginBundle\Entity\IntegrationEntity;
use Mautic\PluginBundle\Entity\IntegrationEntityRepository;
use PHPUnit\Framework\Assert;

/**
 * IntegrationRepository.
 */
class IntegrationEntityRepositoryTest extends MauticMysqlTestCase
{
    /**
     * @var IntegrationEntityRepository
     */
    private $integrationEntityRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->integrationEntityRepository = $this->em->getRepository(IntegrationEntity::class);
    }

    public function testThatGetIntegrationsEntityIdReturnsCorrectValues(): void
    {
        $now                 = new \DateTime();
        $integrationEntityId = random_int(1, 1000);
        $internalEntityId    = random_int(1, 1000);

        $integrationEntity = new IntegrationEntity();
        $integrationEntity->setDateAdded($now);
        $integrationEntity->setIntegration('someIntegration');
        $integrationEntity->setIntegrationEntity('someIntegrationEntity');
        $integrationEntity->setIntegrationEntityId((string) $integrationEntityId);
        $integrationEntity->setInternalEntity('someInternalEntity');
        $integrationEntity->setInternalEntityId($internalEntityId);
        $integrationEntity->setInternal(['someInternalValue']);

        $this->em->persist($integrationEntity);
        $this->em->flush();

        $results = $this->integrationEntityRepository->getIntegrationsEntityId(
            'someIntegration',
            'someIntegrationEntity',
            'someInternalEntity',
            [$internalEntityId],
            null,
            null,
            false,
            0,
            0,
            null
        );

        Assert::assertCount(1, $results);
        Assert::assertSame($integrationEntityId, (int) $results[0]['integration_entity_id']);
        Assert::assertSame($internalEntityId, (int) $results[0]['internal_entity_id']);
    }
}
