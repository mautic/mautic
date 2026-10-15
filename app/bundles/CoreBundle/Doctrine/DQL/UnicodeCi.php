<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Doctrine\DQL;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

final class UnicodeCi extends FunctionNode
{
    private Node $value;

    private ?Node $patternMode = null;

    private ?Node $escapeCharacter = null;

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
        $this->value = $parser->StringPrimary();
        if ($parser->getLexer()->isNextToken(TokenType::T_COMMA)) {
            $parser->match(TokenType::T_COMMA);
            $this->patternMode = $parser->Literal();
            if ($parser->getLexer()->isNextToken(TokenType::T_COMMA)) {
                $parser->match(TokenType::T_COMMA);
                $this->escapeCharacter = $parser->StringPrimary();
            }
        }
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        $arguments = [$sqlWalker->walkStringPrimary($this->value)];
        if (null !== $this->patternMode) {
            $arguments[] = $this->patternMode->dispatch($sqlWalker);
        }
        if (null !== $this->escapeCharacter) {
            $arguments[] = $sqlWalker->walkStringPrimary($this->escapeCharacter);
        }

        return 'mautic_unicode_ci('.implode(', ', $arguments).')';
    }
}
