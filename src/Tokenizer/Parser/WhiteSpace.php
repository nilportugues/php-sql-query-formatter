<?php
/**
 * Author: Nil Portugués Calderó <contact@nilportugues.com>
 * Date: 12/23/14
 * Time: 1:19 PM
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NilPortugues\SqlQueryFormatter\Tokenizer\Parser;

use NilPortugues\SqlQueryFormatter\Tokenizer\Tokenizer;

/**
 * Class WhiteSpace
 * @package NilPortugues\SqlQueryFormatter\Tokenizer
 */
final class WhiteSpace
{
    /**
     * @param       string $string
     * @param array        $matches
     *
     * @return bool
     */
    public static function isWhiteSpaceString($string, array &$matches)
    {
        return (1 == preg_match('/^\s+/', $string, $matches));
    }

    /**
     * @param array $matches
     *
     * @return array
     */
    public static function getWhiteSpaceString(array &$matches)
    {
        return [
            Tokenizer::TOKEN_VALUE => $matches[0],
            Tokenizer::TOKEN_TYPE  => Tokenizer::TOKEN_TYPE_WHITESPACE
        ];
    }
}
