<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Reports the setter injection wired by hand in Config/services.php, e.g.:
 *
 *     $services->get(LeadRepository::class)
 *         ->call('setUniqueIdentifiersOperator', ['%mautic.contact_unique_identifiers_operator%'])
 *         ->call('setListLeadRepository', [service(ListLeadRepository::class)]);
 *
 * A #[Required] attribute on the setter says the same next to the method it feeds, and lets autowiring call
 * it, so the config no longer names the method by a loose string:
 *
 *     #[Required]
 *     public function setListLeadRepository(ListLeadRepository $listLeadRepository): void
 *     {
 *         $this->listLeadRepository = $listLeadRepository;
 *     }
 *
 * Only a call() naming a setXxx() method a service() feeds is reported. A call() to another method, e.g.
 * ->call('configure', ...), runs logic the container cannot infer, and a setter fed a container parameter,
 * e.g. ->call('setDefaultTheme', [param('mautic.theme')]), has no type to autowire, so both stay a manual call.
 *
 * @implements Rule<MethodCall>
 */
final class NoServiceSetterCallRule implements Rule
{
    /**
     * @var string
     */
    private const SERVICES_FILE_NAME = 'services.php';

    /**
     * A setter is a setXxx() method, e.g. setListLeadRepository(). A "setup" or "settle" method is no setter.
     *
     * @var string
     */
    private const SETTER_METHOD_PATTERN = '#^set\p{Lu}#u';

    /**
     * @var string
     */
    private const SERVICE_FUNCTION = 'Symfony\Component\DependencyInjection\Loader\Configurator\service';

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @param MethodCall $node
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (self::SERVICES_FILE_NAME !== basename($scope->getFile())) {
            return [];
        }

        if (!$node->name instanceof Identifier || 'call' !== $node->name->toString()) {
            return [];
        }

        $firstArg = $node->getArgs()[0] ?? null;
        if (null === $firstArg || !$firstArg->value instanceof String_) {
            return [];
        }

        $methodName = $firstArg->value->value;
        if (1 !== preg_match(self::SETTER_METHOD_PATTERN, $methodName)) {
            return [];
        }

        // only a setter fed a service() can move to #[Required]; a container parameter, e.g. '%mautic.theme%',
        // is not resolved by type, so its setter call stays
        if (!$this->hasServiceArgument($node)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                'Setter call() to "%s()" wires the dependency by hand, mark the method #[Required] and let autowiring call it instead.',
                $methodName
            ))
                ->identifier('mautic.noServiceSetterCall')
                ->line($node->getStartLine())
                ->build(),
        ];
    }

    /**
     * The arguments of a call() travel in an array, e.g. ->call('setFieldModel', [service(FieldModel::class)]).
     * A service() reference among them is the dependency #[Required] autowiring resolves by type.
     */
    private function hasServiceArgument(MethodCall $node): bool
    {
        $secondArg = $node->getArgs()[1] ?? null;
        if (null === $secondArg || !$secondArg->value instanceof Array_) {
            return false;
        }

        foreach ($secondArg->value->items as $item) {
            if ($item->value instanceof FuncCall && $this->isServiceFunction($item->value)) {
                return true;
            }
        }

        return false;
    }

    private function isServiceFunction(FuncCall $funcCall): bool
    {
        if (!$funcCall->name instanceof Name) {
            return false;
        }

        $functionName = $funcCall->name->toString();

        return 'service' === $functionName || self::SERVICE_FUNCTION === $functionName;
    }
}
