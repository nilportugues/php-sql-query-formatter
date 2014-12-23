<?php
/**
 * Author: Nil Portugués Calderó <contact@nilportugues.com>
 * Date: 12/23/14
 * Time: 1:18 PM
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NilPortugues\SqlQueryFormatter\Tokenizer\Parser;

use NilPortugues\SqlQueryFormatter\Tokenizer\Tokenizer;

/**
 * Class Reserved
 * @package NilPortugues\SqlQueryFormatter\Tokenizer
 */
final class Reserved
{
    /**
     * A reserved word cannot be preceded by a "." in order to differentiate "mytable.from" from the token "from".
     *
     * @param $previous
     *
     * @return bool
     */
    public static function isReservedPrecededByDotCharacter($previous)
    {
        return !$previous || !isset($previous[Tokenizer::TOKEN_VALUE]) || $previous[Tokenizer::TOKEN_VALUE] !== '.';
    }


    /**
     * @param       string $string
     * @param array        $matches
     *
     * @return array
     */
    public static function getReservedTopLevelString($string, array &$matches)
    {
        return [
            Tokenizer::TOKEN_TYPE  => Tokenizer::TOKEN_TYPE_RESERVED_TOP_LEVEL,
            Tokenizer::TOKEN_VALUE => substr($string, 0, strlen($matches[1]))
        ];
    }


    /**
     * @param       string $string
     * @param array        $matches
     *
     * @return array
     */
    public static function getReservedNewLineString($string, array &$matches)
    {
        $string = strtoupper($string);

        return [
            Tokenizer::TOKEN_TYPE  => Tokenizer::TOKEN_TYPE_RESERVED_NEWLINE,
            Tokenizer::TOKEN_VALUE => substr($string, 0, strlen($matches[1]))
        ];
    }

    /**
     * @param       string $upper
     * @param array        $matches
     * @param              string $regexReserved
     * @param              string $regexBoundaries
     *
     * @return bool
     */
    public static function isReservedString($upper, array &$matches, $regexReserved, $regexBoundaries)
    {
        return 1 == preg_match(
            '/^(' . $regexReserved . ')($|\s|' . $regexBoundaries . ')/',
            strtoupper($upper),
            $matches
        );
    }

    /**
     * @param       string $string
     * @param array        $matches
     *
     * @return array
     */
    public static function getReservedString($string, array &$matches)
    {
        return [
            Tokenizer::TOKEN_TYPE  => Tokenizer::TOKEN_TYPE_RESERVED,
            Tokenizer::TOKEN_VALUE => substr($string, 0, strlen($matches[1]))
        ];
    }
}
