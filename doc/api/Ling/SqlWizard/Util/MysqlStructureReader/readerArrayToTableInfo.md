[Back to the Ling/SqlWizard api](https://github.com/lingtalfi/SqlWizard/blob/master/doc/api/Ling/SqlWizard.md)<br>
[Back to the Ling\SqlWizard\Util\MysqlStructureReader class](https://github.com/lingtalfi/SqlWizard/blob/master/doc/api/Ling/SqlWizard/Util/MysqlStructureReader.md)


MysqlStructureReader::readerArrayToTableInfo
================



MysqlStructureReader::readerArrayToTableInfo — method and returns a tableInfo array, which structure is defined in the [Light_DatabaseInfo->getTableInfo](https://github.com/lingtalfi/Light_DatabaseInfo/blob/master/doc/api/Ling/Light_DatabaseInfo/Service/LightDatabaseInfoService/getTableInfo.md) method.




Description
================


public static [MysqlStructureReader::readerArrayToTableInfo](https://github.com/lingtalfi/SqlWizard/blob/master/doc/api/Ling/SqlWizard/Util/MysqlStructureReader/readerArrayToTableInfo.md)(array $readerArray, ?string $defaultDb = null) : array




This is an adapter method that takes the output of the MysqlStructureReader->readContent
method and returns a tableInfo array, which structure is defined in the [Light_DatabaseInfo->getTableInfo](https://github.com/lingtalfi/Light_DatabaseInfo/blob/master/doc/api/Ling/Light_DatabaseInfo/Service/LightDatabaseInfoService/getTableInfo.md) method.




Parameters
================


- readerArray

    

- defaultDb

    


Return values
================

Returns array.








Source Code
===========
See the source code for method [MysqlStructureReader::readerArrayToTableInfo](https://github.com/lingtalfi/SqlWizard/blob/master/Util/MysqlStructureReader.php#L39-L73)


See Also
================

The [MysqlStructureReader](https://github.com/lingtalfi/SqlWizard/blob/master/doc/api/Ling/SqlWizard/Util/MysqlStructureReader.md) class.

Next method: [readFile](https://github.com/lingtalfi/SqlWizard/blob/master/doc/api/Ling/SqlWizard/Util/MysqlStructureReader/readFile.md)<br>

