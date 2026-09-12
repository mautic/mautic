<?php

namespace Mautic\CoreBundle\Test\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\AnyInvokedCount;
use PHPUnit\Framework\MockObject\Rule\InvocationOrder;

trait MockedConnectionTrait
{
    public function getMockedConnection(): mixed
    {
        $platform = $this->createMock(AbstractPlatform::class);
        // DBAL builds a SELECT through the platform's SQL builder, so the mock has to hand
        // back the real one or every query it is asked to build comes out empty.
        $this->passThrough($platform, AbstractPlatform::class, 'createSelectSQLBuilder');

        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')
          ->willReturn($platform);

        return $connection;
    }

    private function passThrough(MockObject $object, string $class, string $method, ?InvocationOrder $invocationRule = null): void
    {
        if (!$invocationRule) {
            $invocationRule = new AnyInvokedCount();
        }

        $object
          ->expects($invocationRule)
          ->method($method)
          ->willReturnCallback(function (...$parameters) use ($object, $class, $method) {
              $reflectionMethod = new \ReflectionMethod($class, $method);

              return $reflectionMethod->invoke($object, ...$parameters);
          });
    }
}
