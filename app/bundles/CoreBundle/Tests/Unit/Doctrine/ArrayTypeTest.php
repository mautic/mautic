<?php

namespace Mautic\CoreBundle\Tests\Unit\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Types\Type;
use Mautic\CoreBundle\Doctrine\Type\ArrayType;

class ExampleClassWithPrivateProperty
{
    /** @noinspection PhpUnusedPrivateFieldInspection */
    private $test = 'value';
}

class ExampleClassWithProtectedProperty
{
    protected $test = 'value';
}

class ExampleClassWithPublicProperty
{
    public $test = 'value';
}

class ArrayTypeTest extends \PHPUnit\Framework\TestCase
{
    const MAUTIC_ARRAY_TYPE_NAME = 'mautic-array-type';

    /** @var ArrayType */
    private $arrayType;

    /** @var AbstractPlatform */
    private $platform;

    protected function setUp(): void
    {
        parent::setUp();

        if (!Type::hasType(self::MAUTIC_ARRAY_TYPE_NAME)) {
            Type::addType(self::MAUTIC_ARRAY_TYPE_NAME, 'Mautic\CoreBundle\Doctrine\Type\ArrayType');
        }

        $this->arrayType = Type::getType(self::MAUTIC_ARRAY_TYPE_NAME);

        $this->platform = new MySqlPlatform();
    }

    public function testGivenSimpleArrayWhenConvertsToDatabaseValueThenGetEncodedData()
    {
        $stringWithUtf8Characters = '--ěš--';
        $result                   = $this->arrayType->convertToDatabaseValue([$stringWithUtf8Characters], $this->platform);
        $this->assertEquals('a:1:{i:0;s:8:"--ěš--";}', $result);
    }

    public function testGivenNullPoisonedStringWhenConvertsToDatabaseValueThenError()
    {
        $this->expectException('Doctrine\DBAL\Types\ConversionException');

        $this->arrayType->convertToDatabaseValue(["abcd\0efgh"], $this->platform);
    }

    public function testGivenObjectWithPrivatePropertyWhenConvertsToDatabaseValueThenError()
    {
        $this->expectException('Doctrine\DBAL\Types\ConversionException');

        $this->arrayType->convertToDatabaseValue([new ExampleClassWithPrivateProperty()], $this->platform);
    }

    public function testGivenObjectWithProtectedPropertyWhenConvertsToDatabaseValueThenError()
    {
        $this->expectException('Doctrine\DBAL\Types\ConversionException');

        $this->arrayType->convertToDatabaseValue([new ExampleClassWithProtectedProperty()], $this->platform);
    }

    public function testGivenObjectWithPublicPropertyWhenConvertsToDatabaseValueThenGetEncodedData()
    {
        $result = $this->arrayType->convertToDatabaseValue([new ExampleClassWithPublicProperty()], $this->platform);
        $this->assertEquals(
            'a:1:{i:0;O:68:"Mautic\CoreBundle\Tests\Unit\Doctrine\ExampleClassWithPublicProperty":1:{s:4:"test";s:5:"value";}}',
            $result
        );
    }

    public function testGivenStdClassWhenConvertsToDatabaseValueThenGetEncodedData()
    {
        $object       = new \stdClass();
        $object->test = 'value';

        $result = $this->arrayType->convertToDatabaseValue([$object], $this->platform);
        $this->assertEquals(
            'a:1:{i:0;O:8:"stdClass":1:{s:4:"test";s:5:"value";}}',
            $result
        );
    }

    public function testGivenObjectWithPrivatePropertyWhenConvertsToPHPValueThenGetsArrayWithoutObject()
    {
        $array = [
            0,
            new ExampleClassWithPrivateProperty(),
        ];

        $array = serialize($array);

        $result = $this->arrayType->convertToPHPValue($array, $this->platform);
        $this->assertEquals(
            [0],
            $result
        );
    }

    public function testGivenObjectWithProtectedPropertyWhenConvertsToPHPValueThenGetsArrayWithoutObject()
    {
        $array = [
            0,
            new ExampleClassWithProtectedProperty(),
        ];

        $array = serialize($array);

        $result = $this->arrayType->convertToPHPValue($array, $this->platform);
        $this->assertEquals(
            [0],
            $result
        );
    }

    public function testGivenObjectWithPublicPropertyWhenConvertsToPHPValueThenGetsArrayWithObject()
    {
        $array = [
            0,
            new ExampleClassWithPublicProperty(),
        ];

        $array = serialize($array);

        $result = $this->arrayType->convertToPHPValue($array, $this->platform);
        $this->assertEquals(
            [
                0,
                new ExampleClassWithPublicProperty(),
            ],
            $result
        );
    }
}
