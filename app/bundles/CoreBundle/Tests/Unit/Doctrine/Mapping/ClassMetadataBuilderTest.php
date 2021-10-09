<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
    private $classMetadataInfo;

    /**
     * @var ClassMetadataBuilder
     */
    private $classMetadataBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->classMetadataInfo    = $this->createMock(ClassMetadataInfo::class);
        $this->classMetadataBuilder = new ClassMetadataBuilder($this->classMetadataInfo);
    }

    public function testAddNullableFieldWithoutColumnName()
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

    public function testAddNullableFieldWithColumnName()
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
}
