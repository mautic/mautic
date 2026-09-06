<?php

declare(strict_types=1);

namespace Utils\ECS\Fixer;

use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\TokensAnalyzer;
use PhpCsFixer\WhitespacesFixerConfig;

final class NoBlankLineBetweenImportsFixer implements FixerInterface, WhitespacesAwareFixerInterface
{
    private WhitespacesFixerConfig $whitespacesConfig;

    public function getName(): string
    {
        return 'Utils/no_blank_line_between_imports';
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'There must be no blank line between import "use" statements.',
            [new CodeSample("<?php\nuse Foo\\Bar;\n\nuse function Foo\\baz;\n")],
        );
    }

    // run after OrderedImportsFixer and BlankLineBetweenImportGroupsFixer
    public function getPriority(): int
    {
        return -50;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(\T_USE);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function supports(\SplFileInfo $file): bool
    {
        return true;
    }

    public function setWhitespacesConfig(WhitespacesFixerConfig $config): void
    {
        $this->whitespacesConfig = $config;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        $tokensAnalyzer = new TokensAnalyzer($tokens);
        $useIndexes = $tokensAnalyzer->getImportUseIndexes();

        $lineEnding = $this->whitespacesConfig->getLineEnding();

        // start at the 2nd use, so the blank line after the namespace stays untouched
        for ($i = 1, $count = count($useIndexes); $i < $count; ++$i) {
            $useIndex = $useIndexes[$i];

            $previousIndex = $tokens->getPrevMeaningfulToken($useIndex);
            if ($previousIndex === null || !$tokens[$previousIndex]->equals(';')) {
                continue;
            }

            $whitespaceIndex = $useIndex - 1;
            if (!$tokens[$whitespaceIndex]->isWhitespace()) {
                continue;
            }

            if (substr_count($tokens[$whitespaceIndex]->getContent(), "\n") < 2) {
                continue;
            }

            $tokens[$whitespaceIndex] = new Token([\T_WHITESPACE, $lineEnding]);
        }
    }
}
