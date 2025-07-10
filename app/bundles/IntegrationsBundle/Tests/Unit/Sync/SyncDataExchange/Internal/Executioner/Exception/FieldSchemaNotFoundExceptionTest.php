<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Sync\SyncDataExchange\Internal\Executioner\Exception;

use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Executioner\Exception\FieldSchemaNotFoundException;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class FieldSchemaNotFoundExceptionTest extends TestCase
{
    public function testMessage(): void
    {
        $object    = 'SomeObject';
        $alias     = 'SomeAlias';
        $exception = new FieldSchemaNotFoundException($object, $alias);
        $expected  = sprintf('Schema for alias "%s" of object "%s" not found', $alias, $object);
        Assert::assertSame($expected, $exception->getMessage());
    }
}
