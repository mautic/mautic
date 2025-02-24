<?php

namespace Mautic\CoreBundle\Tests\Unit\Doctrine\Mapping;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use PHPUnit\Framework\MockObject\MockObject;

class ClassMetadataBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|ClassMetadataInfo
     */
    private MockObject $classMetadataInfo;

    private ClassMetadataBuilder $classMetadataBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->classMetadataInfo    = $this->createMock(ClassMetadataInfo::class);
        $this->classMetadataBuilder = new ClassMetadataBuilder($this->classMetadataInfo);
    }

    public function testAddNullableFieldWithoutColumnName(): void
    {
        $this->classMetadataInfo->expects($this->once())
            ->method('mapField')
            ->with([
                'fieldName' => 'column_name',
                'type'      => 'string',
                'length'    => 191,
                'nullable'  => true,
            ]);

        $this->classMetadataBuilder->addNullableField('column_name');
    }

    public function testAddNullableFieldWithColumnName(): void
    {
        $this->classMetadataInfo->expects($this->once())
            ->method('mapField')
            ->with([
                'fieldName'  => 'columnName',
                'columnName' => 'column_name',
                'type'       => 'string',
                'length'     => 191,
                'nullable'   => true,
            ]);

        $this->classMetadataBuilder->addNullableField('columnName', Types::STRING, 'column_name');
    }

    public function testaddIndexWithOptions(): void
    {
        $columns = [
            'column_1',
            'column_2',
        ];

        $options = [
            'lengths' => [
                0 => 128,
            ],
        ];

        $index_name = 'index';

        $data = $this->classMetadataBuilder->addIndexWithOptions($columns, $index_name, $options);

        $this->assertEquals($columns, $data->getClassMetadata()->table['indexes'][$index_name]['columns']);
        $this->assertEquals($options, $data->getClassMetadata()->table['indexes'][$index_name]['options']);
    }
}
