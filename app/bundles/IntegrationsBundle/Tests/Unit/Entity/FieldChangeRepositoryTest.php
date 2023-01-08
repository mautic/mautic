<?php

namespace Mautic\IntegrationsBundle\Tests\Unit\Entity;

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Test\Doctrine\RepositoryConfiguratorTrait;
use Mautic\IntegrationsBundle\Entity\FieldChange;
use Mautic\IntegrationsBundle\Entity\FieldChangeRepository;
use PHPUnit\Framework\TestCase;

class FieldChangeRepositoryTest extends TestCase
{
    use RepositoryConfiguratorTrait;

    private FieldChangeRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->configureRepository(FieldChange::class);
        $this->connection->method('createQueryBuilder')->willReturnCallback(fn () => new QueryBuilder($this->connection));
    }

    public function testWhereQueryPartForFindingChangesForSingleObject(): void
    {
        $integration = 'test';
        $objectType  = 'foobar';
        $objectId    = 5;

        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->with(
                'SELECT * FROM '.MAUTIC_TABLE_PREFIX.'sync_object_field_change_report f WHERE (f.integration = :integration) AND (f.object_type = :objectType) AND (f.object_id = :objectId) ORDER BY f.modified_at ASC',
                [
                    'integration' => $integration,
                    'objectType'  => $objectType,
                    'objectId'    => $objectId,
                ]
            );

        $this->repository->findChangesForObject($integration, $objectType, $objectId);
    }
}
