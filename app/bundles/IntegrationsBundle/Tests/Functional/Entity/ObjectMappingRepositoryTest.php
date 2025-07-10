<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Functional\Entity;

use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\IntegrationsBundle\Entity\ObjectMapping;
use Mautic\IntegrationsBundle\Entity\ObjectMappingRepository;
use PHPUnit\Framework\Assert;

final class ObjectMappingRepositoryTest extends MauticMysqlTestCase
{
    private const INTEGRATION             = 'integration';

    private const INTEGRATION_OBJECT_NAME = 'integrationObjectName';

    private const INTEGRATION_OBJECT_ID   = '123';

    private const INTERNAL_OBJECT_NAME    = 'internalObjectName';

    private const INTERNAL_OBJECT_ID      = 569;

    private ObjectMappingRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = static::getContainer()->get('mautic.integrations.repository.object_mapping');
    }

    public function testGetInternalObject(): void
    {
        $arguments = [
            self::INTEGRATION,
            self::INTEGRATION_OBJECT_NAME,
            self::INTEGRATION_OBJECT_ID,
            self::INTERNAL_OBJECT_NAME,
        ];

        Assert::assertNull($this->repository->getInternalObject(...$arguments));
        Assert::assertNull($this->repository->getInternalObjectWithLock(...$arguments));

        $objectMapping = $this->createObjectMapping();
        $expectedData  = [
            'id'                       => (string) $objectMapping->getId(),
            'date_created'             => $objectMapping->getDateCreated()->format(DateTimeHelper::FORMAT_DB),
            'integration'              => $objectMapping->getIntegration(),
            'internal_object_name'     => $objectMapping->getInternalObjectName(),
            'internal_object_id'       => (string) $objectMapping->getInternalObjectId(),
            'integration_object_name'  => $objectMapping->getIntegrationObjectName(),
            'integration_object_id'    => $objectMapping->getIntegrationObjectId(),
            'last_sync_date'           => $objectMapping->getLastSyncDate()->format(DateTimeHelper::FORMAT_DB),
            'internal_storage'         => json_encode($objectMapping->getInternalStorage()),
            'is_deleted'               => (string) (int) $objectMapping->isDeleted(),
            'integration_reference_id' => $objectMapping->getIntegrationReferenceId(),
        ];
        Assert::assertSame($expectedData, $this->repository->getInternalObject(...$arguments));
        Assert::assertSame($expectedData, $this->repository->getInternalObjectWithLock(...$arguments)); // @phpstan-ignore argument.unresolvableType (PHPStan bug I guess)
    }

    public function testUpdateInternalObjectId(): void
    {
        $objectMapping       = $this->createObjectMapping();
        $newInternalObjectId = $objectMapping->getInternalObjectId() + 100;

        $this->repository->updateInternalObjectId($newInternalObjectId, $objectMapping->getId());

        Assert::assertSame((string) $newInternalObjectId, $this->repository->getValue($objectMapping->getId(), 'internal_object_id'));
    }

    public function testInsert(): void
    {
        $affectedRows = $this->repository->insert(
            self::INTEGRATION,
            self::INTEGRATION_OBJECT_NAME,
            self::INTEGRATION_OBJECT_ID,
            self::INTERNAL_OBJECT_NAME,
            self::INTERNAL_OBJECT_ID,
            $now = new \DateTimeImmutable()
        );

        Assert::assertSame(1, $affectedRows);
        Assert::assertSame(1, $this->repository->count([]));

        $objectMapping = $this->repository->findAll()[0];
        \assert($objectMapping instanceof ObjectMapping);

        Assert::assertSame(self::INTEGRATION, $objectMapping->getIntegration());
        Assert::assertSame(self::INTEGRATION_OBJECT_NAME, $objectMapping->getIntegrationObjectName());
        Assert::assertSame(self::INTEGRATION_OBJECT_ID, $objectMapping->getIntegrationObjectId());
        Assert::assertSame(self::INTERNAL_OBJECT_NAME, $objectMapping->getInternalObjectName());
        Assert::assertSame(self::INTERNAL_OBJECT_ID, $objectMapping->getInternalObjectId());
        Assert::assertSame($now->getTimestamp(), $objectMapping->getDateCreated()->getTimestamp());
        Assert::assertSame($now->getTimestamp(), $objectMapping->getLastSyncDate()->getTimestamp());
        Assert::assertNull($objectMapping->getIntegrationReferenceId());
        Assert::assertFalse($objectMapping->isDeleted());
        Assert::assertEmpty($objectMapping->getInternalStorage());
    }

    private function createObjectMapping(): ObjectMapping
    {
        $objectMapping = new ObjectMapping();
        $objectMapping->setIntegration(self::INTEGRATION);
        $objectMapping->setIntegrationObjectName(self::INTEGRATION_OBJECT_NAME);
        $objectMapping->setIntegrationObjectId(self::INTEGRATION_OBJECT_ID);
        $objectMapping->setInternalObjectName(self::INTERNAL_OBJECT_NAME);
        $objectMapping->setInternalObjectId(self::INTERNAL_OBJECT_ID);

        $this->em->persist($objectMapping);
        $this->em->flush();

        return $objectMapping;
    }
}
