<?php


namespace Ling\SqlWizard\Util;


use Ling\SqlWizard\Exception\SqlWizardException;
use Ling\SqlWizard\Tool\SqlWizardGeneralTool;

/**
 * The SelectQueryParser class.
 *
 *
 * Warning, read the section below before using this class.
 *
 *
 * The keyword option
 * --------------
 *
 * Some methods of this class use the "keyword" option.
 * This is because there is a limitation in the technique I used to parse the queries: it will work only if your query doesn't contain
 * an expression with the following format:
 *
 * - __ref1__
 *
 * Where 1 can be replaced by any number.
 * That's because we internally use those references to parse the query.
 *
 * If your query does actually use such references, use the keyword option to replace the ref keyword with
 * one that's not in your query.
 *
 *
 *
 *
 */
class MysqlSelectQueryParser
{


    /**
     * Returns an array containing the different parts of the given mysql query.
     *
     * The available parts are:
     *
     * - fields, the part just after the "select" keyword
     * - from, the part just after the "from" keyword
     * - where
     * - joins, the part containing the joins (including the join keyword for you to investigate the join type).
     *      The following types of joins are handled so far:
     *      - inner join
     *      - left join
     *      - right join
     * - groupBy, the part just after the "group by" keyword
     * - having
     * - orderBy, the part just after the "order by" keyword
     * - limit
     *
     *
     * The returned array contains all the above parts as the key, and the value is either null if the part is not defined,
     * or a string corresponding to the part otherwise.
     *
     *
     *
     * More details about the select syntax: https://dev.mysql.com/doc/refman/8.0/en/select.html.
     *
     *
     * Warning: this doesn't handle subqueries.
     *
     *
     *
     *
     * Available options:
     * - keyword: string=ref, see the class comment for more details
     *
     *
     *
     *
     * @param string $query
     * @param array $options
     * @return array
     */
    public static function getQueryParts(string $query, array $options = []): array
    {
        $keyword = $options['keyword'] ?? 'ref';


        /**
         * first flatten the query to reduce complexity.
         * Flatten means remove all the backtick escaped strings.
         */
        list($flatQuery, $references) = SqlWizardGeneralTool::flattenBackticks($query, [
            'keyword' => $keyword,
        ]);

        $fields = null;
        $from = null;
        $where = null;
        $joins = null;
        $groupBy = null;
        $having = null;
        $orderBy = null;
        $limit = null;

        //--------------------------------------------
        // LIMIT
        //--------------------------------------------
        $pattern = '!\blimit\b!im';
        $res = preg_split($pattern, $flatQuery, 2);
        if (2 === count($res)) {
            list($before, $after) = $res;
            $limit = trim($after);
            $flatQuery = $before;
        }


        //--------------------------------------------
        // ORDER BY
        //--------------------------------------------
        $pattern = '!\border\s+by\b!im';
        $res = preg_split($pattern, $flatQuery, 2);
        if (2 === count($res)) {
            list($before, $after) = $res;
            $orderBy = trim($after);
            $flatQuery = $before;
        }

        //--------------------------------------------
        // HAVING
        //--------------------------------------------
        $pattern = '!\bhaving\b!im';
        $res = preg_split($pattern, $flatQuery, 2);
        if (2 === count($res)) {
            list($before, $after) = $res;
            $having = trim($after);
            $flatQuery = $before;
        }

        //--------------------------------------------
        // GROUP BY
        //--------------------------------------------
        $pattern = '!\bgroup\s+by\b!im';
        $res = preg_split($pattern, $flatQuery, 2);
        if (2 === count($res)) {
            list($before, $after) = $res;
            $groupBy = trim($after);
            $flatQuery = $before;
        }


        //--------------------------------------------
        // JOINS
        //--------------------------------------------
        $pattern = '!((?:(?:\binner\b|\bleft\b|\bright\b)\s+)?\bjoin\b)!im';
        $res = preg_split($pattern, $flatQuery, 2, \PREG_SPLIT_DELIM_CAPTURE);
        if (3 === count($res)) {
            list($before, $delim, $after) = $res;
            $joins = trim($delim . $after);
            $flatQuery = $before;
        }

        //--------------------------------------------
        // WHERE
        //--------------------------------------------
        $pattern = '!\bwhere\b!im';
        $res = preg_split($pattern, $flatQuery, 2);
        if (2 === count($res)) {
            list($before, $after) = $res;
            $where = trim($after);
            $flatQuery = $before;
        }

        //--------------------------------------------
        // FROM
        //--------------------------------------------
        $pattern = '!\bfrom\b!im';
        $res = preg_split($pattern, $flatQuery, 2);
        if (2 === count($res)) {
            list($before, $after) = $res;
            $from = trim($after);
            $flatQuery = $before;
        }

        //--------------------------------------------
        // FIELDS
        //--------------------------------------------
        $pattern = '!\bselect\b!im';
        $res = preg_split($pattern, $flatQuery, 2);
        if (2 === count($res)) {
            list($before, $after) = $res;
            $fields = trim($after);
            $flatQuery = $before;
        }


        if (null === $fields) {
            self::error("Invalid mysql select query: the \"select\" keyword wasn't found.");
        }

        if (null === $from) {
            self::error("Invalid mysql select query: the \"from\" keyword wasn't found.");
        }


        $arr = [
//            'query' => $query,
            'fields' => $fields,
            'from' => $from,
            'where' => $where,
            'joins' => $joins,
            'groupBy' => $groupBy,
            'having' => $having,
            'orderBy' => $orderBy,
            'limit' => $limit,
        ];


        array_walk($arr, function (&$v) use ($references) {
            $v = self::replaceRefsInString($v, $references);
        });


        return $arr;
    }


