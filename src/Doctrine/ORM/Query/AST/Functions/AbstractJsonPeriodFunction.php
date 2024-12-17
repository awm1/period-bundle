<?php

declare(strict_types=1);

namespace Andante\PeriodBundle\Doctrine\ORM\Query\AST\Functions;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

abstract class AbstractJsonPeriodFunction extends FunctionNode
{
    private PathExpression $fieldPathExpression;

    abstract protected function getPropertyName(): string;

    public function getSql(SqlWalker $sqlWalker): string
    {
        // The field is in JSON field
        return \sprintf(
            'JSON_UNQUOTE(JSON_EXTRACT(%s,\'$.%s\'))',
            $this->fieldPathExpression->dispatch($sqlWalker),
            $this->getPropertyName()
        );
    }

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
        $this->fieldPathExpression = $parser->StateFieldPathExpression();
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }
}
