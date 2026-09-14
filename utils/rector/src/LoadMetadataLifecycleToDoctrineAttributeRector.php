<?php

declare(strict_types=1);

namespace Utils\Rector;

use PhpParser\Node;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;

/**
 * Converts loadMetadata() $builder->addLifecycleEvent('onSave', 'preUpdate') calls into lifecycle
 * callback attributes (#[ORM\PreUpdate], ...) on their target methods, plus the class-level
 * #[ORM\HasLifecycleCallbacks] marker.
 */
final class LoadMetadataLifecycleToDoctrineAttributeRector extends AbstractLoadMetadataRector
{
    public function refactor(Node $node): ?Node
    {
        if (!$node instanceof Class_) {
            return null;
        }

        $loadMetadata = $this->getLoadMetadata($node);
        if (null === $loadMetadata) {
            return null;
        }

        $this->initHybridState($node);

        $owned = [];
        /** @var array<string, list<AttributeGroup>> $methodAttributes */
        $methodAttributes = [];
        foreach ((array) $loadMetadata->stmts as $stmt) {
            if (!$stmt instanceof Expression || !$stmt->expr instanceof MethodCall) {
                continue;
            }

            foreach ($this->flattenChain($stmt->expr) ?? [] as $call) {
                if ('addLifecycleEvent' !== $this->methodName($call)) {
                    continue;
                }

                $methodName = $this->stringArg($call, 0);
                $event      = $this->lifecycleEventArg($call, 1);
                if (null === $methodName || null === $event) {
                    continue;
                }

                $eventShortName = $this->lifecycleEventShortName($event);
                if (null === $eventShortName || !$node->getMethod($methodName) instanceof ClassMethod) {
                    continue;
                }

                $methodAttributes[$methodName][] = $this->attribute($eventShortName, []);
                $owned[]                         = $call;
            }
        }

        if ([] === $owned) {
            return null;
        }

        foreach ($methodAttributes as $methodName => $attributeGroups) {
            $method = $node->getMethod($methodName);
            if ($method instanceof ClassMethod) {
                $method->attrGroups = array_merge($method->attrGroups, $attributeGroups);
            }
        }

        $this->ensureEntityScaffolding($node, $loadMetadata);
        if (!$this->hasAttributeNamed($node->attrGroups, 'HasLifecycleCallbacks')) {
            $this->insertClassAttribute($node, $this->attribute('HasLifecycleCallbacks', []));
        }

        $this->removeOwnedCalls($loadMetadata, $owned);
        $this->removeLoadMetadataIfEmpty($node, $loadMetadata);

        return $node;
    }
}
