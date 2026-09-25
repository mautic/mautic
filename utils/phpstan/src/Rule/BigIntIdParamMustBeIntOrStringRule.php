<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use Doctrine\ORM\EntityRepository;
use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\UnionType;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Repository "$id" param must be "int|string" when its entity id is an unsigned bigint via "addBigIntIdField()",
 * as Doctrine hydrates bigint as string.
 *
 * @implements Rule<ClassMethod>
 */
final readonly class BigIntIdParamMustBeIntOrStringRule implements Rule
{
    private const string ID_PARAM_NAME = 'id';

    public function __construct(
        private ReflectionProvider $reflectionProvider,
    ) {
    }

    public function getNodeType(): string
    {
        return ClassMethod::class;
    }

    /**
     * @param ClassMethod $node
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($scope->isInTrait() || !$scope->isInClass()) {
            return [];
        }

        $ruleErrors = [];

        foreach ($node->params as $param) {
            if (!$param->var instanceof Node\Expr\Variable || self::ID_PARAM_NAME !== $param->var->name) {
                continue;
            }

            // untyped params are left to type coverage
            if (null === $param->type || $this->isIntOrString($param->type)) {
                continue;
            }

            if (!$this->isBigIntIdEntityRepository($scope)) {
                return [];
            }

            $ruleErrors[] = RuleErrorBuilder::message(sprintf(
                'Param "$id" of "%s()" must be "int|string", as the entity id is unsigned bigint hydrated as string.',
                $node->name->toString()
            ))
                ->identifier('mautic.bigIntIdParamMustBeIntOrString')
                ->line($param->getStartLine())
                ->build();
        }

        return $ruleErrors;
    }

    private function isIntOrString(Node $type): bool
    {
        if (!$type instanceof UnionType) {
            return false;
        }

        $typeNames = [];
        foreach ($type->types as $subType) {
            if (!$subType instanceof Identifier) {
                return false;
            }

            $typeNames[] = $subType->toLowerString();
        }

        return [] === array_diff(['int', 'string'], $typeNames) && [] === array_diff($typeNames, ['int', 'string', 'null']);
    }

    private function isBigIntIdEntityRepository(Scope $scope): bool
    {
        $entityRepositoryReflection = $scope->getClassReflection()->getAncestorWithClassName(EntityRepository::class);
        if (null === $entityRepositoryReflection) {
            return false;
        }

        $entityType = $entityRepositoryReflection->getActiveTemplateTypeMap()->getType('T');
        if (null === $entityType) {
            return false;
        }

        foreach ($entityType->getObjectClassNames() as $entityClassName) {
            if (!$this->reflectionProvider->hasClass($entityClassName)) {
                continue;
            }

            $fileName = $this->reflectionProvider->getClass($entityClassName)->getFileName();
            if (null === $fileName) {
                continue;
            }

            // no-arg call maps the primary "id" field
            if (1 === preg_match('#->addBigIntIdField\(\s*\)#', (string) file_get_contents($fileName))) {
                return true;
            }
        }

        return false;
    }
}
