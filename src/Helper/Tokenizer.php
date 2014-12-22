<?php
/**
 * Author: Nil Portugués Calderó <contact@nilportugues.com>
 * Date: 6/26/14
 * Time: 12:10 AM
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NilPortugues\SqlQueryFormatter\Helper;

/**
 * Class Tokenizer
 * @package NilPortugues\SqlQueryFormatter\Helper
 */
class Tokenizer
{
    const TOKEN_TYPE_WHITESPACE         = 0;
    const TOKEN_TYPE_WORD               = 1;
    const TOKEN_TYPE_QUOTE              = 2;
    const TOKEN_TYPE_BACK_TICK_QUOTE    = 3;
    const TOKEN_TYPE_RESERVED           = 4;
    const TOKEN_TYPE_RESERVED_TOP_LEVEL = 5;
    const TOKEN_TYPE_RESERVED_NEWLINE   = 6;
    const TOKEN_TYPE_BOUNDARY           = 7;
    const TOKEN_TYPE_COMMENT            = 8;
    const TOKEN_TYPE_BLOCK_COMMENT      = 9;
    const TOKEN_TYPE_NUMBER             = 10;
    const TOKEN_TYPE_ERROR              = 11;
    const TOKEN_TYPE_VARIABLE           = 12;
    const TOKEN_TYPE                    = 0;
    const TOKEN_VALUE                   = 1;

    /**
     * @var string
     */
    private $regexBoundaries;

    /**
     * @var string
     */
    private $regexReserved;

    /**
     * @var string
     */
    private $regexReservedNewLine;

    /**
     * @var string
     */
    private $regexReservedTopLevel;

    /**
     * @var string
     */
    private $regexFunction;

    /**
     * @var int
     */
    private $maxCacheKeySize = 15;

    /**
     * @var array
     */
    private $tokenCache = array();

    /**
     * Builds all the regular expressions needed to Tokenize the input.
     */
    public function __construct()
    {
        $reservedMap = array_combine(Token::$reserved, array_map('strlen', Token::$reserved));
        arsort($reservedMap);
        Token::$reserved = array_keys($reservedMap);

        $this->regexFunction         = $this->initRegex(Token::$functions);
        $this->regexBoundaries       = $this->initRegex(Token::$boundaries);
        $this->regexReserved         = $this->initRegex(Token::$reserved);
        $this->regexReservedTopLevel = str_replace(' ', '\\s+', $this->initRegex(Token::$reservedTopLevel));
        $this->regexReservedNewLine  = str_replace(' ', '\\s+', $this->initRegex(Token::$reservedNewLine));
    }

    /**
     * @param $variable
     *
     * @return string
     */
    private function initRegex($variable)
    {
        return '(' . implode('|', array_map(array($this, 'quoteRegex'), $variable)) . ')';
    }

    /**
     * Takes a SQL string and breaks it into tokens.
     * Each token is an associative array with type and value.
     *
     * @param String $string The SQL string
     *
     * @return Array An array of tokens.
     */
    public function tokenize($string)
    {
        $tokens = array();

        if (strlen($string)>0) {
            $token               = null;
            $currentStringLength = strlen($string);
            $oldStringLength     = strlen($string) + 1;

            while ($currentStringLength >= 0) {
                if ($oldStringLength <= $currentStringLength) {
                    break;
                }

                $oldStringLength = $currentStringLength;

                $cacheKey = $this->useTokenCache($string, $currentStringLength);
                if (!empty($cacheKey) && isset($this->tokenCache[$cacheKey])) {
                    $token = $this->getNextTokenFromCache($cacheKey);
                } else {
                    $token = $this->getNextTokenFromString($string, $token, $cacheKey);
                }

                $tokens[]    = $token;
                $tokenLength = strlen($token[self::TOKEN_VALUE]);
                $currentStringLength -= $tokenLength;

                $string = substr($string, $tokenLength);
            }
        }

        return $tokens;
    }

