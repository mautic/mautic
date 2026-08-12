<?php

namespace Utils\Rector\Tests\AddExpectsOnceToMockMethodCallRector\Fixture;

use PHPUnit\Framework\TestCase;

class SomeReflectionLikeClass
{
    public function method(string $name)
    {
    }
}

class SkipNonMockObject extends TestCase
{
    public function testPayload()
    {
        $someObject = new SomeReflectionLikeClass();
        $someObject->method('getPayload');
    }
}
