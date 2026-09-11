<?php

declare(strict_types=1);

namespace Utils\PHPStan\Collector;

use PhpParser\Node;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

/**
 * Collects every string that looks like a service id, e.g. 'mautic.some.helper' in config.php "arguments",
 * in service('mautic.some.helper') calls or in a plain $container->get('mautic.some.helper').
 *
 * The sprintf() formats of ids built at runtime are collected too, e.g. 'mautic.%s.model.%s' of ModelFactory.
 *
 * @implements Collector<String_, array{string, int}>
 */
final class ServiceNameUsageCollector implements Collector
{
    /**
     * Service ids are lowercase and dot separated, e.g. "mautic.lead.model.lead",
     * a "%s" placeholder stands for the part filled in at runtime.
     *
     * @var string
     */
    private const SERVICE_ID_PATTERN = '#^[a-z][a-z0-9_%]*(\.[a-z0-9_%]+)+$#';

    public function getNodeType(): string
    {
        return String_::class;
    }

    /**
     * @return array{string, int}|null the service id with the line it is used on
     */
    public function processNode(Node $node, Scope $scope): ?array
    {
        if (1 !== preg_match(self::SERVICE_ID_PATTERN, $node->value)) {
            return null;
        }

        return [$node->value, $node->getStartLine()];
    }
}
