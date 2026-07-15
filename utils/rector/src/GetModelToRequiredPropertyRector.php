<?php

declare(strict_types=1);

namespace Utils\Rector;

use PhpParser\Modifiers;
use PhpParser\Node;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Param;
use PhpParser\Node\PropertyItem;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Property;
use PhpParser\NodeVisitor;
use PHPStan\Type\ObjectType;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Rector\Reflection\ReflectionResolver;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Turns a ModelFactory lookup by string alias into a typed, autowired property.
 *
 * Both spellings of the lookup are handled, as both end up in ModelFactory::getModel():
 *   $model = $this->getModel('sms');            // CommonController::getModel() delegate
 *   $model = $modelFactory->getModel('sms');    // ModelFactory directly
 *
 * The dependency is added through a #[Required] setter rather than the constructor: these controllers
 * inherit wide constructors, and overriding one would mean copying every parent param just to forward
 * it to parent::__construct().
 */
final class GetModelToRequiredPropertyRector extends AbstractRector implements ConfigurableRectorInterface
{
    /**
     * @var string
     */
    private const REQUIRED_ATTRIBUTE = 'Symfony\Contracts\Service\Attribute\Required';

    /**
     * A class can need more than one model, so they all share a single autowire method.
     *
     * @var string
     */
    private const AUTOWIRE_METHOD = 'autowire';

    /**
     * @var array<string, class-string>
     */
    private array $aliasToModelClass = [];

    public function __construct(
        private readonly ReflectionResolver $reflectionResolver,
    ) {
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Replace ModelFactory getModel() lookup by string alias with a typed autowired property',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
class SmsController extends FormController
{
    public function indexAction(): Response
    {
        /** @var SmsModel $model */
        $model = $this->getModel('sms');

        return $this->render($model->getEntity(1));
    }
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
class SmsController extends FormController
{
    private \Mautic\SmsBundle\Model\SmsModel $smsModel;

    #[\Symfony\Contracts\Service\Attribute\Required]
    public function autowire(\Mautic\SmsBundle\Model\SmsModel $smsModel): void
    {
        $this->smsModel = $smsModel;
    }

    public function indexAction(): Response
    {
        return $this->render($this->smsModel->getEntity(1));
    }
}
CODE_SAMPLE,
                    [
                        'sms' => 'Mautic\SmsBundle\Model\SmsModel',
                    ]
                ),
            ]
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    /**
     * @param array<string, class-string> $configuration
     */
    public function configure(array $configuration): void
    {
        $aliasToModelClass = [];

        foreach ($configuration as $alias => $modelClass) {
            if (!is_string($alias) || !is_string($modelClass)) {
                throw new \InvalidArgumentException(sprintf('%s configuration must be an array of "alias" => Model::class pairs.', self::class));
            }

            $aliasToModelClass[$alias] = $modelClass;

            // ModelFactory doubles a dot-less alias, so 'sms' and 'sms.sms' are the same model
            if (!str_contains($alias, '.')) {
                $aliasToModelClass[$alias.'.'.$alias] = $modelClass;
            }

            // getModel() also accepts the model FQCN itself
            $aliasToModelClass[$modelClass] = $modelClass;
        }

        $this->aliasToModelClass = $aliasToModelClass;
    }

    public function refactor(Node $node): ?Node
    {
        if (!$node instanceof Class_) {
            return null;
        }

        if ($node->isAbstract() || $node->isAnonymous()) {
            return null;
        }

        /** @var array<string, class-string> $propertyNameToModelClass */
        $propertyNameToModelClass = [];
        $hasChanged               = false;

        foreach ($node->getMethods() as $classMethod) {
            // a constructor already there can take the model as a param, keeping the original assign order
            if ($this->isName($classMethod, '__construct')) {
                $hasChanged = $this->refactorConstructor($classMethod) || $hasChanged;
                continue;
            }

            foreach ($this->refactorClassMethod($node, $classMethod) as $propertyName => $modelClass) {
                $propertyNameToModelClass[$propertyName] = $modelClass;
            }
        }

        foreach ($propertyNameToModelClass as $propertyName => $modelClass) {
            $this->addAutowiredProperty($node, $propertyName, $modelClass);
            $hasChanged = true;
        }

        return $hasChanged ? $node : null;
    }

