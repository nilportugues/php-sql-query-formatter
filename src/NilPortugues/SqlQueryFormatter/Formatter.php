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

use NilPortugues\SqlQueryFormatter\Helper\Tokenizer;

/**
 * Lightweight Formatter heavily based on https://github.com/jdorn/sql-formatter.
 *
 * Class Formatter
 * @package NilPortugues\SqlQueryFormatter
 */
class Formatter
{
    /**
     * @var string
     */
    private $tab = "    ";

    /**
     * @var int
     */
    private $indentLvl = 0;

    /**
     * @var int
     */
    private $inlineCount = 0;

    /**
     * @var bool
     */
    private $newline = false;

    /**
     * @var bool
     */
    private $increaseSpecialIndent = false;

    /**
     * @var bool
     */
    private $increaseBlockIndent = false;

    /**
     * @var bool
     */
    private $inlineParentheses = false;

    /**
     * @var array
     */
    private $indentTypes = array();

    /**
     * @var bool
     */
    private $inlineIndented = false;

    /**
     * @var bool
     */
    private $clauseLimit = false;

    /**
     * @var string
     */
    private $formattedSql = '';

    /**
     *
     */
    public function __construct()
    {
        $this->tokenizer = new Tokenizer();
    }

    /**
     * Returns a SQL string in a readable human-friendly format.
     *
     * @param string $sql
     *
     * @return string
     */
    public function format($sql)
    {
        $this->formattedSql = '';
        $tab                = "\t";

        $originalTokens = $this->tokenizer->tokenize($sql);
        $tokens         = $this->removeTokenWhitespace($originalTokens);

        foreach ($tokens as $i => $token) {

            $queryValue = $token[Tokenizer::TOKEN_VALUE];

            $this->increaseSpecialIndent();
            $this->increaseBlockIndent();

            $addedNewline = $this->addNewLineBreak($tab);

            if ($this->stringHasCommentToken($token)) {
                $this->formattedSql = $this->writeCommentBlock($token, $tab, $queryValue);
                continue;
            }

            if ($this->inlineParentheses) {

                if ($this->stringIsClosingParentheses($token)) {
                    $this->writeInlineParenthesesBlock($tab, $queryValue);
                    continue;
                }
                $this->writeNewLineForLongCommaInlineValues($token);
                $this->inlineCount += strlen($token[Tokenizer::TOKEN_VALUE]);
            }

            if ($this->stringIsOpeningParentheses($token)) {

                $length = 0;
                for ($j = 1; $j <= 250; $j++) {
                    if (isset($tokens[$i + $j])) {
                        $next = $tokens[$i + $j];
                        if ($this->stringIsClosingParentheses($next)) {
                            $this->writeNewInlineParentheses();
                            break;
                        }

                        if ($this->invalidParenthesesTokenValue($next) || $this->invalidParenthesesTokenType($next)) {
                            break;
                        }

                        $length += strlen($next[Tokenizer::TOKEN_VALUE]);
                    }
                }
                $this->writeNewLineForLongInlineValues($length);

                if ($this->isPrecedingCurrentTokenOfTokenTypeWhiteSpace($originalTokens, $token)) {
                    $this->formattedSql = rtrim($this->formattedSql, ' ');
                }

                $this->addNewLineAfterOpeningParentheses();

            } elseif ($this->stringIsClosingParentheses($token)) {

                $this->decreaseIndentLevelUntilIndentTypeIsSpecial();
                $this->addNewLineBeforeClosingParentheses($addedNewline, $tab);

            } elseif ($this->isTokenTypeReservedTopLevel($token)) {

                $this->increaseSpecialIndent = true;
                $this->decreaseSpecialIndentIfCurrentIndentTypeIsSpecial();
                $this->writeNewLineBecauseOfTopLevelReservedWord($addedNewline, $tab);

                if ($this->tokenHasExtraWhiteSpaces($token)) {
                    $queryValue = preg_replace('/\s+/', ' ', $queryValue);
                }
                $this->tokenHasLimitClause($token);

            } elseif ($this->stringIsEndOfLimitClause($token)) {

                $this->clauseLimit = false;

            } elseif ($token[Tokenizer::TOKEN_VALUE] === ',' && !$this->inlineParentheses) {

                $this->writeNewLineBecauseOfComma();

            } elseif ($this->isTokenTypeReservedNewLine($token)) {
                $this->writeNewLineBeforeReservedWord($addedNewline, $tab);

                if ($this->tokenHasExtraWhiteSpaces($token)) {
                    $queryValue = preg_replace('/\s+/', ' ', $queryValue);
                }
            }

            if ($this->tokenHasMultipleBoundaryCharactersTogether($token, $tokens, $i, $originalTokens)) {
                $this->formattedSql = rtrim($this->formattedSql, ' ');
            }

            if ($this->tokenHasExtraWhiteSpaceLeft($token)) {
                $this->formattedSql = rtrim($this->formattedSql, ' ');
            }

            $this->formattedSql .= $queryValue . ' ';

            if ($this->tokenHasExtraWhiteSpaceRight($token)) {
                $this->formattedSql = rtrim($this->formattedSql, ' ');
            }

            if ($this->tokenIsMinusSign($token, $tokens, $i)) {
                $previousTokenType = $tokens[$i - 1][Tokenizer::TOKEN_TYPE];

                if ($this->tokenIsNumberAndHasExtraWhiteSpaceRight($previousTokenType)) {
                    $this->formattedSql = rtrim($this->formattedSql, ' ');
                }
            }
        }

        return trim(str_replace(
                array("\t", " \n"),
                array($this->tab, "\n"), $this->formattedSql)
        ) . "\n";
    }

