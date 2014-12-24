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
     * @param Tokenizer $tokenizer
     * @param string $string
     * @param array $matches
     * @param array|null $previous
     *
     * @return array
     */
    public static function isReserved(Tokenizer $tokenizer, $string, array &$matches, $previous)
    {
        $tokenData = [];

        if (!$tokenizer->getNextToken() && self::isReservedPrecededByDotCharacter($previous)) {
            self::getReservedString(
                $tokenData,
                Tokenizer::TOKEN_TYPE_RESERVED_TOP_LEVEL,
                $string,
                $matches,
                $tokenizer->getRegexReservedTopLevel(),
                $tokenizer->getRegexBoundaries()
            );

            self::getReservedString(
                $tokenData,
                Tokenizer::TOKEN_TYPE_RESERVED_NEWLINE,
                strtoupper($string),
                $matches,
                $tokenizer->getRegexReservedNewLine(),
                $tokenizer->getRegexBoundaries()
            );

            self::getReservedString(
                $tokenData,
                Tokenizer::TOKEN_TYPE_RESERVED,
                $string,
                $matches,
                $tokenizer->getRegexReserved(),
                $tokenizer->getRegexBoundaries()
            );

            $tokenizer->setNextToken($tokenData);
        }
    }

    /**
     * A reserved word cannot be preceded by a "." in order to differentiate "mytable.from" from the token "from".
     *
     * @param $previous
     *
     * @return bool
     */
    protected static function isReservedPrecededByDotCharacter($previous)
    {
        return !$previous || !isset($previous[Tokenizer::TOKEN_VALUE]) || $previous[Tokenizer::TOKEN_VALUE] !== '.';
    }


    /**
     * @param       string        $upper
     * @param array               $matches
     * @param              string $regexReserved
     * @param              string $regexBoundaries
     *
     * @return bool
     */
    protected static function isReservedString($upper, array &$matches, $regexReserved, $regexBoundaries)
    {
        return 1 == preg_match(
            '/^(' . $regexReserved . ')($|\s|' . $regexBoundaries . ')/',
            strtoupper($upper),
            $matches
        );
    }

    /**
     * @param              string $type
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
     * @param array $tokenData
     * @param string string
     * @param string $string
     * @param array $matches
     * @param string string
     * @param string string
     *
     * @return array
     */
    protected static function getReservedString(array &$tokenData, $type, $string, array &$matches, $regex, $boundaries)
    {
        if (empty($tokenData) && self::isReservedString($string, $matches, $regex, $boundaries)) {
            $tokenData = self::getStringTypeArray($type, $string, $matches);
        }
    }
}
