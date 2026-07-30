<?php

declare(strict_types=1);

namespace Utils\PHPStan\Collector;

use PhpParser\Node;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

/**
 * Collects the model container ids the model keys of the codebase stand for, e.g. 'lead.list' asks for
 * "mautic.lead.model.list" and the 'campaign' shortcut asks for "mautic.campaign.model.campaign".
 *
 * A model key rarely reaches getModel() as a literal. It travels as data instead - a getModelName() return,
 * a "model" form option or an ajax request parameter - so every string shaped like a model key counts.
 *
 * @see \Mautic\CoreBundle\Factory\ModelFactory::getModel()
 *
 * @implements Collector<String_, array{string, int}>
 */
final class ServiceModelKeyUsageCollector implements Collector
{
    /**
     * A model key is either a "bundle.name" pair or the shortcut of a model named after its very bundle,
     * e.g. "lead.list" or "campaign".
     *
     * @var string
     */
    private const MODEL_KEY_PATTERN = '#^[a-zA-Z][a-zA-Z0-9_]*(\.[a-zA-Z0-9_]+)?$#';

    public function getNodeType(): string
    {
        return String_::class;
    }

    /**
     * @return array{string, int}|null the model container id with the line it is asked for on
     */
    public function processNode(Node $node, Scope $scope): ?array
    {
        if (1 !== preg_match(self::MODEL_KEY_PATTERN, $node->value)) {
            return null;
        }

        $modelKey = str_contains($node->value, '.') ? $node->value : $node->value.'.'.$node->value;

        [$bundle, $name] = explode('.', $modelKey);

        return [sprintf('mautic.%s.model.%s', $bundle, $name), $node->getStartLine()];
    }
}