    /**
     * Returns an array containing some info about the given fields.
     *
     * It's an array of fieldItems representing the fields used in the query.
     *
     *
     * Each fieldItem is an array containing:
     * - column: string, the actual column from the table
     * - tableAlias: string=null, the table alias used for this column, or null if no table alias was used
     * - alias: string=null, the alias used for this column, or null if no alias was used
     *
     *
     * Note: I didn't put the column as the key since with inner joins, two different tables could use the same column name
     * which would lead to conflicts.
     *
     *
     *
     * Available options:
     * - keyword: string=ref, see the class comment for more details
     *
     *
     *
     *
     *
     * @param string $fields
     * @param array $options
     * @return array
     */
    public static function getFieldsInfo(string $fields, array $options = []): array
    {
        $keyword = $options['keyword'] ?? 'ref';


        /**
         * first flatten the query to reduce complexity.
         * Flatten means remove all the backtick escaped strings.
         */
        list($flatQuery, $references) = SqlWizardGeneralTool::flattenBackticks($fields, [
            'keyword' => $keyword,
        ]);


        $fields = [];


        $p = explode(',', $flatQuery);
        $patternAs = '!\bas\b!i';
        foreach ($p as $sField) {

            $column = null;
            $alias = null;
            $tableAlias = null;


            $p2 = preg_split($patternAs, $sField, 2);
            if (2 === count($p2)) {
                $alias = trim(trim($p2[1]), '`');
                $sColumn = trim($p2[0]);
            } else {
                $sColumn = trim($p2[0]);
            }
            $p3 = explode('.', $sColumn, 2);
            if (2 === count($p3)) {
                $tableAlias = trim($p3[0]);
                $column = trim($p3[1]);
            } else {
                $column = trim($p3[0]);
            }


            $realColumn = self::replaceRefs($column, $references);

            $fields[] = [
                'column' => $realColumn,
                'tableAlias' => self::replaceRefs($tableAlias, $references),
                'alias' => self::replaceRefs($alias, $references),
            ];
        }


        return $fields;
    }


    /**
     * Returns an array containing some info about the "from" clause:
     *
     * - 0: database; string=null, the database if specified, or null otherwise
     * - 1: table; string, the actual table name used
     * - 2: tableAlias; string=null, the table alias is defined, null otherwise
     *
     *
     *
     * Available options:
     * - keyword: string=ref, see the class comment for more details
     *
     *
     *
     *
     *
     * @param string $from
     * @param array $options
     * @return array
     */
    public static function getFromInfo(string $from, array $options = []): array
    {
        $keyword = $options['keyword'] ?? 'ref';

        list($flat, $references) = SqlWizardGeneralTool::flattenBackticks($from, [
            'keyword' => $keyword,
        ]);


        $database = null;
        $table = null;
        $tableAlias = null;


        $pattern = '!(\s+)!im';
        $res = preg_split($pattern, $flat, 2);

        if (2 === count($res)) {
            list($tableString, $aliasString) = $res;
            $res2 = preg_split($pattern, $aliasString, 2);
            $tableAlias = trim(array_pop($res2));
        } else {
            $tableString = $res[0];
        }

        $tableString = trim($tableString);
        $p = explode('.', $tableString, 2);
        if (2 === count($p)) {
            $database = $p[0];
            $table = $p[1];
        } else {
            $table = $p[0];
        }


        return [
            self::replaceRefs($database, $references),
            self::replaceRefs($table, $references),
            self::replaceRefs($tableAlias, $references),
        ];
    }





    //--------------------------------------------
    //
    //--------------------------------------------
    /**
     * Returns the referenced version of the given expression if found in the given references array, or the original expression if not.
     * If the expression is null, this method returns null.
     *
     * @param string|null $expression
     * @param array $references
     * @return string|null
     */
    private static function replaceRefs(?string $expression, array $references)
    {
        if (null === $expression) {
            return null;
        }
        return (array_key_exists($expression, $references)) ? $references[$expression] : $expression;
    }


    /**
     * Replaces all the references by their values in the given expression, and returns the result.
     * Returns null if the given expression is null.
     *
     * Note: this method wraps the values with the backticks, so that you get the original
     * expression as written.
     *
     *
     * @param string|null $expression
     * @param array $references
     * @return string|null
     */
    private static function replaceRefsInString(?string $expression, array $references)
    {
        if (null === $expression) {
            return null;
        }
        $values = array_map(function ($v) {
            return '`' . $v . '`';
        }, array_values($references));


        return str_replace(array_keys($references), $values, $expression);
    }


    /**
     * Throws an exception.
     * @param string $msg
     * @throws \Exception
     */
    private static function error(string $msg)
    {
        throw new SqlWizardException($msg);
    }
}