<?php

declare(strict_types=1);

namespace Utils\PHPStan\Collector;

use PhpParser\Node;
use PhpParser\Node\InterpolatedStringPart;
use PhpParser\Node\Scalar;
use PhpParser\Node\Scalar\InterpolatedString;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

/**
 * Collects every string that looks like a service id, e.g. 'mautic.some.helper' in config.php "arguments",
 * in service('mautic.some.helper') calls or in a plain $container->get('mautic.some.helper').
 *
 * The formats of ids built at runtime are collected too, both the sprintf() one, e.g. 'mautic.%s.model.%s'
 * of ModelFactory, and the interpolated one, e.g. "mautic.integration.{$name}" of IntegrationHelper.
 *
 * @implements Collector<\PhpParser\Node\Scalar, array{string, int}>
 */
final class ServiceNameUsageCollector implements Collector
{
    /**
     * Service ids are dot separated and mostly lowercase, yet a bundle named in camel case brings a camel case
     * segment along, e.g. "mautic.dynamicContent.model.dynamicContent". A "%s" placeholder stands for the part
     * filled in at runtime.
     *
     * @var string
     */
    private const SERVICE_ID_PATTERN = '#^[a-zA-Z][a-zA-Z0-9_%]*(\.[a-zA-Z0-9_%]+)+$#';

    public function getNodeType(): string
    {
        return Scalar::class;
    }

    /**
     * @return array{string, int}|null the service id with the line it is used on
     */
    public function processNode(Node $node, Scope $scope): ?array
    {
        $serviceId = $this->matchServiceId($node);
        if (null === $serviceId) {
            return null;
        }

        if (1 !== preg_match(self::SERVICE_ID_PATTERN, $serviceId)) {
            return null;
        }

        return [$serviceId, $node->getStartLine()];
    }

    /**
     * An interpolated string turns into the very format the id is built by, e.g. "mautic.integration.{$name}"
     * turns into "mautic.integration.%s".
     *
     * A plain string is taken as is. The sprintf() format of ModelFactory is skipped on purpose: it would
     * mark every "mautic.*.model.*" alias used, while ServiceModelKeyUsageCollector resolves the very
     * model keys asked for.
     */
    private function matchServiceId(Node $node): ?string
    {
        if ($node instanceof String_) {
            return str_contains($node->value, '%s') ? null : $node->value;
        }

        if (!$node instanceof InterpolatedString) {
            return null;
        }

        $serviceId = '';
        foreach ($node->parts as $part) {
            $serviceId .= $part instanceof InterpolatedStringPart ? $part->value : '%s';
        }

        return $serviceId;
    }
}
