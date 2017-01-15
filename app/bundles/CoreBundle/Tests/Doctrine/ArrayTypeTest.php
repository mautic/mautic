<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Doctrine;

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

/**
 * Class IpLookupFactoryTest.
 */
class ArrayTypeTest extends \PHPUnit_Framework_TestCase
{
    const MAUTIC_ARRAY_TYPE_NAME = 'mautic-array-type';

    /** @var ArrayType */
    private $arrayType;

    /** @var AbstractPlatform */
    private $platform;

    protected function setUp()
    {
        parent::setUp();

        if (!Type::hasType(self::MAUTIC_ARRAY_TYPE_NAME)) {
            Type::addType(self::MAUTIC_ARRAY_TYPE_NAME, 'Mautic\CoreBundle\Doctrine\Type\ArrayType');
        }

        $this->arrayType = Type::getType(self::MAUTIC_ARRAY_TYPE_NAME);

        $this->platform = new MySqlPlatform();
    }

    public function testGiven_simpleArray_when_convertsToDatabaseValue_then_getEncodedData()
    {
        $stringWithUtf8Characters = '--ěš--';
        $result                   = $this->arrayType->convertToDatabaseValue([$stringWithUtf8Characters], $this->platform);
        $this->assertEquals('a:1:{i:0;s:8:"--ěš--";}', $result);
    }

    public function testGiven_nullPoisonedString_when_convertsToDatabaseValue_then_error()
    {
        $this->setExpectedException('Doctrine\DBAL\Types\ConversionException');

        $this->arrayType->convertToDatabaseValue(["abcd\0efgh"], $this->platform);
    }

    public function testGiven_objectWithPrivateProperty_when_convertsToDatabaseValue_then_error()
    {
        $this->setExpectedException('Doctrine\DBAL\Types\ConversionException');

        $this->arrayType->convertToDatabaseValue([new ExampleClassWithPrivateProperty()], $this->platform);
    }

    public function testGiven_objectWithProtectedProperty_when_convertsToDatabaseValue_then_error()
    {
        $this->setExpectedException('Doctrine\DBAL\Types\ConversionException');

        $this->arrayType->convertToDatabaseValue([new ExampleClassWithProtectedProperty()], $this->platform);
    }

    public function testGiven_objectWithPublicProperty_when_convertsToDatabaseValue_then_getEncodedData()
    {
        $result = $this->arrayType->convertToDatabaseValue([new ExampleClassWithPublicProperty()], $this->platform);
        $this->assertEquals(
            'a:1:{i:0;O:63:"Mautic\CoreBundle\Tests\Doctrine\ExampleClassWithPublicProperty":1:{s:4:"test";s:5:"value";}}',
            $result
        );
    }

    public function testGiven_stdClass_when_convertsToDatabaseValue_then_getEncodedData()
    {
        $object       = new \stdClass();
        $object->test = 'value';

        $result = $this->arrayType->convertToDatabaseValue([$object], $this->platform);
        $this->assertEquals(
            'a:1:{i:0;O:8:"stdClass":1:{s:4:"test";s:5:"value";}}',
            $result
        );
    }
}
