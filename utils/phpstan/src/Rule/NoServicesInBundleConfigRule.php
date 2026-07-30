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
 * The "menus" group is left alone - a menu is no service of its own, ServicePass builds it out of the KnpMenu builder
 * and gives it a renderer of its own, see Mautic\CoreBundle\DependencyInjection\Compiler\ServicePass.
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

    /**
     * @var string
     */
    private const MENUS_KEY_NAME = 'menus';

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

            if ($this->hasMenusOnly($arrayItem->value)) {
                return [];
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

    /**
     * A "services" key made of the "menus" group alone defines no service at all.
     */
    private function hasMenusOnly(Node $servicesValue): bool
    {
        if (!$servicesValue instanceof Array_ || [] === $servicesValue->items) {
            return false;
        }

        foreach ($servicesValue->items as $groupArrayItem) {
            if (!$groupArrayItem->key instanceof String_ || self::MENUS_KEY_NAME !== $groupArrayItem->key->value) {
                return false;
            }
        }

        return true;
    }
}
