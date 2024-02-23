<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Functional\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\IntegrationsBundle\Entity\FieldChange;
use Mautic\IntegrationsBundle\Entity\FieldChangeRepository;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Assert;

final class FieldChangeRepositoryTest extends MauticMysqlTestCase
{
    private const INTEGRATION = 'someIntegration';
    private const COLUMN_NAME = 'some_column';
    private const OBJECT_ID   = 100;

    private FieldChangeRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->em->getRepository(FieldChange::class);
    }

    public function testThatFindChangesBeforeMethodReturnsChangesInCorrectOrder(): void
    {
        $fieldChanges = $this->generateFieldChanges(3);
        $fieldChanges[0]->setObjectId(3);
        $fieldChanges[1]->setObjectId(1);
        $fieldChanges[2]->setObjectId(2);

        foreach ($fieldChanges as $fieldChange) {
            $this->em->persist($fieldChange);
        }

        $this->em->flush();

        $changes = $this->repository->findChangesBefore(
            static::INTEGRATION,
            Lead::class,
            $this->getNow()->modify('+30 seconds'),
            null,
            2
        );

        Assert::assertSame(1, (int) $changes[0]['object_id']);
        Assert::assertSame(2, (int) $changes[1]['object_id']);
    }

    public function testThatItDoesntDeleteObjectsThatCameDuringInternalSynchronization(): void
    {
        $now          = $this->getNow();
        $fieldChanges = $this->generateFieldChanges(2);
        $aDayLater    = (clone $now)->modify('+1 day'); // Don't use \DateTimeImmutable because entity expects \DateTime
        $fieldChanges[1]->setModifiedAt($aDayLater);

        foreach ($fieldChanges as $fieldChange) {
            $this->em->persist($fieldChange);
        }

        $this->em->flush();

        $this->repository->deleteEntitiesForObject(
            static::OBJECT_ID,
            Lead::class,
            static::INTEGRATION,
            $now->modify('+30 seconds')
        );

        $remainingChanges = $this->repository->findAll();
        Assert::assertCount(1, $remainingChanges);
        Assert::assertSame($fieldChanges[1]->getId(), $remainingChanges[0]->getId());
    }

    /**
     * @return FieldChange[]
     */
    private function generateFieldChanges(int $quantity): array
    {
        $fieldChanges = [];
        $now          = $this->getNow();
        for ($i = 1; $i <= $quantity; ++$i) {
            $fieldChange = new FieldChange();
            $fieldChange->setIntegration(static::INTEGRATION);
            $fieldChange->setObjectId(static::OBJECT_ID);
            $fieldChange->setObjectType(Lead::class);
            $fieldChange->setModifiedAt($now);
            $fieldChange->setColumnName(static::COLUMN_NAME);
            $fieldChange->setColumnType('string');
            $fieldChange->setColumnValue((string) $i);

            $fieldChanges[] = $fieldChange;
        }

        return $fieldChanges;
    }

    private function getNow(): \DateTime
    {
        return new \DateTime('now', new \DateTimeZone('UTC'));
    }
}
