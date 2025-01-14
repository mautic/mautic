<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Entity;

use Mautic\IntegrationsBundle\Entity\ObjectMapping;
use PHPUnit\Framework\TestCase;

class ObjectMappingTest extends TestCase
{
    /**
     * @var \DateTime
     */
    private $dateCreated;

    public function setUp(): void
    {
        $this->dateCreated = new \DateTime();

        parent::setUp();
    }

    public function testConstruct(): void
    {
        $objectMapping = new ObjectMapping($this->dateCreated);
        $this->assertInstanceOf(ObjectMapping::class, $objectMapping);
        $this->assertEquals($this->dateCreated, $objectMapping->getDateCreated());
    }

    public function testSetAndGetIntegrationReferenceId(): void
    {
        $objectMapping = new ObjectMapping($this->dateCreated);
        $objectMapping->setIntegrationReferenceId('ref');
        $this->assertEquals('ref', $objectMapping->getIntegrationReferenceId());
    }

    public function testLoadMetadata(): void
    {
        $metadata = new \Doctrine\ORM\Mapping\ClassMetadata(ObjectMapping::class);
        ObjectMapping::loadMetadata($metadata);

        $expectedFieldNames = [
            'id',
            'dateCreated',
            'integration',
            'internalObjectName',
            'internalObjectId',
            'integrationObjectName',
            'integrationObjectId',
            'lastSyncDate',
            'internalStorage',
            'isDeleted',
            'integrationReferenceId',
        ];
        $this->assertEquals($expectedFieldNames, $metadata->getFieldNames());

        $referenceIdMapping = $metadata->table['indexes']['integration_reference'];
        $this->assertEquals(
            [
                'integration',
                'integration_object_name',
                'integration_reference_id',
                'integration_object_id',
            ],
            $referenceIdMapping['columns'],
            'Required index is not being created.'
        );
    }
}
