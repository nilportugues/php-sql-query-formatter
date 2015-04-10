<?php
/**
 * Author: Nil Portugués Calderó <contact@nilportugues.com>
 * Date: 12/23/14
 * Time: 1:22 PM
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NilPortugues\Sql\QueryFormatter\Tokenizer\Parser;

use NilPortugues\Sql\QueryFormatter\Tokenizer\Tokenizer;

/**
 * Class Comment
 * @package NilPortugues\Sql\QueryFormatter\Tokenizer\Parser
 */
final class Comment
{
    /**
     * @param Tokenizer $tokenizer
     * @param           string $string
     */
    public static function isComment(Tokenizer $tokenizer, $string)
    {
        if (!$tokenizer->getNextToken() && Comment::isCommentString($string)) {
            $tokenizer->setNextToken(Comment::getCommentString($string));
        }
    }

    /**
     * @param string $string
     *
     * @return bool
     */
    protected static function isCommentString($string)
    {
        return $string[0] === '#' || self::isTwoCharacterComment($string);
    }

    /**
     * @param string $string
     *
     * @return bool
     */
    protected static function isTwoCharacterComment($string)
    {
        return isset($string[1]) && (self::startsWithDoubleDash($string) || self::startsAsBlock($string));
    }

    /**
     * @param string $string
     *
     * @return bool
     */
    protected static function startsWithDoubleDash($string)
    {
        return $string[0] === '-' && ($string[1] === $string[0]);
    }

    /**
     * @param string $string
     *
     * @return bool
     */
    protected static function startsAsBlock($string)
    {
        return $string[0] === '/' && $string[1] === '*';
    }

    /**
     * @param  string $string
     *
     * @return array
     */
    protected static function getCommentString($string)
    {
        $last = strpos($string, "*/", 2) + 2;
        $type = Tokenizer::TOKEN_TYPE_BLOCK_COMMENT;

        if ($string[0] === '-' || $string[0] === '#') {
            $last = strpos($string, "\n");
            $type = Tokenizer::TOKEN_TYPE_COMMENT;
        }

        $last = ($last === false) ? strlen($string) : $last;

        return [
            Tokenizer::TOKEN_VALUE => substr($string, 0, $last),
            Tokenizer::TOKEN_TYPE  => $type
        ];
    }
}
