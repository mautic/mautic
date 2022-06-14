<?php

namespace Mautic\IntegrationsBundle\Tests\Unit\Entity;

use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\CoreBundle\Test\Doctrine\DBALMocker;
use Mautic\IntegrationsBundle\Entity\FieldChangeRepository;
use PHPUnit\Framework\TestCase;

class FieldChangeRepositoryTest extends TestCase
{
    public function testWhereQueryPartForFindingChangesForSingleObject(): void
    {
        $dbalMock = new DBALMocker($this);
        $metadata = $this->createMock(ClassMetadata::class);

        $integration = 'test';
        $objectType  = 'foobar';
        $objectId    = 5;

        $repository = new FieldChangeRepository($dbalMock->getMockEm(), $metadata);
        $repository->findChangesForObject($integration, $objectType, $objectId);

        $where = $dbalMock->getQueryPart('where');
        $this->assertCount(1, $where);
        $this->assertCount(1, $where[0]);

        /** @var CompositeExpression $expr */
        $expr = $where[0][0];
        $this->assertSame(
            '(f.integration = :integration) AND (f.object_type = :objectType) AND (f.object_id = :objectId)',
            (string) $expr
        );

        $parameters = $dbalMock->getQueryPart('parameters');
        $this->assertCount(3, $parameters);
        $this->assertEquals('integration', $parameters[0][0]);
        $this->assertEquals($integration, $parameters[0][1]);
        $this->assertEquals('objectType', $parameters[1][0]);
        $this->assertEquals($objectType, $parameters[1][1]);
        $this->assertEquals('objectId', $parameters[2][0]);
        $this->assertEquals($objectId, $parameters[2][1]);
    }
}
