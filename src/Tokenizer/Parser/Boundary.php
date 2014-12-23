<?php
/**
 * Author: Nil Portugués Calderó <contact@nilportugues.com>
 * Date: 12/23/14
 * Time: 1:34 PM
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NilPortugues\SqlQueryFormatter\Tokenizer\Parser;

use NilPortugues\SqlQueryFormatter\Tokenizer\Tokenizer;

/**
 * Class Boundary
 * @package NilPortugues\SqlQueryFormatter\Tokenizer\Parser
 */
final class Boundary
{
    /**
     * @param       string $string
     * @param array        $matches
     * @param              $regexBoundaries
     *
     * @return bool
     */
    public static function isBoundaryCharacter($string, array &$matches, $regexBoundaries)
    {
        return (1 == preg_match('/^(' . $regexBoundaries . ')/', $string, $matches));
    }

    /**
     * @param array $matches
     *
     * @return array
     */
    public static function getBoundaryCharacter(array &$matches)
    {
        return [
            Tokenizer::TOKEN_VALUE => $matches[1],
            Tokenizer::TOKEN_TYPE  => Tokenizer::TOKEN_TYPE_BOUNDARY
        ];
    }
}