    /**
     * @param string $string
     * @param integer $currentStringLength
     *
     * @return string
     */
    private function useTokenCache($string, $currentStringLength)
    {
        $cacheKey = '';

        if ($currentStringLength >= $this->maxCacheKeySize) {
            $cacheKey = substr($string, 0, $this->maxCacheKeySize);
        }

        return $cacheKey;
    }

    /**
     * @param string $cacheKey
     *
     * @return mixed
     */
    private function getNextTokenFromCache($cacheKey)
    {
        return $this->tokenCache[$cacheKey];
    }

    /**
     * Get the next token and the token type and store it in cache.
     *
     * @param string $string
     * @param $token
     * @param string $cacheKey
     *
     * @return array
     */
    private function getNextTokenFromString($string, $token, $cacheKey)
    {
        $token = $this->getNextToken($string, $token);

        if ($cacheKey && strlen($token[self::TOKEN_VALUE]) < $this->maxCacheKeySize) {
            $this->tokenCache[$cacheKey] = $token;
        }

        return $token;
    }

    /**
     * Return the next token and token type in a SQL string.
     * Quoted strings, comments, reserved words, whitespace, and punctuation are all their own tokens.
     *
     * @param string $string   The SQL string
     * @param array  $previous The result of the previous getNextToken() call
     *
     * @return array An associative array containing the type and value of the token.
     */
    private function getNextToken($string, $previous = null)
    {
        $matches = array();

        if ($this->isWhiteSpaceString($string, $matches)) {
            return $this->getWhiteSpaceString($matches);
        }

        if ($this->isCommentString($string)) {
            return $this->getCommentString($string);
        }

        if ($this->isQuotedString($string)) {
            return $this->getQuotedString($string);
        }

        if ($this->isUserDefinedVariableString($string)) {
            return $this->getUserDefinedVariableString($string);
        }

        if ($this->isNumeralString($string, $matches)) {
            return $this->getNumeralString($matches);
        }

        if ($this->isBoundaryCharacter($string, $matches)) {
            return $this->getBoundaryCharacter($matches);
        }

        if ($this->isReservedPrecededByDotCharacter($previous)) {
            if ($this->isReservedTopLevelString($string, $matches)) {
                return $this->getReservedTopLevelString($string, $matches);
            }

            if ($this->isReservedNewLineString($string, $matches)) {
                return $this->getReservedNewLineString($string, $matches);
            }

            if ($this->isReservedString($string, $matches)) {
                return $this->getReservedString($string, $matches);
            }
        }

        if ($this->isFunctionString($string, $matches)) {
            return $this->getFunctionString($string, $matches);
        }

        return $this->getNonReservedString($string);
    }

    /**
     * @param       string $string
     * @param array $matches
     *
     * @return bool
     */
    private function isWhiteSpaceString($string, array &$matches)
    {
        return (1 == preg_match('/^\s+/', $string, $matches));
    }

    /**
     * @param array $matches
     *
     * @return array
     */
    private function getWhiteSpaceString(array &$matches)
    {
        return array(self::TOKEN_VALUE => $matches[0], self::TOKEN_TYPE => self::TOKEN_TYPE_WHITESPACE);
    }

    /**
     * @param string $string
     *
     * @return bool
     */
    private function isCommentString($string)
    {
        return
            $string[0] === '#'
            || (
                isset($string[1])
                && ($string[0] === '-' && $string[1] === '-')
                || ($string[0] === '/' && $string[1] === '*')
            );
    }

    /**
     * @param  string $string
     *
     * @return array
     */
    private function getCommentString($string)
    {
        if ($string[0] === '-' || $string[0] === '#') {
            // Comment until end of line
            $last = strpos($string, "\n");
            $type = self::TOKEN_TYPE_COMMENT;
        } else {
            // Comment until closing comment tag
            $last = strpos($string, "*/", 2) + 2;
            $type = self::TOKEN_TYPE_BLOCK_COMMENT;
        }

        $last = ($last === false) ? strlen($string) : $last;

        return array(
            self::TOKEN_VALUE => substr($string, 0, $last),
            self::TOKEN_TYPE  => $type
        );
    }

