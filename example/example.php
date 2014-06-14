<?php
require_once('../src/dadata.php');


$arDirtyNames = array(
	'Иванов Иван Иванонович',
	'иванов иван иванович',
	'ivanov ivan ivanovich',
	'иванов'
);

$arCleanData = array();
print('<pre>');
try
{
	// https://dadata.ru/api/clean/
	$obDaData = new DaData\DaData('YOUR API KEY');
	// normalize names with non - strict mode
	foreach($arDirtyNames as $dirtyData)
	{
		$arCleanData[] = $obDaData->normalizeFullName($dirtyData, false);
	}
	var_dump($arCleanData);

	// normalize name with strict mode
	$arCleanData[] = $obDaData->normalizeFullName($arDirtyNames[0], true);
}
catch (DaData\DaDataException $e)
{
	var_dump($e->getMessage());
	var_dump($e->getTraceAsString());
}
print('</pre>');