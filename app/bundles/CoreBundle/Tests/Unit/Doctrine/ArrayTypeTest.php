<?php

namespace Mautic\CoreBundle\Tests\Unit\Doctrine;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Types\Type;

class ExampleClassWithPrivateProperty
{
    /**
     * @phpstan-ignore-next-line
     */
    private $test = 'value';
}

class ExampleClassWithProtectedProperty
{
    /**
     * @phpstan-ignore-next-line
     */
    protected $test = 'value';
}

class ExampleClassWithPublicProperty
{
    /**
     * @phpstan-ignore-next-line
     */
    public $test = 'value';
}

class ArrayTypeTest extends \PHPUnit\Framework\TestCase
{
    public const MAUTIC_ARRAY_TYPE_NAME = 'mautic-array-type';

    /**
     * @var Type
     */
    private $arrayType;

    private MySQLPlatform $platform;

    protected function setUp(): void
    {
        parent::setUp();

        if (!Type::hasType(self::MAUTIC_ARRAY_TYPE_NAME)) {
            Type::addType(self::MAUTIC_ARRAY_TYPE_NAME, \Mautic\CoreBundle\Doctrine\Type\ArrayType::class);
        }

        $this->arrayType = Type::getType(self::MAUTIC_ARRAY_TYPE_NAME);

        $this->platform = new MySQLPlatform();
    }

    public function testGivenSimpleArrayWhenConvertsToDatabaseValueThenGetEncodedData(): void
    {
        $stringWithUtf8Characters = '--ěš--';
        $result                   = $this->arrayType->convertToDatabaseValue([$stringWithUtf8Characters], $this->platform);
        $this->assertEquals('a:1:{i:0;s:8:"--ěš--";}', $result);
    }

    public function testGivenNullPoisonedStringWhenConvertsToDatabaseValueThenError(): void
    {
        $this->expectException(\Doctrine\DBAL\Types\ConversionException::class);

        $this->arrayType->convertToDatabaseValue(["abcd\0efgh"], $this->platform);
    }

    public function testGivenObjectWithPrivatePropertyWhenConvertsToDatabaseValueThenError(): void
    {
        $this->expectException(\Doctrine\DBAL\Types\ConversionException::class);

        $this->arrayType->convertToDatabaseValue([new ExampleClassWithPrivateProperty()], $this->platform);
    }

    public function testGivenObjectWithProtectedPropertyWhenConvertsToDatabaseValueThenError(): void
    {
        $this->expectException(\Doctrine\DBAL\Types\ConversionException::class);

        $this->arrayType->convertToDatabaseValue([new ExampleClassWithProtectedProperty()], $this->platform);
    }

    public function testGivenObjectWithPublicPropertyWhenConvertsToDatabaseValueThenGetEncodedData(): void
    {
        $result = $this->arrayType->convertToDatabaseValue([new ExampleClassWithPublicProperty()], $this->platform);
        $this->assertEquals(
            'a:1:{i:0;O:68:"Mautic\CoreBundle\Tests\Unit\Doctrine\ExampleClassWithPublicProperty":1:{s:4:"test";s:5:"value";}}',
            $result
        );
    }

    public function testGivenStdClassWhenConvertsToDatabaseValueThenGetEncodedData(): void
    {
        $object       = new \stdClass();
        $object->test = 'value';

        $result = $this->arrayType->convertToDatabaseValue([$object], $this->platform);
        $this->assertEquals(
            'a:1:{i:0;O:8:"stdClass":1:{s:4:"test";s:5:"value";}}',
            $result
        );
    }

    public function testGivenObjectWithPrivatePropertyWhenConvertsToDatabaseValue(): void
    {
        $value = [
            'fields' => [
                'field_account_executive_o' => [
                    null,
                    new \Mautic\IntegrationsBundle\Sync\DAO\Value\ReferenceValueDAO(),
                ],
            ],
            'dateModified' => [
                '2022-05-02T21:39:27+00:00',
                '2022-05-03T14:22:33+00:00',
            ],
        ];

        $serialized   = $this->arrayType->convertToDatabaseValue($value, $this->platform);
        $unserialized = $this->arrayType->convertToPHPValue($serialized, $this->platform);

        $this->assertEquals($value, $unserialized);
    }

    public function testGivenObjectWithPrivatePropertyWhenConvertsToPHPValueThenGetsArrayWithoutObject(): void
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

    public function testGivenObjectWithProtectedPropertyWhenConvertsToPHPValueThenGetsArrayWithoutObject(): void
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

    public function testGivenObjectWithPublicPropertyWhenConvertsToPHPValueThenGetsArrayWithObject(): void
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