    /**
     * @param string $string
     *
     * @return bool
     */
    private function isQuotedString($string)
    {
        return
            $string[0] === '"'
            || $string[0] === '\''
            || $string[0] === '`'
            || $string[0] === '[';
    }

    /**
     * @param string $string
     *
     * @return array
     */
    private function getQuotedString($string)
    {
        $tokenType = self::TOKEN_TYPE_QUOTE;

        if ($string[0] === '`' || $string[0] === '[') {
            $tokenType = self::TOKEN_TYPE_BACK_TICK_QUOTE;
        }

        return array(
            self::TOKEN_TYPE  => $tokenType,
            self::TOKEN_VALUE => $this->wrapStringWithQuotes($string)
        );
    }

    /**
     *  This checks for the following patterns:
     *  1. backtick quoted string using `` to escape
     *  2. square bracket quoted string (SQL Server) using ]] to escape
     *  3. double quoted string using "" or \" to escape
     *  4. single quoted string using '' or \' to escape
     *
     * @param string $string
     *
     * @return null
     */
    private function wrapStringWithQuotes($string)
    {
        $returnString = null;

        $regex = '/^(((`[^`]*($|`))+)|((\[[^\]]*($|\]))(\][^\]]*($|\]))*)|' .
            '(("[^"\\\\]*(?:\\\\.[^"\\\\]*)*("|$))+)|((\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*(\'|$))+))/s';

        if (1 == preg_match($regex, $string, $matches)) {
            $returnString = $matches[1];
        }

        return $returnString;
    }

    /**
     * @param string $string
     *
     * @return bool
     */
    private function isUserDefinedVariableString(&$string)
    {
        return $string[0] === '@' && isset($string[1]);
    }

    /**
     * Gets the user defined variables for in quoted or non-quoted fashion.
     *
     * @param string $string
     *
     * @return array
     */
    private function getUserDefinedVariableString(&$string)
    {
        $returnData = array(
            self::TOKEN_VALUE => null,
            self::TOKEN_TYPE  => self::TOKEN_TYPE_VARIABLE
        );

        if ($string[1] === '"' || $string[1] === '\'' || $string[1] === '`') {
            $returnData[self::TOKEN_VALUE] = '@' . $this->wrapStringWithQuotes(substr($string, 1));
        } else {
            $matches = array();
            preg_match('/^(@[a-zA-Z0-9\._\$]+)/', $string, $matches);
            if ($matches) {
                $returnData[self::TOKEN_VALUE] = $matches[1];
            }
        }

        return $returnData;
    }

    /**
     * @param       string $string
     * @param array $matches
     *
     * @return bool
     */
    private function isNumeralString($string, array &$matches)
    {
        return (1 == preg_match(
            '/^([0-9]+(\.[0-9]+)?|0x[0-9a-fA-F]+|0b[01]+)($|\s|"\'`|' . $this->regexBoundaries . ')/',
            $string,
            $matches
        ));
    }

    /**
     * @param array $matches
     *
     * @return array
     */
    private function getNumeralString(array &$matches)
    {
        return array(self::TOKEN_VALUE => $matches[1], self::TOKEN_TYPE => self::TOKEN_TYPE_NUMBER);
    }

    /**
     * @param       string $string
     * @param array $matches
     *
     * @return bool
     */
    private function isBoundaryCharacter($string, array &$matches)
    {
        return (1 == preg_match('/^(' . $this->regexBoundaries . ')/', $string, $matches));
    }

    /**
     * @param array $matches
     *
     * @return array
     */
    private function getBoundaryCharacter(array &$matches)
    {
        return array(self::TOKEN_VALUE => $matches[1], self::TOKEN_TYPE => self::TOKEN_TYPE_BOUNDARY);
    }

