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
     * @param array  $matches
     * @param string $previous
     * @param string $string
     * @param string $reservedTopLevel
     * @param string $reservedNewLine
     * @param string $boundaries
     * @param string $reserved
     *
     * @return array
     */
    public static function isReserved(
        array &$matches,
        $previous,
        $string,
        $reservedTopLevel,
        $reservedNewLine,
        $boundaries,
        $reserved
    ) {
        $reservedArray = [];

        if (Reserved::isReservedPrecededByDotCharacter($previous)) {
            Reserved::getReservedTopLevelString($reservedArray, $string, $matches, $reservedTopLevel, $boundaries);
            Reserved::getReservedNewLineString($reservedArray, $string, $matches, $reservedNewLine, $boundaries);
            Reserved::getReservedString($reservedArray, $string, $matches, $reserved, $boundaries);
        }

        return $reservedArray;
    }

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
     * @param array        $reservedArray
     * @param       string $string
     * @param array        $matches
     * @param              $reservedTopLevel
     * @param              $boundaries
     *
     * @return array
     */
    public static function getReservedTopLevelString(
        array &$reservedArray,
        $string,
        array &$matches,
        $reservedTopLevel,
        $boundaries
    ) {
        if (empty($reservedArray) && Reserved::isReservedString($string, $matches, $reservedTopLevel, $boundaries)) {
            $reservedArray = self::getStringTypeArray(Tokenizer::TOKEN_TYPE_RESERVED_TOP_LEVEL, $string, $matches);
        }
    }

    /**
     * @param       string        $upper
     * @param array               $matches
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
     * @param              $type
     * @param       string $string
     * @param array        $matches
     *
     * @return array
     */
    protected static function getStringTypeArray($type, $string, array &$matches)
    {
        return [
            Tokenizer::TOKEN_TYPE  => $type,
            Tokenizer::TOKEN_VALUE => substr($string, 0, strlen($matches[1]))
        ];
    }

    /**
     * @param array        $reservedArray
     * @param       string $string
     * @param array        $matches
     * @param              $reservedNewLine
     * @param              $boundaries
     *
     * @return array
     */
    public static function getReservedNewLineString(
        array &$reservedArray,
        $string,
        array &$matches,
        $reservedNewLine,
        $boundaries
    ) {
        if (empty($reservedArray) && Reserved::isReservedString($string, $matches, $reservedNewLine, $boundaries)) {
            $reservedArray = self::getStringTypeArray(
                Tokenizer::TOKEN_TYPE_RESERVED_NEWLINE,
                strtoupper($string),
                $matches
            );
        }
    }

    /**
     * @param array        $reservedArray
     * @param string       $string
     * @param array        $matches
     * @param              $reserved
     * @param              $boundaries
     *
     * @return array
     */
    public static function getReservedString(array &$reservedArray, $string, array &$matches, $reserved, $boundaries)
    {
        if (empty($reservedArray) && Reserved::isReservedString($string, $matches, $reserved, $boundaries)) {
            $reservedArray = self::getStringTypeArray(Tokenizer::TOKEN_TYPE_RESERVED, $string, $matches);
        }
    }
}
