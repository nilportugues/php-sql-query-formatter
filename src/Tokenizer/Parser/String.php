<?php
/**
 * Author: Nil Portugués Calderó <contact@nilportugues.com>
 * Date: 12/23/14
 * Time: 1:36 PM
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NilPortugues\SqlQueryFormatter\Tokenizer\Parser;

use NilPortugues\SqlQueryFormatter\Tokenizer\Tokenizer;

/**
 * Class String
 * @package NilPortugues\SqlQueryFormatter\Tokenizer\Parser
 */
final class String
{
    /**
     * A function must be succeeded by '('.
     * This makes it so that a function such as "COUNT(" is considered a function, but "COUNT" alone is not function.
     *
     * @param   string $string
     * @param array    $matches
     * @param          $regexFunction
     *
     * @return bool
     */
    public static function isFunctionString($string, array &$matches, $regexFunction)
    {
        return (1 == preg_match('/^(' . $regexFunction . '[(]|\s|[)])/', strtoupper($string), $matches));
    }

    /**
     * @param       string $string
     * @param array        $matches
     *
     * @return array
     */
    public static function getFunctionString($string, array &$matches)
    {
        return [
            Tokenizer::TOKEN_TYPE  => Tokenizer::TOKEN_TYPE_RESERVED,
            Tokenizer::TOKEN_VALUE => substr($string, 0, strlen($matches[1]) - 1)
        ];
    }

    /**
     * @param string $string
     * @param        $regexBoundaries
     *
     * @return array
     */
    public static function getNonReservedString($string, $regexBoundaries)
    {
        $data    = [];
        $matches = [];

        if (1 == preg_match('/^(.*?)($|\s|["\'`]|' . $regexBoundaries . ')/', $string, $matches)) {
            $data = [
                Tokenizer::TOKEN_VALUE => $matches[1],
                Tokenizer::TOKEN_TYPE  => Tokenizer::TOKEN_TYPE_WORD
            ];
        }

        return $data;
    }
}