    /**
     * A reserved word cannot be preceded by a "." in order to differentiate "mytable.from" from the token "from".
     *
     * @param $previous
     *
     * @return bool
     */
    private function isReservedPrecededByDotCharacter($previous)
    {
        return
            !$previous
            || !isset($previous[self::TOKEN_VALUE])
            || $previous[self::TOKEN_VALUE] !== '.';
    }

    /**
     * @param       string $string
     * @param array $matches
     *
     * @return bool
     */
    private function isReservedTopLevelString($string, array &$matches)
    {
        return 1 == preg_match(
            '/^(' . $this->regexReservedTopLevel . ')($|\s|' . $this->regexBoundaries . ')/',
            strtoupper($string),
            $matches
        );
    }

    /**
     * @param       string $string
     * @param array $matches
     *
     * @return array
     */
    private function getReservedTopLevelString($string, array &$matches)
    {
        return array(
            self::TOKEN_TYPE  => self::TOKEN_TYPE_RESERVED_TOP_LEVEL,
            self::TOKEN_VALUE => substr($string, 0, strlen($matches[1]))
        );
    }

    /**
     * @param string $string
     * @param $matches
     *
     * @return bool
     */
    private function isReservedNewLineString($string, &$matches)
    {
        return 1 == preg_match(
            '/^(' . $this->regexReservedNewLine . ')($|\s|' . $this->regexBoundaries . ')/',
            strtoupper($string),
            $matches
        );
    }

    /**
     * @param       string $string
     * @param array $matches
     *
     * @return array
     */
    private function getReservedNewLineString($string, array &$matches)
    {
        $string = strtoupper($string);

        return array(
            self::TOKEN_TYPE  => self::TOKEN_TYPE_RESERVED_NEWLINE,
            self::TOKEN_VALUE => substr($string, 0, strlen($matches[1]))
        );
    }

    /**
     * @param       string $upper
     * @param array $matches
     *
     * @return bool
     */
    private function isReservedString($upper, array &$matches)
    {
        return 1 == preg_match(
            '/^(' . $this->regexReserved . ')($|\s|' . $this->regexBoundaries . ')/',
            strtoupper($upper),
            $matches
        );
    }

    /**
     * @param       string $string
     * @param array $matches
     *
     * @return array
     */
    private function getReservedString($string, array &$matches)
    {
        return array(
            self::TOKEN_TYPE  => self::TOKEN_TYPE_RESERVED,
            self::TOKEN_VALUE => substr($string, 0, strlen($matches[1]))
        );
    }

    /**
     * A function must be succeeded by '('.
     * This makes it so that a function such as "COUNT(" is considered a function, but "COUNT" alone is not function.
     *
     * @param   string $string
     *
     * @param array $matches
     *
     * @return bool
     */
    private function isFunctionString($string, array &$matches)
    {
        return (1 == preg_match('/^(' . $this->regexFunction . '[(]|\s|[)])/', strtoupper($string), $matches));
    }

    /**
     * @param       string $string
     * @param array $matches
     *
     * @return array
     */
    private function getFunctionString($string, array &$matches)
    {
        return array(
            self::TOKEN_TYPE  => self::TOKEN_TYPE_RESERVED,
            self::TOKEN_VALUE => substr($string, 0, strlen($matches[1]) - 1)
        );
    }

    /**
     * @param string $string
     *
     * @return array
     */
    private function getNonReservedString($string)
    {
        $data    = array();
        $matches = array();

        if (1 == preg_match('/^(.*?)($|\s|["\'`]|' . $this->regexBoundaries . ')/', $string, $matches)) {
            $data = array(
                self::TOKEN_VALUE => $matches[1],
                self::TOKEN_TYPE  => self::TOKEN_TYPE_WORD
            );
        }

        return $data;
    }

    /**
     * Helper function for building regular expressions for reserved words and boundary characters
     *
     * @param string $string
     *
     * @return string
     */
    private function quoteRegex($string)
    {
        return preg_quote($string, '/');
    }
}
