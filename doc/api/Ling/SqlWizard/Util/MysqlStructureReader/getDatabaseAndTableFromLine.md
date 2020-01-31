[Back to the Ling/SqlWizard api](https://github.com/lingtalfi/SqlWizard/blob/master/doc/api/Ling/SqlWizard.md)<br>
[Back to the Ling\SqlWizard\Util\MysqlStructureReader class](https://github.com/lingtalfi/SqlWizard/blob/master/doc/api/Ling/SqlWizard/Util/MysqlStructureReader.md)


MysqlStructureReader::getDatabaseAndTableFromLine
================



MysqlStructureReader::getDatabaseAndTableFromLine — Returns an array containing the database and the table name from the given line.




Description
================


protected [MysqlStructureReader::getDatabaseAndTableFromLine](https://github.com/lingtalfi/SqlWizard/blob/master/doc/api/Ling/SqlWizard/Util/MysqlStructureReader/getDatabaseAndTableFromLine.md)(string $line) : array




Returns an array containing the database and the table name from the given line.
Note: the database will be null if not found in the line.

The returned array will look like this:

- 0: $database
- 1: $table




Parameters
================


- line

    


Return values
================

Returns array.








Source Code
===========
See the source code for method [MysqlStructureReader::getDatabaseAndTableFromLine](https://github.com/lingtalfi/SqlWizard/blob/master/Util/MysqlStructureReader.php#L180-L198)


See Also
================

The [MysqlStructureReader](https://github.com/lingtalfi/SqlWizard/blob/master/doc/api/Ling/SqlWizard/Util/MysqlStructureReader.md) class.

Previous method: [readContent](https://github.com/lingtalfi/SqlWizard/blob/master/doc/api/Ling/SqlWizard/Util/MysqlStructureReader/readContent.md)<br>Next method: [extractColumn](https://github.com/lingtalfi/SqlWizard/blob/master/doc/api/Ling/SqlWizard/Util/MysqlStructureReader/extractColumn.md)<br>
