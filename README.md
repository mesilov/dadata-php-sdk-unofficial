dadata-php-sdk (unofficial)
=========================

A PHP library for the DaData.ru REST API


[API documentation](https://dadata.ru/api/clean/)
## Example ##
``` php
// init lib
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
```
## Installation ##
Update your composer.json file
``` json
    "require": {
        "mesilov/dadata-php-sdk-unofficial":"dev-master"
    },
    "repositories": [
        {

		"url": "https://github.com/mesilov/dadata-php-sdk-unofficial",
		"type": "vcs"

        }
    ]    
```
## Support ##
email: <mesilov.maxim@gmail.com>  
vk: [mesilov.maxim](https://vk.com/mesilov.maxim)  
twitter: [@mesilov](https://twitter.com/mesilov)
