<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use Doctrine\Common\Collections\Collection;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
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
 * Method parameter typed "Collection" must declare generic docblock, e.g. "@param Collection<int, Entity> $items".
 *
 * @implements Rule<ClassMethod>
 */
final class CollectionParamMustHaveGenericDocblockRule implements Rule
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
        $genericParamNames = $this->resolveGenericParamNames($node);

        $ruleErrors = [];

        foreach ($node->params as $param) {
            if (!$param->type instanceof Name || Collection::class !== $param->type->toString()) {
                continue;
            }

            if (!$param->var instanceof Node\Expr\Variable || !is_string($param->var->name)) {
                continue;
            }

            if (in_array($param->var->name, $genericParamNames, true)) {
                continue;
            }

            $ruleErrors[] = RuleErrorBuilder::message(sprintf(
                'Parameter "$%s" of method "%s()" is bare "Collection", add generic docblock, e.g. "@param Collection<int, %s> $%s".',
                $param->var->name,
                $node->name->toString(),
                'SomeEntity',
                $param->var->name
            ))
                ->identifier('mautic.collectionParamMustHaveGenericDocblock')
                ->line($param->getStartLine())
                ->build();
        }

        return $ruleErrors;
    }

    /**
     * @return list<string>
     */
    private function resolveGenericParamNames(ClassMethod $classMethod): array
    {
        $docComment = $classMethod->getDocComment();
        if (null === $docComment) {
            return [];
        }

        $tokenIterator = new TokenIterator($this->lexer->tokenize($docComment->getText()));
        $phpDocNode = $this->phpDocParser->parse($tokenIterator);

        $genericParamNames = [];
        foreach ($phpDocNode->getParamTagValues() as $paramTagValue) {
            if ($paramTagValue instanceof ParamTagValueNode && $paramTagValue->type instanceof GenericTypeNode) {
                $genericParamNames[] = ltrim($paramTagValue->parameterName, '$');
            }
        }

        return $genericParamNames;
    }
}
