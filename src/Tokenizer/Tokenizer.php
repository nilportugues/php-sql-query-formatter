<?php
/**
 * Author: Nil Portugués Calderó <contact@nilportugues.com>
 * Date: 6/26/14
 * Time: 12:10 AM
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NilPortugues\SqlQueryFormatter\Tokenizer;

use NilPortugues\SqlQueryFormatter\Helper\Token;
use NilPortugues\SqlQueryFormatter\Tokenizer\Parser\Boundary;
use NilPortugues\SqlQueryFormatter\Tokenizer\Parser\Comment;
use NilPortugues\SqlQueryFormatter\Tokenizer\Parser\Numeral;
use NilPortugues\SqlQueryFormatter\Tokenizer\Parser\Quoted;
use NilPortugues\SqlQueryFormatter\Tokenizer\Parser\Reserved;
use NilPortugues\SqlQueryFormatter\Tokenizer\Parser\String;
use NilPortugues\SqlQueryFormatter\Tokenizer\Parser\UserDefined;
use NilPortugues\SqlQueryFormatter\Tokenizer\Parser\WhiteSpace;

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
    protected $regexBoundaries;

    /**
     * @var string
     */
    protected $regexReserved;

    /**
     * @var string
     */
    protected $regexReservedNewLine;

    /**
     * @var string
     */
    protected $regexReservedTopLevel;

    /**
     * @var string
     */
    protected $regexFunction;

    /**
     * @var int
     */
    protected $maxCacheKeySize = 15;

    /**
     * @var array
     */
    protected $tokenCache = [];

    /**
     * @var array
     */
    protected $nextToken = [];


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
    protected function initRegex($variable)
    {
        return '(' . implode('|', array_map(array($this, 'quoteRegex'), $variable)) . ')';
    }

    /**
     * Takes a SQL string and breaks it into tokens.
     * Each token is an associative array with type and value.
     *
     * @param string $string
     *
     * @return array
     */
    public function tokenize($string)
    {
        $tokens = [];

        if (strlen($string) > 0) {
            $token               = null;
            $currentStringLength = strlen($string);
            $oldStringLength     = strlen($string) + 1;

            while ($currentStringLength >= 0) {
                if ($oldStringLength <= $currentStringLength) {
                    break;
                }

                $token = $this->getToken($string, $currentStringLength, $token);
                $tokens[]    = $token;
                $tokenLength = strlen($token[self::TOKEN_VALUE]);

                $oldStringLength = $currentStringLength;
                $currentStringLength -= $tokenLength;

                $string = substr($string, $tokenLength);
            }
        }

        return $tokens;
    }

    /**
     * @param string  $string
     * @param integer $currentStringLength
     *
     * @return string
     */
    protected function useTokenCache($string, $currentStringLength)
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
    protected function getNextTokenFromCache($cacheKey)
    {
        return $this->tokenCache[$cacheKey];
    }

    /**
     * Get the next token and the token type and store it in cache.
     *
     * @param string $string
     * @param        $token
     * @param string $cacheKey
     *
     * @return array
     */
    protected function getNextTokenFromString($string, $token, $cacheKey)
    {
        $token = $this->parseNextToken($string, $token);

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
     * @param array  $previous The result of the previous parseNextToken() call
     *
     * @return array An associative array containing the type and value of the token.
     */
    protected function parseNextToken($string, $previous = null)
    {
        $matches         = [];
        $this->nextToken = [];

        WhiteSpace::isWhiteSpace($this, $string, $matches);
        Comment::isComment($this, $string);
        Quoted::isQuoted($this, $string);
        UserDefined::isUserDefinedVariable($this, $string);
        Numeral::isNumeral($this, $string, $matches);
        Boundary::isBoundary($this, $string, $matches);
        Reserved::isReserved($this, $string, $matches, $previous);
        String::isFunction($this, $string, $matches);
        String::getNonReservedString($this, $string, $matches);

        return $this->nextToken;
    }

    /**
     * @return array
     */
    public function getNextToken()
    {
        return $this->nextToken;
    }

    /**
     * @param array $nextToken
     *
     * @return $this
     */
    public function setNextToken($nextToken)
    {
        $this->nextToken = $nextToken;
        return $this;
    }

    /**
     * @return string
     */
    public function getRegexBoundaries()
    {
        return $this->regexBoundaries;
    }

    /**
     * @return string
     */
    public function getRegexFunction()
    {
        return $this->regexFunction;
    }

    /**
     * @return string
     */
    public function getRegexReserved()
    {
        return $this->regexReserved;
    }

    /**
     * @return string
     */
    public function getRegexReservedNewLine()
    {
        return $this->regexReservedNewLine;
    }

    /**
     * @return string
     */
    public function getRegexReservedTopLevel()
    {
        return $this->regexReservedTopLevel;
    }

    /**
     * Helper function for building regular expressions for reserved words and boundary characters
     *
     * @param string $string
     *
     * @return string
     */
    protected function quoteRegex($string)
    {
        return preg_quote($string, '/');
    }

    /**
     * @param $string
     * @param $currentStringLength
     * @param $token
     *
     * @return array|mixed
     */
    protected function getToken($string, $currentStringLength, $token)
    {
        $cacheKey = $this->useTokenCache($string, $currentStringLength);
        if (!empty($cacheKey) && isset($this->tokenCache[$cacheKey])) {
            $token = $this->getNextTokenFromCache($cacheKey);
        } else {
            $token = $this->getNextTokenFromString($string, $token, $cacheKey);
        }
        return $token;
    }
}
