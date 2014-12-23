<?php
/**
 * Author: Nil Portugués Calderó <contact@nilportugues.com>
 * Date: 6/26/14
 * Time: 12:10 AM
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace NilPortugues\SqlQueryFormatter;

use NilPortugues\SqlQueryFormatter\Helper\Comment;
use NilPortugues\SqlQueryFormatter\Helper\Indent;
use NilPortugues\SqlQueryFormatter\Helper\NewLine;
use NilPortugues\SqlQueryFormatter\Helper\Parentheses;
use NilPortugues\SqlQueryFormatter\Helper\WhiteSpace;
use NilPortugues\SqlQueryFormatter\Tokenizer\Tokenizer;

/**
 * Lightweight Formatter heavily based on https://github.com/jdorn/sql-formatter.
 *
 * Class Formatter
 * @package NilPortugues\SqlQueryFormatter
 */
class Formatter
{
    /**
     * @var Tokenizer
     */
    protected $tokenizer;

    /**
     * @var NewLine
     */
    protected $newLine;

    /**
     * @var Parentheses
     */
    protected $parentheses;

    /**
     * @var string
     */
    protected $tab = "    ";
    /**
     * @var int
     */
    protected $inlineCount = 0;

    /**
     * @var bool
     */
    protected $clauseLimit = false;
    /**
     * @var string
     */
    protected $formattedSql = '';
    /**
     * @var Indent
     */
    protected $indentation;

    /**
     * @var Comment
     */
    protected $comment;

    /**
     * Returns a SQL string in a readable human-friendly format.
     *
     * @param string $sql
     *
     * @return string
     */
    public function format($sql)
    {
        $this->reset();
        $tab = "\t";

        $originalTokens = $this->tokenizer->tokenize($sql);
        $tokens         = WhiteSpace::removeTokenWhitespace($originalTokens);

        foreach ($tokens as $i => $token) {
            $queryValue = $token[Tokenizer::TOKEN_VALUE];

            $this->indentation
                ->increaseSpecialIndent()
                ->increaseBlockIndent();

            $addedNewline = $this->newLine->addNewLineBreak($tab);

            if ($this->comment->stringHasCommentToken($token)) {
                $this->formattedSql = $this->comment->writeCommentBlock($token, $tab, $queryValue);
                continue;
            }

            if ($this->parentheses->getInlineParentheses()) {
                if ($this->parentheses->stringIsClosingParentheses($token)) {
                    $this->parentheses->writeInlineParenthesesBlock($tab, $queryValue);
                    continue;
                }
                $this->newLine->writeNewLineForLongCommaInlineValues($token);
                $this->inlineCount += strlen($token[Tokenizer::TOKEN_VALUE]);
            }

            if ($this->parentheses->stringIsOpeningParentheses($token)) {
                $length = 0;
                for ($j = 1; $j <= 250; $j++) {
                    if (isset($tokens[$i + $j])) {
                        $next = $tokens[$i + $j];
                        if ($this->parentheses->stringIsClosingParentheses($next)) {
                            $this->parentheses->writeNewInlineParentheses();
                            break;
                        }

                        if ($this->parentheses->invalidParenthesesTokenValue($next)
                            || $this->parentheses->invalidParenthesesTokenType($next)
                        ) {
                            break;
                        }

                        $length += strlen($next[Tokenizer::TOKEN_VALUE]);
                    }
                }
                $this->newLine->writeNewLineForLongInlineValues($length);

                if (WhiteSpace::isPrecedingCurrentTokenOfTokenTypeWhiteSpace($originalTokens, $token)) {
                    $this->formattedSql = rtrim($this->formattedSql, ' ');
                }

                $this->newLine->addNewLineAfterOpeningParentheses();
            } elseif ($this->parentheses->stringIsClosingParentheses($token)) {
                $this->indentation->decreaseIndentLevelUntilIndentTypeIsSpecial($this);
                $this->newLine->addNewLineBeforeClosingParentheses($addedNewline, $tab);
            } elseif ($this->isTokenTypeReservedTopLevel($token)) {
                $this->indentation
                    ->setIncreaseSpecialIndent(true)
                    ->decreaseSpecialIndentIfCurrentIndentTypeIsSpecial($this);

                $this->newLine->writeNewLineBecauseOfTopLevelReservedWord($addedNewline, $tab);

                if (WhiteSpace::tokenHasExtraWhiteSpaces($token)) {
                    $queryValue = preg_replace('/\s+/', ' ', $queryValue);
                }
                $this->tokenHasLimitClause($token);
            } elseif ($this->stringIsEndOfLimitClause($token)) {
                $this->clauseLimit = false;
            } elseif (
                $token[Tokenizer::TOKEN_VALUE] === ','
                && false === $this->parentheses->getInlineParentheses()
            ) {
                $this->newLine->writeNewLineBecauseOfComma();
            } elseif ($this->newLine->isTokenTypeReservedNewLine($token)) {
                $this->newLine->writeNewLineBeforeReservedWord($addedNewline, $tab);

                if (WhiteSpace::tokenHasExtraWhiteSpaces($token)) {
                    $queryValue = preg_replace('/\s+/', ' ', $queryValue);
                }
            }

            if ($this->tokenHasMultipleBoundaryCharactersTogether($token, $tokens, $i, $originalTokens)) {
                $this->formattedSql = rtrim($this->formattedSql, ' ');
            }

            if (WhiteSpace::tokenHasExtraWhiteSpaceLeft($token)) {
                $this->formattedSql = rtrim($this->formattedSql, ' ');
            }

            $this->formattedSql .= $queryValue . ' ';

            if (WhiteSpace::tokenHasExtraWhiteSpaceRight($token)) {
                $this->formattedSql = rtrim($this->formattedSql, ' ');
            }

            if ($this->tokenIsMinusSign($token, $tokens, $i)) {
                $previousTokenType = $tokens[$i - 1][Tokenizer::TOKEN_TYPE];

                if (WhiteSpace::tokenIsNumberAndHasExtraWhiteSpaceRight($previousTokenType)) {
                    $this->formattedSql = rtrim($this->formattedSql, ' ');
                }
            }
        }

        return trim(str_replace(["\t", " \n"], [$this->tab, "\n"], $this->formattedSql)) . "\n";
    }

