<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
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

    /**
     * @var string
     */
    private const TESTS_DIRECTORY_NAME = '/Tests/';

    public function getNodeType(): string
    {
        return Return_::class;
    }

    /**
     * @param Return_ $node
     *
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (self::CONFIG_FILE_NAME !== basename($scope->getFile())) {
            return [];
        }

        // a config of a Tests directory is a fixture, it configures no bundle at all
        if (str_contains($scope->getFile(), self::TESTS_DIRECTORY_NAME)) {
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

            return $this->createGroupRuleErrors($arrayItem);
        }

        return [];
    }

    /**
     * Every service group is reported on its own, the "menus" one being the only one left alone.
     *
     * @return list<IdentifierRuleError>
     */
    private function createGroupRuleErrors(ArrayItem $servicesArrayItem): array
    {
        $servicesValue = $servicesArrayItem->value;
        if (!$servicesValue instanceof Array_) {
            return [$this->createRuleError(self::SERVICES_KEY_NAME, $servicesArrayItem->getStartLine())];
        }

        $ruleErrors = [];

        foreach ($servicesValue->items as $groupArrayItem) {
            if (!$groupArrayItem->key instanceof String_) {
                continue;
            }

            if (self::MENUS_KEY_NAME === $groupArrayItem->key->value) {
                continue;
            }

            $ruleErrors[] = $this->createRuleError($groupArrayItem->key->value, $groupArrayItem->getStartLine());
        }

        return $ruleErrors;
    }

    private function createRuleError(string $groupName, int $line): IdentifierRuleError
    {
        return RuleErrorBuilder::message(sprintf(
            'Config file must not define services. Register the "%s" group in the autowired Config/services.php instead.',
            $groupName
        ))
            ->identifier('mautic.noServicesInBundleConfig')
            ->line($line)
            ->build();
    }
}