    /**
     * @param $originalTokens
     *
     * @return array
     */
    private function removeTokenWhitespace(array &$originalTokens)
    {
        $tokens = array();
        foreach ($originalTokens as $i => &$token) {
            if ($token[Tokenizer::TOKEN_TYPE] !== Tokenizer::TOKEN_TYPE_WHITESPACE) {
                $token['i'] = $i;
                $tokens[]   = $token;
            }
        }

        return $tokens;
    }

    /**
     * Increase the Special Indent if increaseSpecialIndent is true after the current iteration.
     */
    private function increaseSpecialIndent()
    {
        if ($this->increaseSpecialIndent) {
            $this->indentLvl++;
            $this->increaseSpecialIndent = false;
            array_unshift($this->indentTypes, 'special');
        }
    }

    /**
     * Increase the Block Indent if increaseBlockIndent is true after the current iteration.
     */
    private function increaseBlockIndent()
    {
        if ($this->increaseBlockIndent) {
            $this->indentLvl++;
            $this->increaseBlockIndent = false;
            array_unshift($this->indentTypes, 'block');
        }
    }

    /**
     * Adds a new line break if needed.
     *
     * @param string $tab
     *
     * @return bool
     */
    private function addNewLineBreak($tab)
    {
        $addedNewline = false;
        if ($this->newline) {
            $this->formattedSql .= "\n" . str_repeat($tab, $this->indentLvl);
            $this->newline = false;
            $addedNewline  = true;
        }

        return $addedNewline;
    }

    /**
     * @param $token
     *
     * @return bool
     */
    private function stringHasCommentToken($token)
    {
        return
            $token[Tokenizer::TOKEN_TYPE] === Tokenizer::TOKEN_TYPE_COMMENT
            || $token[Tokenizer::TOKEN_TYPE] === Tokenizer::TOKEN_TYPE_BLOCK_COMMENT;
    }

    /**
     * @param $token
     * @param string $tab
     * @param $queryValue
     *
     * @return string
     */
    private function writeCommentBlock($token, $tab, $queryValue)
    {
        if ($token[Tokenizer::TOKEN_TYPE] === Tokenizer::TOKEN_TYPE_BLOCK_COMMENT) {
            $indent = str_repeat($tab, $this->indentLvl);

            $this->formattedSql .= "\n" . $indent;
            $queryValue = str_replace("\n", "\n" . $indent, $queryValue);
        }

        $this->formattedSql .= $queryValue;
        $this->newline = true;

        return $this->formattedSql;
    }

    /**
     * @param $token
     *
     * @return bool
     */
    private function stringIsClosingParentheses($token)
    {
        return $token[Tokenizer::TOKEN_VALUE] === ')';
    }

    /**
     * @param string $tab
     * @param $queryValue
     */
    private function writeInlineParenthesesBlock($tab, $queryValue)
    {
        $this->formattedSql = rtrim($this->formattedSql, ' ');

        if ($this->inlineIndented) {
            array_shift($this->indentTypes);
            $this->indentLvl--;
            $this->formattedSql .= "\n" . str_repeat($tab, $this->indentLvl);
        }

        $this->inlineParentheses = false;
        $this->formattedSql .= $queryValue . ' ';
    }

