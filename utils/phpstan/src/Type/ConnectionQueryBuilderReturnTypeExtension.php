<?php

declare(strict_types=1);

namespace Utils\PHPStan\Type;

use Doctrine\DBAL\Connection;
use Mautic\CoreBundle\Doctrine\Query\QueryBuilder;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

/**
 * Connection::createQueryBuilder() returns Mautic's query builder, not DBAL's.
 *
 * Every connection Mautic opens is one of the wrappers named by 'wrapper_class' in
 * app/config/config.php, and both of them return {@see QueryBuilder}, which records its
 * parts so a query can be read back after it is built. DBAL declares the base class, so
 * without this each caller that reads a query back has to restate the type for itself.
 */
final class ConnectionQueryBuilderReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return Connection::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return 'createQueryBuilder' === $methodReflection->getName();
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
    {
        return new ObjectType(QueryBuilder::class);
    }
}
