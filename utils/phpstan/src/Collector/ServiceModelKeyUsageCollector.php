<?php

declare(strict_types=1);

namespace Utils\PHPStan\Collector;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

/**
 * Collects the model container ids the model keys of the codebase stand for, e.g. 'lead.list' asks for
 * "mautic.lead.model.list" and the 'campaign' shortcut asks for "mautic.campaign.model.campaign".
 *
 * A model key rarely reaches getModel() as a literal, it travels as data instead, so the places a model key
 * is written down count as well: a getModelName() return and a "model" option of a lookup form type.
 *
 * The position is what makes a model key, never the shape alone - "campaign.event" is a mail stat source
 * as much as it looks like a model key of the campaign bundle.
 *
 * @see \Mautic\CoreBundle\Factory\ModelFactory::getModel()
 *
 * @implements Collector<Expr, list<array{string, int}>>
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

    /**
     * @var list<string>
     */
    private const MODEL_FACTORY_METHOD_NAMES = ['getModel', 'hasModel'];

    /**
     * @var list<string>
     */
    private const MODEL_NAME_METHOD_NAMES = ['getModelName'];

    /**
     * @var string
     */
    private const MODEL_OPTION_NAME = 'model';

    public function getNodeType(): string
    {
        return Expr::class;
    }

    /**
     * @return list<array{string, int}>|null the model container ids with the line they are asked for on
     */
    public function processNode(Node $node, Scope $scope): ?array
    {
        $modelKeyNodes = $this->matchModelKeyNodes($node, $scope);

        $modelUsages = [];
        foreach ($modelKeyNodes as $modelKeyNode) {
            $modelId = $this->matchModelId($modelKeyNode->value);
            if (null === $modelId) {
                continue;
            }

            $modelUsages[] = [$modelId, $modelKeyNode->getStartLine()];
        }

        return [] === $modelUsages ? null : $modelUsages;
    }

    /**
     * A model key is written down as the argument of a model factory call, as the "model" option of a lookup
     * form type or as the getModelName() return of a controller.
     *
     * @return list<String_>
     */
    private function matchModelKeyNodes(Node $node, Scope $scope): array
    {
        if ($node instanceof String_) {
            return in_array($scope->getFunctionName(), self::MODEL_NAME_METHOD_NAMES, true) ? [$node] : [];
        }

        if ($node instanceof MethodCall) {
            if (!$node->name instanceof Identifier || !in_array($node->name->toString(), self::MODEL_FACTORY_METHOD_NAMES, true)) {
                return [];
            }

            $firstArg = $node->getArgs()[0] ?? null;

            return $firstArg instanceof Node\Arg && $firstArg->value instanceof String_ ? [$firstArg->value] : [];
        }

        if (!$node instanceof Array_) {
            return [];
        }

        $modelKeyNodes = [];
        foreach ($node->items as $arrayItem) {
            if (!$arrayItem->key instanceof String_ || self::MODEL_OPTION_NAME !== $arrayItem->key->value) {
                continue;
            }

            if ($arrayItem->value instanceof String_) {
                $modelKeyNodes[] = $arrayItem->value;
            }
        }

        return $modelKeyNodes;
    }

    private function matchModelId(string $modelKey): ?string
    {
        if (1 !== preg_match(self::MODEL_KEY_PATTERN, $modelKey)) {
            return null;
        }

        if (!str_contains($modelKey, '.')) {
            $modelKey = $modelKey.'.'.$modelKey;
        }

        [$bundle, $name] = explode('.', $modelKey);

        return sprintf('mautic.%s.model.%s', $bundle, $name);
    }
}