    /**
     * The lookup result is already assigned somewhere in the constructor body, so swapping it for a param of
     * the same name keeps every existing read working, and keeps it happening before parent::__construct().
     */
    private function refactorConstructor(ClassMethod $constructClassMethod): bool
    {
        if (null === $constructClassMethod->stmts) {
            return false;
        }

        $hasChanged = false;

        foreach ($this->resolveStmtsHolders($constructClassMethod) as $stmtsHolder) {
            foreach ($stmtsHolder->stmts as $key => $stmt) {
                $modelAssign = $this->matchModelAssign($stmt);
                if (null === $modelAssign) {
                    continue;
                }

                [$variableName, $modelClass] = $modelAssign;

                // the variable turns into a param, so nothing else may write to it
                if (!$this->isCollapsibleVariable($constructClassMethod, $variableName)) {
                    continue;
                }

                unset($stmtsHolder->stmts[$key]);
                $this->removeInstanceOfAsserts($stmtsHolder, $variableName, $modelClass);
                $stmtsHolder->stmts = array_values($stmtsHolder->stmts);

                $this->addParam($constructClassMethod, new Param(new Variable($variableName), null, new FullyQualified($modelClass)));

                $hasChanged = true;

                break;
            }
        }

        return $hasChanged;
    }

    /**
     * A required param may not sit behind an optional or variadic one.
     */
    private function addParam(ClassMethod $classMethod, Param $param): void
    {
        foreach ($classMethod->params as $key => $existingParam) {
            if (null !== $existingParam->default || $existingParam->variadic) {
                array_splice($classMethod->params, $key, 0, [$param]);

                return;
            }
        }

        $classMethod->params[] = $param;
    }

    /**
     * @return array<string, class-string> property name to model class
     */
    private function refactorClassMethod(Class_ $class, ClassMethod $classMethod): array
    {
        if (null === $classMethod->stmts) {
            return [];
        }

        $propertyNameToModelClass = [];

        // the lookup is not always a top level statement, it also shows up inside if/foreach bodies
        foreach ($this->resolveStmtsHolders($classMethod) as $stmtsHolder) {
            foreach ($stmtsHolder->stmts as $key => $stmt) {
                $modelAssign = $this->matchModelAssign($stmt);
                if (null === $modelAssign) {
                    continue;
                }

                [$variableName, $modelClass] = $modelAssign;

                if (!$this->isCollapsibleVariable($classMethod, $variableName)) {
                    continue;
                }

                $propertyName = lcfirst((string) substr((string) strrchr('\\'.$modelClass, '\\'), 1));

                // a name already taken by something else is not safe to reuse
                if (!$this->isFreeToAdd($class, $propertyName)) {
                    continue;
                }

                unset($stmtsHolder->stmts[$key]);
                $this->removeInstanceOfAsserts($stmtsHolder, $variableName, $modelClass);
                $stmtsHolder->stmts = array_values($stmtsHolder->stmts);

                $this->replaceVariableWithPropertyFetch($classMethod, $variableName, $propertyName);

                $propertyNameToModelClass[$propertyName] = $modelClass;

                break;
            }
        }

        return $propertyNameToModelClass;
    }

    /**
     * The property and its setter are both new API on the class, so neither name may be in use already,
     * including anything inherited.
     */
    private function isFreeToAdd(Class_ $class, string $propertyName): bool
    {
        if ($class->getProperty($propertyName) instanceof Property) {
            return false;
        }

        $classReflection = $this->reflectionResolver->resolveClassReflection($class);
        if (null === $classReflection) {
            return true;
        }

        // the property name must be free on the whole hierarchy, or the assign would hit someone else's property
        if ($classReflection->hasProperty($propertyName)) {
            return false;
        }

        // an inherited autowire method would be silently overridden by the one added here
        return !$classReflection->hasMethod(self::AUTOWIRE_METHOD)
            || $class->getMethod(self::AUTOWIRE_METHOD) instanceof ClassMethod;
    }

