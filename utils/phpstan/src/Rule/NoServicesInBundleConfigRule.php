<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Bundle Config/config.php must not define services, the autowired Config/services.php next to it is the place for them.
 *
 * @implements Rule<Return_>
 */
final class NoServicesInBundleConfigRule implements Rule
{
    /**
     * @var string
     */
    private const CONFIG_FILE_NAME = 'config.php';

    /**
     * @var string
     */
    private const SERVICES_KEY_NAME = 'services';

    public function getNodeType(): string
    {
        return Return_::class;
    }

    /**
     * @param Return_ $node
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (self::CONFIG_FILE_NAME !== basename($scope->getFile())) {
            return [];
        }

        if (!$node->expr instanceof Array_) {
            return [];
        }

        foreach ($node->expr->items as $arrayItem) {
            if (!$arrayItem->key instanceof String_) {
                continue;
            }

            if (self::SERVICES_KEY_NAME !== $arrayItem->key->value) {
                continue;
            }

            $ruleError = RuleErrorBuilder::message(sprintf(
                'Config file must not define the "%s" key. Register the services in the autowired Config/services.php instead.',
                self::SERVICES_KEY_NAME
            ))
                ->identifier('mautic.noServicesInBundleConfig')
                ->line($arrayItem->getStartLine())
                ->build();

            return [$ruleError];
        }

        return [];
    }
}
