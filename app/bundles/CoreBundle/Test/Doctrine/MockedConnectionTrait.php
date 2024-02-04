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
<<<<<<< HEAD
        // Following line is needed once we update to doctrine/dbal >= 3.8.0.
        // This allows easy mocking of the createSelectSQLBuilder method without needing to mock the whole chain.
        // $this->passThrough($platform, AbstractPlatform::class, 'createSelectSQLBuilder');
=======
        $this->passThrough($platform, AbstractPlatform::class, 'createSelectSQLBuilder');
>>>>>>> 81b90388fa (Adapt tests to be compatible to the changes in the QueryBuilder:: getSQLForSelect() method)

        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')
          ->willReturn($platform);

        return $connection;
    }

<<<<<<< HEAD
    private function passThrough(MockObject $object, string $class, string $method, ?InvocationOrder $invocationRule = null): void
=======
    private function passThrough(MockObject $object, string $class, string $method, InvocationOrder $invocationRule = null): void
>>>>>>> 81b90388fa (Adapt tests to be compatible to the changes in the QueryBuilder:: getSQLForSelect() method)
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