    /**
     * @param $token
     */
    private function writeNewLineForLongCommaInlineValues($token)
    {
        if ($token[Tokenizer::TOKEN_VALUE] === ',') {
            if ($this->inlineCount >= 30) {
                $this->inlineCount = 0;
                $this->newline     = true;
            }
        }
    }

    /**
     * @param $token
     *
     * @return bool
     */
    private function stringIsOpeningParentheses($token)
    {
        return $token[Tokenizer::TOKEN_VALUE] === '(';
    }

    /**
     *
     */
    private function writeNewInlineParentheses()
    {
        $this->inlineParentheses = true;
        $this->inlineCount       = 0;
        $this->inlineIndented    = false;
    }

    /**
     * @param $token
     *
     * @return bool
     */
    private function invalidParenthesesTokenValue($token)
    {
        return
            $token[Tokenizer::TOKEN_VALUE] === ';'
            || $token[Tokenizer::TOKEN_VALUE] === '(';
    }

    /**
     * @param $token
     *
     * @return bool
     */
    private function invalidParenthesesTokenType($token)
    {
        return
            $token[Tokenizer::TOKEN_TYPE] === Tokenizer::TOKEN_TYPE_RESERVED_TOP_LEVEL
            || $token[Tokenizer::TOKEN_TYPE] === Tokenizer::TOKEN_TYPE_RESERVED_NEWLINE
            || $token[Tokenizer::TOKEN_TYPE] === Tokenizer::TOKEN_TYPE_COMMENT
            || $token[Tokenizer::TOKEN_TYPE] === Tokenizer::TOKEN_TYPE_BLOCK_COMMENT;
    }

    /**
     * @param integer $length
     */
    private function writeNewLineForLongInlineValues($length)
    {
        if ($this->inlineParentheses && $length > 30) {
            $this->increaseBlockIndent = true;
            $this->inlineIndented      = true;
            $this->newline             = true;
        }
    }

    /**
     * @param $originalTokens
     * @param $token
     *
     * @return bool
     */
    private function isPrecedingCurrentTokenOfTokenTypeWhiteSpace($originalTokens, $token)
    {
        return isset($originalTokens[$token['i'] - 1])
        && $originalTokens[$token['i'] - 1][Tokenizer::TOKEN_TYPE] !== Tokenizer::TOKEN_TYPE_WHITESPACE;
    }

    /**
     * Adds a new line break for an opening parentheses for a non-inline expression.
     */
    private function addNewLineAfterOpeningParentheses()
    {
        if (!$this->inlineParentheses) {
            $this->increaseBlockIndent = true;
            $this->newline             = true;
        }
    }

    /**
     * Closing parentheses decrease the block indent level.
     */
    private function decreaseIndentLevelUntilIndentTypeIsSpecial()
    {
        $this->formattedSql = rtrim($this->formattedSql, ' ');
        $this->indentLvl--;

        while ($j = array_shift($this->indentTypes)) {
            if ($j === 'special') {
                $this->indentLvl--;
            } else {
                break;
            }
        }
    }

    /**
     * @param boolean $addedNewline
     * @param string $tab
     */
    private function addNewLineBeforeClosingParentheses($addedNewline, $tab)
    {
        if (!$addedNewline) {
            $this->formattedSql .= "\n" . str_repeat($tab, $this->indentLvl);
        }
    }

    /**
     * @param $token
     *
     * @return bool
     */
    private function isTokenTypeReservedTopLevel($token)
    {
        return $token[Tokenizer::TOKEN_TYPE] === Tokenizer::TOKEN_TYPE_RESERVED_TOP_LEVEL;
    }

    /**
     *
     */
    private function decreaseSpecialIndentIfCurrentIndentTypeIsSpecial()
    {
        reset($this->indentTypes);

        if (current($this->indentTypes) === 'special') {
            $this->indentLvl--;
            array_shift($this->indentTypes);
        }
    }

    /**
     * @param boolean $addedNewline
     * @param string $tab
     */
    private function writeNewLineBecauseOfTopLevelReservedWord($addedNewline, $tab)
    {
        // Add a newline before the top level reserved word if necessary and indent.
        $this->formattedSql = (!$addedNewline) ? $this->formattedSql . "\n" : rtrim($this->formattedSql, $tab);
        $this->formattedSql .= str_repeat($tab, $this->indentLvl);

        // Add a newline after the top level reserved word
        $this->newline = true;
    }

