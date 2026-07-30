<?php

declare(strict_types=1);

namespace Utils\PHPStan\Collector;

use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

/**
 * Collects the class names referred to by a "SomeHelper::class" constant, e.g. in $container->get(SomeHelper::class),
 * in $this->loadFixtures([LoadLeadData::class]) or in a Constraint::validatedBy() return.
 *
 * A class name alias is what makes such a fetch work, so any of these makes the alias used.
 *
 * The services.php files are skipped, as the "SomeHelper::class" of a set() or an alias() call registers
 * the service, it does not use it.
 *
 * @implements Collector<ClassConstFetch, string>
 */
final class ServiceClassUsageCollector implements Collector
{
    /**
     * @var string
     */
    private const SERVICES_FILE_NAME = 'services.php';

    public function getNodeType(): string
    {
        return ClassConstFetch::class;
    }

    public function processNode(Node $node, Scope $scope): ?string
    {
        if (self::SERVICES_FILE_NAME === basename($scope->getFile())) {
            return null;
        }

        if (!$node->name instanceof Identifier || 'class' !== $node->name->toLowerString()) {
            return null;
        }

        if (!$node->class instanceof Name) {
            return null;
        }

        return $scope->resolveName($node->class);
    }
}
