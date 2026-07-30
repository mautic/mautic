<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Utils\PHPStan\Collector\ServiceAliasCollector;
use Utils\PHPStan\Collector\ServiceNameUsageCollector;
use Utils\PHPStan\ServiceNameUsageResolver;

/**
 * Reports the service id aliases of Config/services.php nothing refers to,
 * e.g. $services->alias('mautic.some.helper', SomeHelper::class) no service('mautic.some.helper') asks for.
 *
 * A class name alias, e.g. $services->alias(SomeHelper::class, 'mautic.some.helper'), is left alone on purpose.
 * It is not a mere lookup shortcut: it replaces the definition the PSR-4 $services->load() registers under the
 * very same class name id, so dropping it brings that autowired definition back to life - a second service of
 * the same class, tagged again by autoconfigure(), or a container that no longer compiles.
 *
 * A model id, e.g. "mautic.lead.model.list", is left alone as well. ModelFactory builds it at runtime out of
 * a model key that travels as data - a getModelName() return, a "model" form option, a "model" ajax parameter
 * of a Twig template or a JS file - so there is no way to tell such an id is unused.
 *
 * Only PHP is analysed, so an alias used by a Twig template, a YAML or an XML file alone looks unused here.
 *
 * @implements Rule<CollectedDataNode>
 */
final class NoUnusedServiceAliasRule implements Rule
{
    /**
     * The "mautic.%s.model.%s" format ModelFactory builds a model container id by.
     *
     * @see \Mautic\CoreBundle\Factory\ModelFactory::getModel()
     *
     * @var string
     */
    private const MODEL_ID_PATTERN = '#^mautic\.[a-zA-Z0-9_]+\.model\.[a-zA-Z0-9_]+$#';

    public function __construct(
        private ServiceNameUsageResolver $serviceNameUsageResolver,
    ) {
    }

    public function getNodeType(): string
    {
        return CollectedDataNode::class;
    }

    /**
     * @param CollectedDataNode $node
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $ruleErrors = [];

        /** @var array<string, list<array{string, int, int}>> $aliasesByFilePath */
        $aliasesByFilePath = $node->get(ServiceAliasCollector::class);

        /** @var array<string, list<array{string, int}>> $usagesByFilePath */
        $usagesByFilePath = $node->get(ServiceNameUsageCollector::class);

        foreach ($aliasesByFilePath as $filePath => $aliases) {
            foreach ($aliases as [$aliasName, $startLine, $endLine]) {
                if (str_contains($aliasName, '\\')) {
                    continue;
                }

                if (1 === preg_match(self::MODEL_ID_PATTERN, $aliasName)) {
                    continue;
                }

                if ($this->serviceNameUsageResolver->isUsedName($aliasName, $usagesByFilePath, $filePath, $startLine, $endLine)) {
                    continue;
                }

                $ruleErrors[] = RuleErrorBuilder::message(sprintf(
                    'Service alias "%s" is never used, remove it.',
                    $aliasName
                ))
                    ->identifier('mautic.noUnusedServiceAlias')
                    ->file($filePath)
                    ->line($startLine)
                    ->build();
            }
        }

        return $ruleErrors;
    }
}
