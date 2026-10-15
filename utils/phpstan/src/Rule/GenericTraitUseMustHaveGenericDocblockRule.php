<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Stmt\TraitUse;
use PHPStan\Analyser\Scope;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Using a generic trait (declares "@template") must carry a "@use Trait<Entity>" docblock.
 *
 * @implements Rule<TraitUse>
 *
 * @see \Utils\PHPStan\Tests\Rule\GenericTraitUseMustHaveGenericDocblockRuleTest
 */
final readonly class GenericTraitUseMustHaveGenericDocblockRule implements Rule
{
    private Lexer $lexer;

    private PhpDocParser $phpDocParser;

    public function __construct(
        private ReflectionProvider $reflectionProvider,
    ) {
        $parserConfig = new ParserConfig([]);
        $this->lexer = new Lexer($parserConfig);
        $constExprParser = new ConstExprParser($parserConfig);
        $this->phpDocParser = new PhpDocParser($parserConfig, new TypeParser($parserConfig, $constExprParser), $constExprParser);
    }

    public function getNodeType(): string
    {
        return TraitUse::class;
    }

    /**
     * @param TraitUse $node
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $documentedTraitShortNames = $this->resolveDocumentedTraitShortNames($node);

        $ruleErrors = [];
        foreach ($node->traits as $trait) {
            $traitName = $trait->toString();
            if (!$this->reflectionProvider->hasClass($traitName)) {
                continue;
            }

            $traitReflection = $this->reflectionProvider->getClass($traitName);
            if ([] === $traitReflection->getTemplateTags()) {
                continue;
            }

            $shortName = $this->resolveShortName($traitName);
            if (in_array($shortName, $documentedTraitShortNames, true)) {
                continue;
            }

            $ruleErrors[] = RuleErrorBuilder::message(sprintf(
                'Trait "%s" is generic, add "@use %s<SomeEntity>" docblock above the "use" statement.',
                $shortName,
                $shortName
            ))
                ->identifier('mautic.genericTraitUseMustHaveGenericDocblock')
                ->line($node->getStartLine())
                ->build();
        }

        return $ruleErrors;
    }

    /**
     * @return list<string>
     */
    private function resolveDocumentedTraitShortNames(TraitUse $traitUse): array
    {
        $docComment = $traitUse->getDocComment();
        if (null === $docComment) {
            return [];
        }

        $tokenIterator = new TokenIterator($this->lexer->tokenize($docComment->getText()));
        $phpDocNode = $this->phpDocParser->parse($tokenIterator);

        $shortNames = [];
        foreach ($phpDocNode->getUsesTagValues() as $usesTagValue) {
            if (!$usesTagValue->type instanceof GenericTypeNode) {
                continue;
            }

            $typeNode = $usesTagValue->type->type;
            if (!$typeNode instanceof IdentifierTypeNode) {
                continue;
            }

            $shortNames[] = $this->resolveShortName($typeNode->name);
        }

        return $shortNames;
    }

    private function resolveShortName(string $name): string
    {
        $parts = explode('\\', $name);

        return end($parts);
    }
}