    private function addAutowiredProperty(Class_ $class, string $propertyName, string $modelClass): void
    {
        $property = new Property(
            Modifiers::PRIVATE,
            [new PropertyItem($propertyName)],
            [],
            new FullyQualified($modelClass)
        );

        $param  = new Param(new Variable($propertyName), null, new FullyQualified($modelClass));
        $assign = new Expression(new Assign(
            new PropertyFetch(new Variable('this'), new Identifier($propertyName)),
            new Variable($propertyName)
        ));

        $autowireClassMethod = $class->getMethod(self::AUTOWIRE_METHOD);

        // a second model on the same class joins the autowire method already there
        if ($autowireClassMethod instanceof ClassMethod) {
            $autowireClassMethod->params[] = $param;
            $autowireClassMethod->stmts[]  = $assign;

            $this->insertAfterTraitUses($class, [$property]);

            return;
        }

        $autowireClassMethod = new ClassMethod(new Identifier(self::AUTOWIRE_METHOD), [
            'flags'      => Modifiers::PUBLIC,
            'params'     => [$param],
            'returnType' => new Identifier('void'),
            'stmts'      => [$assign],
            'attrGroups' => [
                new AttributeGroup([new Node\Attribute(new FullyQualified(self::REQUIRED_ATTRIBUTE))]),
            ],
        ]);

        $this->insertAfterTraitUses($class, [$property, $autowireClassMethod]);
    }

    /**
     * @param Node\Stmt[] $stmts
     */
    private function insertAfterTraitUses(Class_ $class, array $stmts): void
    {
        $offset = 0;

        foreach ($class->stmts as $key => $stmt) {
            if (!$stmt instanceof Node\Stmt\TraitUse) {
                break;
            }

            $offset = $key + 1;
        }

        array_splice($class->stmts, $offset, 0, $stmts);
    }

    /**
     * Collects every node holding a statement list, so nested lookups are found too. Closures are left
     * alone, as a variable there belongs to another scope.
     *
     * @return array<ClassMethod|Node\Stmt\If_|Node\Stmt\Else_|Node\Stmt\ElseIf_|Node\Stmt\Foreach_|Node\Stmt\While_|Node\Stmt\Do_|Node\Stmt\For_|Node\Stmt\TryCatch|Node\Stmt\Catch_|Node\Stmt\Finally_|Node\Stmt\Case_>
     */
    private function resolveStmtsHolders(ClassMethod $classMethod): array
    {
        $stmtsHolders = [$classMethod];

        $this->traverseNodesWithCallable((array) $classMethod->stmts, static function (Node $node) use (&$stmtsHolders) {
            if ($node instanceof Node\FunctionLike) {
                return NodeVisitor::DONT_TRAVERSE_CHILDREN;
            }

            if (property_exists($node, 'stmts') && is_array($node->stmts)) {
                $stmtsHolders[] = $node;
            }

            return null;
        });

        return $stmtsHolders;
    }

    /**
     * Matches `$model = $this->getModel('sms');` / `$model = $modelFactory->getModel('sms');`.
     *
     * @return array{string, class-string}|null the assigned variable name and the resolved model class
     */
    private function matchModelAssign(Node $stmt): ?array
    {
        if (!$stmt instanceof Expression) {
            return null;
        }

        if (!$stmt->expr instanceof Assign) {
            return null;
        }

        $assign = $stmt->expr;

        if (!$assign->var instanceof Variable || !is_string($assign->var->name)) {
            return null;
        }

        $modelClass = $this->matchGetModelCall($assign->expr);
        if (null === $modelClass) {
            return null;
        }

        return [$assign->var->name, $modelClass];
    }

