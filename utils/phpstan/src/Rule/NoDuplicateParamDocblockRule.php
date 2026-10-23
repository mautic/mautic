<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * A method docblock must not repeat the same "@param $name" twice, so a widened/added "@param" cannot
 * stack on top of the old one (e.g. both "@param object $entity" and "@param Campaign $entity").
 *
 * @implements Rule<ClassMethod>
 *
 * @see \Utils\PHPStan\Tests\Rule\NoDuplicateParamDocblockRuleTest
 */
final readonly class NoDuplicateParamDocblockRule implements Rule
{
    private Lexer $lexer;

    private PhpDocParser $phpDocParser;

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
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $docComment = $node->getDocComment();
        if (null === $docComment) {
            return [];
        }

        $tokenIterator = new TokenIterator($this->lexer->tokenize($docComment->getText()));
        $phpDocNode = $this->phpDocParser->parse($tokenIterator);

        $seenParameterNames = [];
        $ruleErrors = [];
        foreach ($phpDocNode->getParamTagValues() as $paramTagValue) {
            $parameterName = $paramTagValue->parameterName;
            if (!isset($seenParameterNames[$parameterName])) {
                $seenParameterNames[$parameterName] = true;
                continue;
            }

            $ruleErrors[] = RuleErrorBuilder::message(sprintf(
                'Param "%s" is documented twice in the "%s()" docblock, keep a single "@param" per parameter.',
                $parameterName,
                $node->name->toString()
            ))
                ->identifier('mautic.noDuplicateParamDocblock')
                ->line($node->getStartLine())
                ->build();
        }

        return $ruleErrors;
    }
}