    /**
     * @param $token
     *
     * @return bool
     */
    private function tokenHasExtraWhiteSpaces($token)
    {
        return strpos($token[Tokenizer::TOKEN_VALUE], ' ') !== false
        || strpos($token[Tokenizer::TOKEN_VALUE], "\n") !== false
        || strpos($token[Tokenizer::TOKEN_VALUE], "\t") !== false;
    }

    /**
     * @param $token
     */
    private function tokenHasLimitClause($token)
    {
        if ($token[Tokenizer::TOKEN_VALUE] === 'LIMIT' && !$this->inlineParentheses) {
            $this->clauseLimit = true;
        }
    }

    /**
     * @param $token
     *
     * @return bool
     */
    private function stringIsEndOfLimitClause($token)
    {
        return
            $this->clauseLimit
            && $token[Tokenizer::TOKEN_VALUE] !== ","
            && $token[Tokenizer::TOKEN_TYPE] !== Tokenizer::TOKEN_TYPE_NUMBER
            && $token[Tokenizer::TOKEN_TYPE] !== Tokenizer::TOKEN_TYPE_WHITESPACE;
    }

    /**
     * Commas start a new line unless they are found within inline parentheses or SQL 'LIMIT' clause.
     * If the previous TOKEN_VALUE is 'LIMIT', undo new line.
     */
    private function writeNewLineBecauseOfComma()
    {
        $this->newline = true;

        if ($this->clauseLimit === true) {
            $this->newline     = false;
            $this->clauseLimit = false;
        }
    }

    /**
     * @param $token
     *
     * @return bool
     */
    private function isTokenTypeReservedNewLine($token)
    {
        return $token[Tokenizer::TOKEN_TYPE] === Tokenizer::TOKEN_TYPE_RESERVED_NEWLINE;
    }

    /**
     * @param boolean $addedNewline
     * @param string $tab
     */
    private function writeNewLineBeforeReservedWord($addedNewline, $tab)
    {
        if (!$addedNewline) {
            $this->formattedSql .= "\n" . str_repeat($tab, $this->indentLvl);
        }
    }

    /**
     * @param $token
     * @param $tokens
     * @param $i
     * @param $originalTokens
     *
     * @return bool
     */
    private function tokenHasMultipleBoundaryCharactersTogether($token, &$tokens, $i, &$originalTokens)
    {
        return
            $token[Tokenizer::TOKEN_TYPE] === Tokenizer::TOKEN_TYPE_BOUNDARY
            && isset($tokens[$i - 1])
            && $tokens[$i - 1][Tokenizer::TOKEN_TYPE] === Tokenizer::TOKEN_TYPE_BOUNDARY
            && isset($originalTokens[$token['i'] - 1])
            && $originalTokens[$token['i'] - 1][Tokenizer::TOKEN_TYPE] !== Tokenizer::TOKEN_TYPE_WHITESPACE;
    }

    /**
     * @param $token
     *
     * @return bool
     */
    private function tokenHasExtraWhiteSpaceLeft($token)
    {
        return
            $token[Tokenizer::TOKEN_VALUE] === '.'
            || $token[Tokenizer::TOKEN_VALUE] === ','
            || $token[Tokenizer::TOKEN_VALUE] === ';';
    }

    /**
     * @param $token
     *
     * @return bool
     */
    private function tokenHasExtraWhiteSpaceRight($token)
    {
        return
            $token[Tokenizer::TOKEN_VALUE] === '('
            || $token[Tokenizer::TOKEN_VALUE] === '.';
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
        return $token[Tokenizer::TOKEN_VALUE] === '-'
        && isset($tokens[$i + 1])
        && $tokens[$i + 1][Tokenizer::TOKEN_TYPE] === Tokenizer::TOKEN_TYPE_NUMBER
        && isset($tokens[$i - 1]);
    }

    /**
     * @param $tokenType
     *
     * @return bool
     */
    private function tokenIsNumberAndHasExtraWhiteSpaceRight($tokenType)
    {
        return
            $tokenType !== Tokenizer::TOKEN_TYPE_QUOTE
            && $tokenType !== Tokenizer::TOKEN_TYPE_BACK_TICK_QUOTE
            && $tokenType !== Tokenizer::TOKEN_TYPE_WORD
            && $tokenType !== Tokenizer::TOKEN_TYPE_NUMBER;
    }
}
