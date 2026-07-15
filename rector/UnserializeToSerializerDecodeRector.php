<?php

declare(strict_types=1);

namespace MauticRector;

use Mautic\CoreBundle\Helper\Serializer;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Replaces raw unserialize() calls with the secure Serializer::decode() helper,
 * which enforces allowed_classes => false and throws on object-injection payloads.
 *
 * Cases handled:
 *   unserialize($x)                               -> Serializer::decode($x)
 *   unserialize($x, ['allowed_classes' => false]) -> Serializer::decode($x)
 *
 * Cases intentionally skipped (non-default allowed_classes):
 *   unserialize($x, ['allowed_classes' => [SomeClass::class]])
 */
final class UnserializeToSerializerDecodeRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Replace unserialize() with Serializer::decode() to prevent PHP Object Injection',
            [
                new CodeSample(
                    'unserialize($data);',
                    '\\Mautic\\CoreBundle\\Helper\\Serializer::decode($data);',
                ),
                new CodeSample(
                    "unserialize(\$data, ['allowed_classes' => false]);",
                    '\\Mautic\\CoreBundle\\Helper\\Serializer::decode($data);',
                ),
            ]
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [FuncCall::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (!$node instanceof FuncCall) {
            return null;
        }

        if (!$this->isName($node, 'unserialize')) {
            return null;
        }

        $args = $node->args;

        if (0 === count($args)) {
            return null;
        }

        // If a second argument is present, only replace when it is ['allowed_classes' => false].
        // Any other value (e.g. a class whitelist) must be handled manually.
        if (isset($args[1])) {
            if (!$args[1] instanceof Arg) {
                return null;
            }

            if (!$this->isAllowedClassesFalse($args[1])) {
                return null;
            }
        }

        if (!$args[0] instanceof Arg) {
            return null;
        }

        return new StaticCall(
            new FullyQualified(Serializer::class),
            'decode',
            [$args[0]]
        );
    }

    /**
     * Returns true when $arg is exactly ['allowed_classes' => false].
     */
    private function isAllowedClassesFalse(Arg $arg): bool
    {
        $value = $arg->value;

        if (!$value instanceof Array_) {
            return false;
        }

        if (1 !== count($value->items)) {
            return false;
        }

        $item = $value->items[0];

        if (!$item instanceof ArrayItem) {
            return false;
        }

        if (!$item->key instanceof String_ || 'allowed_classes' !== $item->key->value) {
            return false;
        }

        return $item->value instanceof ConstFetch && $this->isName($item->value, 'false');
    }
}
