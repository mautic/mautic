<?php

declare(strict_types=1);

namespace Utils\PHPStan\Collector;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

/**
 * Collects the service() references of bundle Config/services.php files that ask for a service by a string id,
 * e.g. service('mautic.helper.core_parameters').
 *
 * A service(SomeHelper::class) reference is left out, that one already names the service by its class.
 *
 * @implements Collector<FuncCall, array{string, int}>
 */
final class ServiceStringReferenceCollector implements Collector
{
    /**
     * @var string
     */
    private const SERVICES_FILE_NAME = 'services.php';

    /**
     * @var string
     */
    private const SERVICE_FUNCTION = 'Symfony\Component\DependencyInjection\Loader\Configurator\service';

    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    /**
     * @return array{string, int}|null the string service id with the line the reference is on
     */
    public function processNode(Node $node, Scope $scope): ?array
    {
        if (self::SERVICES_FILE_NAME !== basename($scope->getFile())) {
            return null;
        }

        if (!$node->name instanceof Name) {
            return null;
        }

        $functionName = $node->name->toString();
        if ('service' !== $functionName && self::SERVICE_FUNCTION !== $functionName) {
            return null;
        }

        $firstArg = $node->getArgs()[0] ?? null;
        if (!$firstArg instanceof Node\Arg || !$firstArg->value instanceof String_) {
            return null;
        }

        return [$firstArg->value->value, $node->getStartLine()];
    }
}
