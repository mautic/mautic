<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
