<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use Doctrine\Common\Collections\Collection;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Property;
use PHPStan\Analyser\Scope;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Property typed "Collection" must declare generic docblock, e.g. "@var Collection<int, Entity>".
 *
 * @implements Rule<Property>
 */
final class CollectionPropertyMustHaveGenericDocblockRule implements Rule
{
    private readonly Lexer $lexer;

    private readonly PhpDocParser $phpDocParser;

    public function __construct()
    {
        $parserConfig = new ParserConfig([]);
        $this->lexer = new Lexer($parserConfig);
        $constExprParser = new ConstExprParser($parserConfig);
        $this->phpDocParser = new PhpDocParser($parserConfig, new TypeParser($parserConfig, $constExprParser), $constExprParser);
    }

    public function getNodeType(): string
    {
        return Property::class;
    }

    /**
     * @param Property $node
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->type instanceof Name || Collection::class !== $node->type->toString()) {
            return [];
        }

        if ($this->hasGenericVarDocblock($node)) {
            return [];
        }

        $propertyName = $node->props[0]->name->toString();

        $ruleError = RuleErrorBuilder::message(sprintf(
            'Property "$%s" is bare "Collection", add generic docblock, e.g. "@var Collection<int, %s>".',
            $propertyName,
            'SomeEntity'
        ))
            ->identifier('mautic.collectionPropertyMustHaveGenericDocblock')
            ->line($node->getStartLine())
            ->build();

        return [$ruleError];
    }

    private function hasGenericVarDocblock(Property $property): bool
    {
        $docComment = $property->getDocComment();
        if (null === $docComment) {
            return false;
        }

        $tokenIterator = new TokenIterator($this->lexer->tokenize($docComment->getText()));
        $phpDocNode = $this->phpDocParser->parse($tokenIterator);

        foreach ($phpDocNode->getVarTagValues() as $varTagValue) {
            if ($varTagValue->type instanceof GenericTypeNode) {
                return true;
            }
        }

        return false;
    }
}
