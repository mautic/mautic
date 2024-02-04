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
        $this->passThrough($platform, AbstractPlatform::class, 'createSelectSQLBuilder');

        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')
          ->willReturn($platform);

        return $connection;
    }

    private function passThrough(MockObject $object, string $class, string $method, InvocationOrder $invocationRule = null): void
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
