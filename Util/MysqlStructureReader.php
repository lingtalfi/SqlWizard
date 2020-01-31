<?php


namespace Ling\SqlWizard\Util;


use Ling\SqlWizard\Exception\SqlWizardException;

/**
 * The MysqlStructureReader class.
 *
 * This tool can help with parsing a sql file containing CREATE TABLE statements,
 * and will extract some table information (the table name, the fields with their types, their nullability,
 * and if they have auto-incremented, the primary key,  the foreign keys, the unique indexes).
 *
 * However, this will work only on very specific files which have the certain characteristics.
 * Typically, those files which are generated by tools such as MysqlWorkBench (which I tested).
 * Maybe it should work with the dump from phpMyAdmin (not tested).
 *
 * See the @page(MysqlStructureReader example) for more info.
 *
 *
 */
class MysqlStructureReader
{


    /**
     * Same as the readContent method, but takes a file as argument.
     *
     * @param string $file
     * @return array
     * @throws \Exception
     */
    public function readFile(string $file): array
    {
        return $this->readContent(file_get_contents($file));
    }

    /**
     * Reads the given content and returns an array containing **table info items**, each of which having the following structure:
     *
     * - db: string, the name of the database
     * - table: string, the name of the table
     * - pk: array, the names of the columns of the primary key (or an empty array by default)
     * - uind: array, the unique indexes. Each entry of the array is itself an array representing one index.
     *     Each index is an array of column names composing the index.
     * - fkeys: array, the foreign keys. It's an array of foreign key => references, with references being an array with
     *     the following structure:
     *     - 0: the referenced database, or null if it was not specified
     *     - 1: the referenced table
     *     - 2: the referenced column
     * - columnNames: array, the array of column names for this table
     * - columnTypes: array, the array of column name => column type. Each type is in lower string, and contains
     *     the information in parenthesis if any (for instance int, or varchar(64), or char(1), etc...)
     * - columnNullables: array, the array of column name => boolean (whether the column is nullable)
     * - ai: string|null = null, the name of the auto-incremented column if any
     *
     *
     *
     *
     * @param string $content
     * @return array
     * @throws \Exception
     */
    public function readContent(string $content): array
    {

        $tables = [];
        $fkStarted = false;

        if (preg_match_all('!CREATE TABLE (.*);$!msU', $content, $matches)) {
            foreach ($matches[0] as $match) {

                $db = null;
                $table = null;
                $primaryKey = null;
                $uniqueIndexes = [];
                $fKeys = [];
                $columnNames = [];
                $columnTypes = [];
                $columnNulls = [];
                $ai = null;


                //
                $fkName = null;


                $lines = explode("\n", $match);
                $firstLine = array_shift($lines);
                list($db, $table) = $this->getDatabaseAndTableFromLine($firstLine);


                foreach ($lines as $line) {
                    $line = trim($line);


                    //--------------------------------------------
                    // FK PARSING MODE
                    //--------------------------------------------
                    if (true === $fkStarted) {
                        if (0 === strpos($line, "REFERENCES")) {
                            $references = $this->extractColumns($line);
                            if (2 === count($references)) {
                                // adding database=null if it wasn't specified
                                array_unshift($references, null);
                            }
                            $fkStarted = false;
                            $fKeys[$fkName] = $references;
                            $fkName = null;
                        }
                    }
                    //--------------------------------------------
                    // REGULAR PARSING MODE
                    //--------------------------------------------
                    else {

                        if (0 === strpos($line, "PRIMARY KEY")) {
                            $primaryKey = $this->extractColumns($line);
                        } elseif (0 === strpos($line, "UNIQUE INDEX")) {
                            $indexes = $this->extractColumns($line);
                            array_shift($indexes); // drop the name of the unique index
                            $uniqueIndexes[] = $indexes;
                        } elseif (0 === strpos($line, "FOREIGN KEY")) {
                            $fkName = $this->extractColumn($line);
                            $fkStarted = true;
                        } else {
                            //--------------------------------------------
                            // REGULAR COLUMN PARSING
                            //--------------------------------------------
                            $colInfo = $this->extractRegularColumnInfo($line);
                            if (false !== $colInfo) {

                                $columnNames[] = $colInfo[0];
                                $columnTypes[$colInfo[0]] = $colInfo[1];
                                $columnNulls[$colInfo[0]] = $colInfo[2];

                                if (true === $colInfo[3]) {
                                    $ai = $colInfo[0];
                                }
                            }
                        }
                    }
                }


                $tables[] = [
                    "db" => $db,
                    "table" => $table,
                    "pk" => $primaryKey,
                    "uind" => $uniqueIndexes,
                    "fkeys" => $fKeys,
                    "columnNames" => $columnNames,
                    "columnTypes" => $columnTypes,
                    "columnNullables" => $columnNulls,
                    "ai" => $ai,
                ];

            }
        }
        return $tables;
    }


    /**
     * Returns an array containing the database and the table name from the given line.
     * Note: the database will be null if not found in the line.
     *
     * The returned array will look like this:
     *
     * - 0: $database
     * - 1: $table
     *
     *
     *
     * @param string $line
     * @return array
     */
    protected function getDatabaseAndTableFromLine(string $line): array
    {
        $db = null;
        $table = null;
        if (preg_match('!`(.*)`!', $line, $match)) {
            $tmp = str_replace('`', '', $match[1]);
            $p = explode(".", $tmp);
            if (count($p) > 1) {
                $db = array_shift($p);
                $table = implode(".", $p);
            } else {
                $table = $tmp;
            }
        }
        return [
            $db,
            $table,
        ];
    }


    /**
     * Returns the value protected inside backticks from the given line,
     * or throws an exception if it doesn't find one.
     *
     * @param string $line
     * @return string
     * @throws \Exception
     */
    protected function extractColumn(string $line): string
    {
        if (preg_match('!`([^`]*)`!', $line, $match)) {
            return $match[1];
        }
        throw new SqlWizardException("No value inside backticks found in the given line \"$line\".");
    }


    /**
     * Returns the values protected inside backticks from the given line,
     * or throws an exception if it doesn't find any.
     *
     * @param string $line
     * @return array
     * @throws \Exception
     */
    protected function extractColumns(string $line): array
    {
        if (preg_match_all('!`([^`]*)`!', $line, $matches)) {
            return $matches[1];
        }
        throw new SqlWizardException("No value inside backticks found in the given column \"$line\".");
    }


    /**
     * Parse the given line and returns an array containing the following info:
     *
     * - 0: column name
     * - 1: column type (including information in parenthesis if any), in lowercase
     * - 2: is null (bool)
     * - 3: is auto-incremented (bool)
     *
     * Returns false if the line is not recognized as a column definition.
     *
     *
     * @param string $line
     * @return array|false
     * @throws \Exception
     */
    protected function extractRegularColumnInfo(string $line)
    {


        if (preg_match('!^`([^`]*)` ([^ ]*)!', $line, $match)) {
            $name = $match[1];
            $type = strtolower($match[2]);
            $isNull = true;
            $isAi = false;

            if (false !== strpos($line, " NOT NULL")) {
                $isNull = false;
            }

            if (false !== strpos($line, " AUTO_INCREMENT")) {
                $isAi = true;
            }

            return [
                $name,
                $type,
                $isNull,
                $isAi,
            ];
        }
        return false;
    }
}