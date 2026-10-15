<?php

namespace Mautic\CoreBundle\Tests\Unit\Doctrine;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use Mautic\CoreBundle\Doctrine\Type\ArrayType;
use Mautic\IntegrationsBundle\Sync\DAO\Value\ReferenceValueDAO;

final class ExampleClassWithPrivateProperty
{
    /**
     * @phpstan-ignore-next-line
     */
    private string $test = 'value';
}

final class ExampleClassWithProtectedProperty
{
    /**
     * @phpstan-ignore-next-line
     */
    private string $test = 'value';
}

final class ExampleClassWithPublicProperty
{
    /**
     * @phpstan-ignore-next-line
     */
    public $test = 'value';
}

final class ExampleClassDeprecatedOnWakeup
{
    /**
     * @phpstan-ignore-next-line
     */
    public $test = 'value';

    public function __wakeup(): void
    {
        trigger_error('This shape is deprecated.', E_USER_DEPRECATED);
    }
}

final class ExampleClassWithoutDeclaredProperty
{
}

final class ArrayTypeTest extends \PHPUnit\Framework\TestCase
{
    public const string MAUTIC_ARRAY_TYPE_NAME = 'mautic-array-type';

    private Type $arrayType;

    private MySQLPlatform $platform;

    protected function setUp(): void
    {
        parent::setUp();

        if (!Type::hasType(self::MAUTIC_ARRAY_TYPE_NAME)) {
            Type::addType(self::MAUTIC_ARRAY_TYPE_NAME, ArrayType::class);
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
        $this->expectException(ConversionException::class);

        $this->arrayType->convertToDatabaseValue(["abcd\0efgh"], $this->platform);
    }

    public function testGivenObjectWithPrivatePropertyWhenConvertsToDatabaseValueThenError(): void
    {
        $this->expectException(ConversionException::class);

        $this->arrayType->convertToDatabaseValue([new ExampleClassWithPrivateProperty()], $this->platform);
    }

    public function testGivenObjectWithProtectedPropertyWhenConvertsToDatabaseValueThenError(): void
    {
        $this->expectException(ConversionException::class);

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
                    new ReferenceValueDAO(),
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

    /**
     * DBAL let deprecations through rather than turning them into a conversion failure.
     * Throwing on one discards the whole stored array, unrelated values included.
     */
    public function testGivenUserDeprecationDuringUnserializeWhenConvertsToPHPValueThenKeepsTheArray(): void
    {
        $array = serialize([
            'before' => 'kept',
            'object' => new ExampleClassDeprecatedOnWakeup(),
            'after'  => 'also kept',
        ]);

        $result = $this->arrayType->convertToPHPValue($array, $this->platform);

        $this->assertEquals(
            [
                'before' => 'kept',
                'object' => new ExampleClassDeprecatedOnWakeup(),
                'after'  => 'also kept',
            ],
            $result
        );
    }

    /**
     * The realistic trigger: since PHP 8.2 unserializing a property the class no longer
     * declares raises E_DEPRECATED for the dynamic property.
     */
    public function testGivenDynamicPropertyDeprecationWhenConvertsToPHPValueThenKeepsTheArray(): void
    {
        $class = ExampleClassWithoutDeclaredProperty::class;
        $array = sprintf(
            'a:2:{s:6:"before";s:4:"kept";s:6:"object";O:%d:"%s":1:{s:7:"removed";s:5:"value";}}',
            strlen($class),
            $class
        );

        $result = $this->arrayType->convertToPHPValue($array, $this->platform);

        $this->assertIsArray($result);
        $this->assertSame('kept', $result['before'] ?? null);
    }
}