    /**
     * @return class-string|null
     */
    private function matchGetModelCall(Node $expr): ?string
    {
        if (!$expr instanceof MethodCall) {
            return null;
        }

        if (!$this->isName($expr->name, 'getModel')) {
            return null;
        }

        if (1 !== count($expr->getArgs())) {
            return null;
        }

        $firstArg = $expr->getArgs()[0];
        if (!$firstArg->value instanceof String_) {
            return null;
        }

        $modelClass = $this->aliasToModelClass[$firstArg->value->value] ?? null;
        if (null === $modelClass) {
            return null;
        }

        if (!$this->isGetModelCaller($expr->var)) {
            return null;
        }

        return $modelClass;
    }

    /**
     * Only ModelFactory itself and the CommonController delegate expose getModel().
     */
    private function isGetModelCaller(Node $expr): bool
    {
        if ($this->isObjectType($expr, new ObjectType('Mautic\CoreBundle\Factory\ModelFactory'))) {
            return true;
        }

        return $this->isObjectType($expr, new ObjectType('Mautic\CoreBundle\Controller\CommonController'));
    }

    /**
     * The variable may only be swapped for a property when it is written exactly once and never bound by
     * name somewhere a property fetch is not valid syntax, i.e. a param or a closure use().
     */
    private function isCollapsibleVariable(ClassMethod $classMethod, string $variableName): bool
    {
        foreach ($classMethod->params as $param) {
            if ($param->var instanceof Variable && $this->isName($param->var, $variableName)) {
                return false;
            }
        }

        $assignCount = 0;
        $isNameBound = false;

        $this->traverseNodesWithCallable((array) $classMethod->stmts, function (Node $node) use ($variableName, &$assignCount, &$isNameBound): null {
            if ($node instanceof Assign && $node->var instanceof Variable && $this->isName($node->var, $variableName)) {
                ++$assignCount;
            }

            if ($node instanceof Node\ClosureUse && $this->isName($node->var, $variableName)) {
                $isNameBound = true;
            }

            if ($node instanceof Param && $node->var instanceof Variable && $this->isName($node->var, $variableName)) {
                $isNameBound = true;
            }

            return null;
        });

        return 1 === $assignCount && !$isNameBound;
    }

    /**
     * The instanceof assert only existed to type the untyped getModel() return, so it is now noise.
     */
    private function removeInstanceOfAsserts(Node $stmtsHolder, string $variableName, string $modelClass): void
    {
        foreach ((array) $stmtsHolder->stmts as $key => $stmt) {
            if (!$stmt instanceof Expression) {
                continue;
            }

            if (!$stmt->expr instanceof Node\Expr\FuncCall) {
                continue;
            }

            if (!$this->isName($stmt->expr, 'assert')) {
                continue;
            }

            if (1 !== count($stmt->expr->getArgs())) {
                continue;
            }

            $assertedExpr = $stmt->expr->getArgs()[0]->value;
            if (!$assertedExpr instanceof Node\Expr\Instanceof_) {
                continue;
            }

            if (!$assertedExpr->expr instanceof Variable || !$this->isName($assertedExpr->expr, $variableName)) {
                continue;
            }

            if (!$assertedExpr->class instanceof Node\Name) {
                continue;
            }

            if (!$this->isName($assertedExpr->class, $modelClass)) {
                continue;
            }

            unset($stmtsHolder->stmts[$key]);
        }
    }

    private function replaceVariableWithPropertyFetch(ClassMethod $classMethod, string $variableName, string $propertyName): void
    {
        $this->traverseNodesWithCallable((array) $classMethod->stmts, function (Node $node) use ($variableName, $propertyName): ?PropertyFetch {
            if (!$node instanceof Variable) {
                return null;
            }

            if (!$this->isName($node, $variableName)) {
                return null;
            }

            return new PropertyFetch(new Variable('this'), new Identifier($propertyName));
        });
    }
}
