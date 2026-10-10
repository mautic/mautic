<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use Doctrine\Common\Collections\Collection;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;
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
        if (!$node->returnType instanceof Name || Collection::class !== $node->returnType->toString()) {
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
            if ($returnTagValue->type instanceof GenericTypeNode) {
                return true;
            }
        }

        return false;
    }
}
