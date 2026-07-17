<?php

declare(strict_types=1);

namespace Utils\ECS;

use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use Symplify\CodingStandard\Fixer\AbstractSymplifyFixer;
use Symplify\CodingStandard\TokenAnalyzer\ParamNewliner;

/**
 * Every parameter of a public "autowire*()" method must be on a standalone line.
 *
 * These methods are the dependency injection points of controllers. Their parameter list grows over time and a
 * one-line signature makes every added or removed dependency a full-line diff. One parameter per line keeps the
 * diff at the single dependency that changed.
 *
 * @see Tests\StandaloneLineAutowireParamFixer\StandaloneLineAutowireParamFixerTest
 */
final class StandaloneLineAutowireParamFixer extends AbstractSymplifyFixer
{
    /**
     * @var string
     */
    private const ERROR_MESSAGE = 'Parameter of an autowire method should be on a standalone line';

    /**
     * @var string
     */
    private const AUTOWIRE_PREFIX = 'autowire';

    /**
     * @var int[]
     */
    private const METHOD_MODIFIER_KINDS = [T_PUBLIC, T_PROTECTED, T_PRIVATE, T_STATIC, T_FINAL, T_ABSTRACT];

    public function __construct(
        private readonly ParamNewliner $paramNewliner,
    ) {
    }

    /**
     * Must run before.
     *
     * @see \PhpCsFixer\Fixer\Basic\BracesFixer::getPriority()
     */
    public function getPriority(): int
    {
        return 40;
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(self::ERROR_MESSAGE, [new CodeSample(<<<'CODE_SAMPLE'
final class SomeController
{
    #[Required]
    public function autowireSomeController(FormModel $formModel, SubmissionModel $submissionModel): void
    {
    }
}
CODE_SAMPLE
        )]);
    }

    /**
     * @param Tokens<Token> $tokens
     */
    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_FUNCTION);
    }

    /**
     * @param Tokens<Token> $tokens
     */
    public function fix(\SplFileInfo $fileInfo, Tokens $tokens): void
    {
        // from the bottom up, as adding tokens shifts every position after them
        for ($position = count($tokens) - 1; $position >= 0; --$position) {
            /** @var Token $token */
            $token = $tokens[$position];
            if (!$token->isGivenKind(T_FUNCTION)) {
                continue;
            }

            if (!$this->isPublicAutowireMethod($tokens, $position)) {
                continue;
            }

            if (!$this->hasParams($tokens, $position)) {
                continue;
            }

            $this->paramNewliner->processFunction($tokens, $position);
        }
    }

    /**
     * @param Tokens<Token> $tokens
     */
    private function isPublicAutowireMethod(Tokens $tokens, int $position): bool
    {
        $namePosition = $tokens->getNextMeaningfulToken($position);
        if (null === $namePosition) {
            return false;
        }

        /** @var Token $nameToken */
        $nameToken = $tokens[$namePosition];

        // a closure has no name
        if (!$nameToken->isGivenKind(T_STRING)) {
            return false;
        }

        if (!str_starts_with($nameToken->getContent(), self::AUTOWIRE_PREFIX)) {
            return false;
        }

        return $this->isPublic($tokens, $position);
    }

    /**
     * Only an explicit "public" counts, so that a plain function outside a class is never touched.
     *
     * @param Tokens<Token> $tokens
     */
    private function isPublic(Tokens $tokens, int $position): bool
    {
        $previousPosition = $tokens->getPrevMeaningfulToken($position);

        while (null !== $previousPosition) {
            /** @var Token $previousToken */
            $previousToken = $tokens[$previousPosition];

            if (!$previousToken->isGivenKind(self::METHOD_MODIFIER_KINDS)) {
                return false;
            }

            if ($previousToken->isGivenKind(T_PUBLIC)) {
                return true;
            }

            $previousPosition = $tokens->getPrevMeaningfulToken($previousPosition);
        }

        return false;
    }

    /**
     * An empty parameter list would be broken into an empty line between the brackets.
     *
     * @param Tokens<Token> $tokens
     */
    private function hasParams(Tokens $tokens, int $position): bool
    {
        $namePosition = $tokens->getNextMeaningfulToken($position);
        if (null === $namePosition) {
            return false;
        }

        $openBracketPosition = $tokens->getNextMeaningfulToken($namePosition);
        if (null === $openBracketPosition) {
            return false;
        }

        $firstParamPosition = $tokens->getNextMeaningfulToken($openBracketPosition);
        if (null === $firstParamPosition) {
            return false;
        }

        /** @var Token $firstParamToken */
        $firstParamToken = $tokens[$firstParamPosition];

        return ')' !== $firstParamToken->getContent();
    }
}
