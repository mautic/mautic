<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use Doctrine\Common\Collections\Collection;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Method with "Collection" return type must declare generic docblock, e.g. "@return Collection<int, Entity>".
 *
 * @implements Rule<ClassMethod>
 *
 * @see \Utils\PHPStan\Tests\Rule\CollectionReturnMustHaveGenericDocblockRuleTest
 */
final class CollectionReturnMustHaveGenericDocblockRule implements Rule
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
        return ClassMethod::class;
    }

    /**
     * @param ClassMethod $node
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $returnType = $node->returnType instanceof NullableType ? $node->returnType->type : $node->returnType;

        if (!$returnType instanceof Name || Collection::class !== $returnType->toString()) {
            return [];
        }

        if ($this->hasGenericReturnDocblock($node)) {
            return [];
        }

        $ruleError = RuleErrorBuilder::message(sprintf(
            'Method "%s()" returns bare "Collection", add generic docblock, e.g. "@return Collection<int, %s>".',
            $node->name->toString(),
            'SomeEntity'
        ))
            ->identifier('mautic.collectionReturnMustHaveGenericDocblock')
            ->line($node->getStartLine())
            ->build();

        return [$ruleError];
    }

    private function hasGenericReturnDocblock(ClassMethod $classMethod): bool
    {
        $docComment = $classMethod->getDocComment();
        if (null === $docComment) {
            return false;
        }

        $tokenIterator = new TokenIterator($this->lexer->tokenize($docComment->getText()));
        $phpDocNode = $this->phpDocParser->parse($tokenIterator);

        foreach ($phpDocNode->getReturnTagValues() as $returnTagValue) {
            if ($this->isGenericType($returnTagValue->type)) {
                return true;
            }
        }

        return false;
    }

    private function isGenericType(TypeNode $typeNode): bool
    {
        if ($typeNode instanceof GenericTypeNode) {
            return true;
        }

        if ($typeNode instanceof NullableTypeNode) {
            return $this->isGenericType($typeNode->type);
        }

        if ($typeNode instanceof UnionTypeNode) {
            foreach ($typeNode->types as $innerTypeNode) {
                if ($this->isGenericType($innerTypeNode)) {
                    return true;
                }
            }
        }

        return false;
    }
}