    /**
     *
     */
    public function reset()
    {
        $this->tokenizer   = new Tokenizer();
        $this->indentation = new Indent();
        $this->parentheses = new Parentheses($this, $this->indentation);
        $this->newLine     = new NewLine($this, $this->indentation, $this->parentheses);
        $this->comment     = new Comment($this, $this->indentation, $this->newLine);

        $this->formattedSql = '';
    }


    /**
     * @param $token
     *
     * @return bool
     */
    protected function isTokenTypeReservedTopLevel($token)
    {
        return $token[Tokenizer::TOKEN_TYPE] === Tokenizer::TOKEN_TYPE_RESERVED_TOP_LEVEL;
    }

    /**
     * @param $token
     */
    protected function tokenHasLimitClause($token)
    {
        if ('LIMIT' === $token[Tokenizer::TOKEN_VALUE] && false === $this->parentheses->getInlineParentheses()) {
            $this->clauseLimit = true;
        }
    }

    /**
     * @param $token
     *
     * @return bool
     */
    protected function stringIsEndOfLimitClause($token)
    {
        return $this->clauseLimit
        && $token[Tokenizer::TOKEN_VALUE] !== ","
        && $token[Tokenizer::TOKEN_TYPE] !== Tokenizer::TOKEN_TYPE_NUMBER
        && $token[Tokenizer::TOKEN_TYPE] !== Tokenizer::TOKEN_TYPE_WHITESPACE;
    }


    /**
     * @param $token
     * @param $tokens
     * @param $i
     * @param $originalTokens
     *
     * @return bool
     */
    protected function tokenHasMultipleBoundaryCharactersTogether($token, &$tokens, $i, &$originalTokens)
    {
        return $token[Tokenizer::TOKEN_TYPE] === Tokenizer::TOKEN_TYPE_BOUNDARY
        && isset($tokens[$i - 1])
        && $tokens[$i - 1][Tokenizer::TOKEN_TYPE] === Tokenizer::TOKEN_TYPE_BOUNDARY
        && isset($originalTokens[$token['i'] - 1])
        && $originalTokens[$token['i'] - 1][Tokenizer::TOKEN_TYPE] !== Tokenizer::TOKEN_TYPE_WHITESPACE;
    }

    /**
     * @param $token
     * @param $tokens
     * @param $i
     *
     * @return bool
     */
    protected function tokenIsMinusSign($token, &$tokens, $i)
    {
        return '-' === $token[Tokenizer::TOKEN_VALUE]
        && isset($tokens[$i + 1])
        && $tokens[$i + 1][Tokenizer::TOKEN_TYPE] === Tokenizer::TOKEN_TYPE_NUMBER
        && isset($tokens[$i - 1]);
    }

    /**
     * @return string
     */
    public function getFormattedSql()
    {
        return $this->formattedSql;
    }

    /**
     * @param string $formattedSql
     *
     * @return $this
     */
    public function setFormattedSql($formattedSql)
    {
        $this->formattedSql = $formattedSql;
        return $this;
    }

    /**
     * @param $string
     *
     * @return $this
     */
    public function appendToFormattedSql($string)
    {
        $this->formattedSql .= $string;
        return $this;
    }

    /**
     * @return int
     */
    public function getInlineCount()
    {
        return $this->inlineCount;
    }

    /**
     * @param int $inlineCount
     *
     * @return $this
     */
    public function setInlineCount($inlineCount)
    {
        $this->inlineCount = $inlineCount;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getClauseLimit()
    {
        return $this->clauseLimit;
    }

    /**
     * @param boolean $clauseLimit
     *
     * @return $this
     */
    public function setClauseLimit($clauseLimit)
    {
        $this->clauseLimit = $clauseLimit;
        return $this;
    }
}
