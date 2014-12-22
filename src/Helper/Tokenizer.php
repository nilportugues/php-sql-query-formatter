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

    private $reserved = array(
        'ACCESSIBLE',
        'ACTION',
        'AGAINST',
        'AGGREGATE',
        'ALGORITHM',
        'ALL',
        'ALTER',
        'ANALYSE',
        'ANALYZE',
        'AS',
        'ASC',
        'AUTOCOMMIT',
        'AUTO_INCREMENT',
        'BACKUP',
        'BEGIN',
        'BETWEEN',
        'BINLOG',
        'BOTH',
        'CASCADE',
        'CASE',
        'CHANGE',
        'CHANGED',
        'CHARACTER SET',
        'CHARSET',
        'CHECK',
        'CHECKSUM',
        'COLLATE',
        'COLLATION',
        'COLUMN',
        'COLUMNS',
        'COMMENT',
        'COMMIT',
        'COMMITTED',
        'COMPRESSED',
        'CONCURRENT',
        'CONSTRAINT',
        'CONTAINS',
        'CONVERT',
        'CREATE',
        'CROSS',
        'CURRENT_TIMESTAMP',
        'DATABASE',
        'DATABASES',
        'DAY',
        'DAY_HOUR',
        'DAY_MINUTE',
        'DAY_SECOND',
        'DEFAULT',
        'DEFINER',
        'DELAYED',
        'DELETE',
        'DESC',
        'DESCRIBE',
        'DETERMINISTIC',
        'DISTINCT',
        'DISTINCTROW',
        'DIV',
        'DO',
        'DUMPFILE',
        'DUPLICATE',
        'DYNAMIC',
        'ELSE',
        'ENCLOSED',
        'END',
        'ENGINE',
        'ENGINE_TYPE',
        'ENGINES',
        'ESCAPE',
        'ESCAPED',
        'EVENTS',
        'EXEC',
        'EXECUTE',
        'EXISTS',
        'EXPLAIN',
        'EXTENDED',
        'FAST',
        'FIELDS',
        'FILE',
        'FIRST',
        'FIXED',
        'FLUSH',
        'FOR',
        'FORCE',
        'FOREIGN',
        'FULL',
        'FULLTEXT',
        'FUNCTION',
        'GLOBAL',
        'GRANT',
        'GRANTS',
        'GROUP_CONCAT',
        'HEAP',
        'HIGH_PRIORITY',
        'HOSTS',
        'HOUR',
        'HOUR_MINUTE',
        'HOUR_SECOND',
        'IDENTIFIED',
        'IF',
        'IFNULL',
        'IGNORE',
        'IN',
        'INDEX',
        'INDEXES',
        'INFILE',
        'INSERT',
        'INSERT_ID',
        'INSERT_METHOD',
        'INTERVAL',
        'INTO',
        'INVOKER',
        'IS',
        'ISOLATION',
        'KEY',
        'KEYS',
        'KILL',
        'LAST_INSERT_ID',
        'LEADING',
        'LEVEL',
        'LIKE',
        'LINEAR',
        'LINES',
        'LOAD',
        'LOCAL',
        'LOCK',
        'LOCKS',
        'LOGS',
        'LOW_PRIORITY',
        'MARIA',
        'MASTER',
        'MASTER_CONNECT_RETRY',
        'MASTER_HOST',
        'MASTER_LOG_FILE',
        'MATCH',
        'MAX_CONNECTIONS_PER_HOUR',
        'MAX_QUERIES_PER_HOUR',
        'MAX_ROWS',
        'MAX_UPDATES_PER_HOUR',
        'MAX_USER_CONNECTIONS',
        'MEDIUM',
        'MERGE',
        'MINUTE',
        'MINUTE_SECOND',
        'MIN_ROWS',
        'MODE',
        'MODIFY',
        'MONTH',
        'MRG_MYISAM',
        'MYISAM',
        'NAMES',
        'NATURAL',
        'NOT',
        'NOW()',
        'NULL',
        'OFFSET',
        'ON',
        'OPEN',
        'OPTIMIZE',
        'OPTION',
        'OPTIONALLY',
        'ON UPDATE',
        'ON DELETE',
        'OUTFILE',
        'PACK_KEYS',
        'PAGE',
        'PARTIAL',
        'PARTITION',
        'PARTITIONS',
        'PASSWORD',
        'PRIMARY',
        'PRIVILEGES',
        'PROCEDURE',
        'PROCESS',
        'PROCESSLIST',
        'PURGE',
        'QUICK',
        'RANGE',
        'RAID0',
        'RAID_CHUNKS',
        'RAID_CHUNKSIZE',
        'RAID_TYPE',
        'READ',
        'READ_ONLY',
        'READ_WRITE',
        'REFERENCES',
        'REGEXP',
        'RELOAD',
        'RENAME',
        'REPAIR',
        'REPEATABLE',
        'REPLACE',
        'REPLICATION',
        'RESET',
        'RESTORE',
        'RESTRICT',
        'RETURN',
        'RETURNS',
        'REVOKE',
        'RLIKE',
        'ROLLBACK',
        'ROW',
        'ROWS',
        'ROW_FORMAT',
        'SECOND',
        'SECURITY',
        'SEPARATOR',
        'SERIALIZABLE',
        'SESSION',
        'SHARE',
        'SHOW',
        'SHUTDOWN',
        'SLAVE',
        'SONAME',
        'SOUNDS',
        'SQL',
        'SQL_AUTO_IS_NULL',
        'SQL_BIG_RESULT',
        'SQL_BIG_SELECTS',
        'SQL_BIG_TABLES',
        'SQL_BUFFER_RESULT',
        'SQL_CALC_FOUND_ROWS',
        'SQL_LOG_BIN',
        'SQL_LOG_OFF',
        'SQL_LOG_UPDATE',
        'SQL_LOW_PRIORITY_UPDATES',
        'SQL_MAX_JOIN_SIZE',
        'SQL_QUOTE_SHOW_CREATE',
        'SQL_SAFE_UPDATES',
        'SQL_SELECT_LIMIT',
        'SQL_SLAVE_SKIP_COUNTER',
        'SQL_SMALL_RESULT',
        'SQL_WARNINGS',
        'SQL_CACHE',
        'SQL_NO_CACHE',
        'START',
        'STARTING',
        'STATUS',
        'STOP',
        'STORAGE',
        'STRAIGHT_JOIN',
        'STRING',
        'STRIPED',
        'SUPER',
        'TABLE',
        'TABLES',
        'TEMPORARY',
        'TERMINATED',
        'THEN',
        'TO',
        'TRAILING',
        'TRANSACTIONAL',
        'TRUE',
        'TRUNCATE',
        'TYPE',
        'TYPES',
        'UNCOMMITTED',
        'UNIQUE',
        'UNLOCK',
        'UNSIGNED',
        'USAGE',
        'USE',
        'USING',
        'VARIABLES',
        'VIEW',
        'WHEN',
        'WITH',
        'WORK',
        'WRITE',
        'YEAR_MONTH'
    );

    /**
     * @var array
     */
    private $reservedTopLevel = array(
        'SELECT',
        'FROM',
        'WHERE',
        'SET',
        'ORDER BY',
        'GROUP BY',
        'LIMIT',
        'DROP',
        'VALUES',
        'UPDATE',
        'HAVING',
        'ADD',
        'AFTER',
        'ALTER TABLE',
        'DELETE FROM',
        'UNION ALL',
        'UNION',
        'EXCEPT',
        'INTERSECT'
    );

    /**
     * @var array
     */
    private $reservedNewLine = array(
        'LEFT OUTER JOIN',
        'RIGHT OUTER JOIN',
        'LEFT JOIN',
        'RIGHT JOIN',
        'OUTER JOIN',
        'INNER JOIN',
        'JOIN',
        'XOR',
        'OR',
        'AND'
    );

    /**
     * @var array
     */
    private $functions = array(
        'ABS',
        'ACOS',
        'ADDDATE',
        'ADDTIME',
        'AES_DECRYPT',
        'AES_ENCRYPT',
        'AREA',
        'ASBINARY',
        'ASCII',
        'ASIN',
        'ASTEXT',
        'ATAN',
        'ATAN2',
        'AVG',
        'BDMPOLYFROMTEXT',
        'BDMPOLYFROMWKB',
        'BDPOLYFROMTEXT',
        'BDPOLYFROMWKB',
        'BENCHMARK',
        'BIN',
        'BIT_AND',
        'BIT_COUNT',
        'BIT_LENGTH',
        'BIT_OR',
        'BIT_XOR',
        'BOUNDARY',
        'BUFFER',
        'CAST',
        'CEIL',
        'CEILING',
        'CENTROID',
        'CHAR',
        'CHARACTER_LENGTH',
        'CHARSET',
        'CHAR_LENGTH',
        'COALESCE',
        'COERCIBILITY',
        'COLLATION',
        'COMPRESS',
        'CONCAT',
        'CONCAT_WS',
        'CONNECTION_ID',
        'CONTAINS',
        'CONV',
        'CONVERT',
        'CONVERT_TZ',
        'CONVEXHULL',
        'COS',
        'COT',
        'COUNT',
        'CRC32',
        'CROSSES',
        'CURDATE',
        'CURRENT_DATE',
        'CURRENT_TIME',
        'CURRENT_TIMESTAMP',
        'CURRENT_USER',
        'CURTIME',
        'DATABASE',
        'DATE',
        'DATEDIFF',
        'DATE_ADD',
        'DATE_DIFF',
        'DATE_FORMAT',
        'DATE_SUB',
        'DAY',
        'DAYNAME',
        'DAYOFMONTH',
        'DAYOFWEEK',
        'DAYOFYEAR',
        'DECODE',
        'DEFAULT',
        'DEGREES',
        'DES_DECRYPT',
        'DES_ENCRYPT',
        'DIFFERENCE',
        'DIMENSION',
        'DISJOINT',
        'DISTANCE',
        'ELT',
        'ENCODE',
        'ENCRYPT',
        'ENDPOINT',
        'ENVELOPE',
        'EQUALS',
        'EXP',
        'EXPORT_SET',
        'EXTERIORRING',
        'EXTRACT',
        'EXTRACTVALUE',
        'FIELD',
        'FIND_IN_SET',
        'FLOOR',
        'FORMAT',
        'FOUND_ROWS',
        'FROM_DAYS',
        'FROM_UNIXTIME',
        'GEOMCOLLFROMTEXT',
        'GEOMCOLLFROMWKB',
        'GEOMETRYCOLLECTION',
        'GEOMETRYCOLLECTIONFROMTEXT',
        'GEOMETRYCOLLECTIONFROMWKB',
        'GEOMETRYFROMTEXT',
        'GEOMETRYFROMWKB',
        'GEOMETRYN',
        'GEOMETRYTYPE',
        'GEOMFROMTEXT',
        'GEOMFROMWKB',
        'GET_FORMAT',
        'GET_LOCK',
        'GLENGTH',
        'GREATEST',
        'GROUP_CONCAT',
        'GROUP_UNIQUE_USERS',
        'HEX',
        'HOUR',
        'IF',
        'IFNULL',
        'INET_ATON',
        'INET_NTOA',
        'INSERT',
        'INSTR',
        'INTERIORRINGN',
        'INTERSECTION',
        'INTERSECTS',
        'INTERVAL',
        'ISCLOSED',
        'ISEMPTY',
        'ISNULL',
        'ISRING',
        'ISSIMPLE',
        'IS_FREE_LOCK',
        'IS_USED_LOCK',
        'LAST_DAY',
        'LAST_INSERT_ID',
        'LCASE',
        'LEAST',
        'LEFT',
        'LENGTH',
        'LINEFROMTEXT',
        'LINEFROMWKB',
        'LINESTRING',
        'LINESTRINGFROMTEXT',
        'LINESTRINGFROMWKB',
        'LN',
        'LOAD_FILE',
        'LOCALTIME',
        'LOCALTIMESTAMP',
        'LOCATE',
        'LOG',
        'LOG10',
        'LOG2',
        'LOWER',
        'LPAD',
        'LTRIM',
        'MAKEDATE',
        'MAKETIME',
        'MAKE_SET',
        'MASTER_POS_WAIT',
        'MAX',
        'MBRCONTAINS',
        'MBRDISJOINT',
        'MBREQUAL',
        'MBRINTERSECTS',
        'MBROVERLAPS',
        'MBRTOUCHES',
        'MBRWITHIN',
        'MD5',
        'MICROSECOND',
        'MID',
        'MIN',
        'MINUTE',
        'MLINEFROMTEXT',
        'MLINEFROMWKB',
        'MOD',
        'MONTH',
        'MONTHNAME',
        'MPOINTFROMTEXT',
        'MPOINTFROMWKB',
        'MPOLYFROMTEXT',
        'MPOLYFROMWKB',
        'MULTILINESTRING',
        'MULTILINESTRINGFROMTEXT',
        'MULTILINESTRINGFROMWKB',
        'MULTIPOINT',
        'MULTIPOINTFROMTEXT',
        'MULTIPOINTFROMWKB',
        'MULTIPOLYGON',
        'MULTIPOLYGONFROMTEXT',
        'MULTIPOLYGONFROMWKB',
        'NAME_CONST',
        'NULLIF',
        'NUMGEOMETRIES',
        'NUMINTERIORRINGS',
        'NUMPOINTS',
        'OCT',
        'OCTET_LENGTH',
        'OLD_PASSWORD',
        'ORD',
        'OVERLAPS',
        'PASSWORD',
        'PERIOD_ADD',
        'PERIOD_DIFF',
        'PI',
        'POINT',
        'POINTFROMTEXT',
        'POINTFROMWKB',
        'POINTN',
        'POINTONSURFACE',
        'POLYFROMTEXT',
        'POLYFROMWKB',
        'POLYGON',
        'POLYGONFROMTEXT',
        'POLYGONFROMWKB',
        'POSITION',
        'POW',
        'POWER',
        'QUARTER',
        'QUOTE',
        'RADIANS',
        'RAND',
        'RELATED',
        'RELEASE_LOCK',
        'REPEAT',
        'REPLACE',
        'REVERSE',
        'RIGHT',
        'ROUND',
        'ROW_COUNT',
        'RPAD',
        'RTRIM',
        'SCHEMA',
        'SECOND',
        'SEC_TO_TIME',
        'SESSION_USER',
        'SHA',
        'SHA1',
        'SIGN',
        'SIN',
        'SLEEP',
        'SOUNDEX',
        'SPACE',
        'SQRT',
        'SRID',
        'STARTPOINT',
        'STD',
        'STDDEV',
        'STDDEV_POP',
        'STDDEV_SAMP',
        'STRCMP',
        'STR_TO_DATE',
        'SUBDATE',
        'SUBSTR',
        'SUBSTRING',
        'SUBSTRING_INDEX',
        'SUBTIME',
        'SUM',
        'SYMDIFFERENCE',
        'SYSDATE',
        'SYSTEM_USER',
        'TAN',
        'TIME',
        'TIMEDIFF',
        'TIMESTAMP',
        'TIMESTAMPADD',
        'TIMESTAMPDIFF',
        'TIME_FORMAT',
        'TIME_TO_SEC',
        'TOUCHES',
        'TO_DAYS',
        'TRIM',
        'TRUNCATE',
        'UCASE',
        'UNCOMPRESS',
        'UNCOMPRESSED_LENGTH',
        'UNHEX',
        'UNIQUE_USERS',
        'UNIX_TIMESTAMP',
        'UPDATEXML',
        'UPPER',
        'USER',
        'UTC_DATE',
        'UTC_TIME',
        'UTC_TIMESTAMP',
        'UUID',
        'VARIANCE',
        'VAR_POP',
        'VAR_SAMP',
        'VERSION',
        'WEEK',
        'WEEKDAY',
        'WEEKOFYEAR',
        'WITHIN',
        'X',
        'Y',
        'YEAR',
        'YEARWEEK'
    );

    /**
     * @var array
     */
    private $boundaries = array(
        ',',
        ';',
        ')',
        '(',
        '.',
        '=',
        '<',
        '>',
        '+',
        '-',
        '*',
        '/',
        '!',
        '^',
        '%',
        '|',
        '&',
        '#'
    );

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
        $reservedMap = array_combine($this->reserved, array_map('strlen', $this->reserved));
        arsort($reservedMap);
        $this->reserved = array_keys($reservedMap);

        $this->regexFunction         = $this->initRegex($this->functions);
        $this->regexBoundaries       = $this->initRegex($this->boundaries);
        $this->regexReserved         = $this->initRegex($this->reserved);
        $this->regexReservedTopLevel = str_replace(' ', '\\s+', $this->initRegex($this->reservedTopLevel));
        $this->regexReservedNewLine  = str_replace(' ', '\\s+', $this->initRegex($this->reservedNewLine));
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
