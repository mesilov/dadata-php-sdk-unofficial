<?php
namespace DaData;
require_once("dadataexception.php");

class DaData
{
	/**
	 * SDK version
	 */
	const VERSION = '1.0';
	const QC_PASSED = 0;
	const QC_FAILED = 1;
	const GENDER_MALE = "М";
	const GENDER_FEMALE = "Ж";
	const GENDER_UNKNOWN = "НД";

	/*
	 * @todo сделать отдельным методом хранение URL для подключения к API
	 */


	/**
	 * access token
	 * @var string
	 */
	protected $accessToken = null;

	/**
	 * raw request, contain all cURL options array and API query
	 * @var array
	 */
	protected $rawRequest = null;

	/**
	 * @var array, contain all api-method parameters, vill be available after call method
	 */
	protected $methodParameters = null;

	/**
	 * request info data structure акщь curl_getinfo function
	 * @var array
	 */
	protected $requestInfo = null;

	/**
	 * @var bool if true raw response from bitrix24 will be available from method getRawResponse, this is debug mode
	 */
	protected $isSaveRawResponse = false;

	/**
	 * @var array raw response from bitrix24
	 */
	protected $rawResponse = null;


	/**
	 * Create a object to work with Bitrix24 REST API service
	 * @param bool $isSaveRawResponse - if true raw response from bitrix24 will be available from method getRawResponse, this is debug mode
	 * @throws DaDataException
	 * @return DaData
	 */
	public function __construct($isSaveRawResponse = false)
	{
		if (!extension_loaded('curl'))
		{
			throw new DaDataException('cURL extension must be installed to use this library');
		}
		if(!is_bool($isSaveRawResponse))
		{
			throw new DaDataException('isSaveRawResponse flag must be boolean');
		}
		$this->isSaveRawResponse = $isSaveRawResponse;
	}

	/**
	 * Set access token
	 * @param string $accessToken
	 * @throws DaDataException
	 * @return true;
	 */
	public function setAccessToken($accessToken)
	{
		if(!empty($accessToken))
		{
			$this->accessToken = $accessToken;
			return true;
		}
		else
		{
			throw new DaDataException('access token not set');
		}
	}

	/**
	 * Get access token
	 * @return string | null
	 */
	public function getAccessToken()
	{
		return $this->accessToken;
	}

	/**
	 * Return raw request, contain all cURL options array and API query. Data available after you try to call method call
	 * numbers of array keys is const of cURL module. Example: CURLOPT_RETURNTRANSFER = 19913
	 * @return array | null
	 */
	public function getRawRequest()
	{
		return $this->rawRequest;
	}

	/**
	 * Return result from function curl_getinfo. Data available after you try to call method call
	 * @return array | null
	 */
	public function getRequestInfo()
	{
		return $this->requestInfo;
	}

	/**
	 * Return additional parameters of last api-call. Data available after you try to call method call
	 * @return array | null
	 */
	public function getMethodParameters()
	{
		return $this->methodParameters;
	}

	/**
	 * Execute a request API to dadata using cURL
	 * @param $arData
	 * @param string $url
	 * @param $accessToken
	 * @throws DaDataIoException
	 * @throws DaDataException
	 * @internal param array $additionalParameters
	 * @return array
	 */
	protected function executeRequest($arData, $url, $accessToken)
	{
		/**
		 * @todo add method to set custom cURL options
		 */
		$curlOptions = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLINFO_HEADER_OUT => true,
			CURLOPT_VERBOSE => true,
			CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_TIMEOUT        => 5,
			CURLOPT_USERAGENT => strtolower(__CLASS__.'-PHP-SDK/v'.self::VERSION),
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => json_encode($arData),
			CURLOPT_URL => $url,
			CURLOPT_HTTPHEADER => array(
				'Authorization: Token '.$accessToken,
				'Content-Type: application/json'
			)
		);
		$this->rawRequest = $curlOptions;
		$curl = curl_init();
		curl_setopt_array($curl, $curlOptions);
		$curlResult = curl_exec($curl);
		$this->requestInfo = curl_getinfo($curl);
		$curlErrorNumber = curl_errno($curl);
		// handling network I/O errors
		if($curlErrorNumber > 0)
		{
			$errorMsg = curl_error($curl).PHP_EOL.'cURL error code: '.$curlErrorNumber.PHP_EOL;
			curl_close($curl);
			throw new DaDataIoException($errorMsg);
		}
		else
		{
			curl_close($curl);
		}
		if(true === $this->isSaveRawResponse)
		{
			$this->rawResponse = $curlResult;
		}
		// handling json_decode errors
		$jsonResult = json_decode($curlResult, true);
		unset($curlResult);
		$jsonErrorCode = json_last_error();
		if(is_null($jsonResult) && (JSON_ERROR_NONE != $jsonErrorCode))
		{
			/**
			 * @todo add function json_last_error_msg()
			 */
			$errorMsg = 'fatal error in function json_decode.'.PHP_EOL.'Error code: '.$jsonErrorCode.PHP_EOL;
			throw new DaDataException($errorMsg);
		}
		return $jsonResult;
	}

	/**
	 * Execute DaData REST API method
	 * @param array $arUsers
	 * @throws DaDataIoException
	 * @throws DaDataException
	 * @throws DaDataApiException
	 * @return array
	 */
	public function call(array $arUsers = array())
	{
		if(is_null($this->getAccessToken()))
		{
			throw new DaDataException('access token not found, you must call setAccessToken method before');
		}

		$url = 'https://dadata.ru/api/v1/clean';
		// save method parameters for debug
		$this->methodParameters = $arUsers;


		$requestResult = $this->executeRequest($arUsers, $url, $this->accessToken);

		if (array_key_exists('error', $requestResult))
		{
			$errName = '';
			$errDescription = '';
			if (isset($requestResult['error_description'])) {
				$errDescription = $requestResult['error_description'].PHP_EOL;
			}
			if (!strlen($errDescription)) {
				$errName = $requestResult['error'].PHP_EOL;
			}
			$errorMsg = $errName.$errDescription;
			throw new DaDataApiException($errorMsg);
		}
		return $requestResult;
	}

	/**
	 * Get raw response from Bitrix24 before json_decode call, method available only in debug mode.
	 * To activate debug mode you must before set to true flag isSaveRawResponse in class construct
	 * @throws DaDataException
	 * @return string
	 */
	public function getRawResponse()
	{
		if(false === $this->isSaveRawResponse)
		{
			throw new DaDataApiException('you must before set to true flag isSaveRawResponse in class construct');
		}
		return $this->rawResponse;
	}

	/**
	 * @param $fullName
	 * @param bool $isStrict
	 * @return null
	 * @throws DaDataException
	 */
	public function normalizeFullName($fullName, $isStrict = true)
	{
		$arResult=null;
		$arDataToNrmalize = array(
			'structure' => array(
				'NAME'
			),
			'data' => array(
				array(
					$fullName
				)
			)
		);

		$arCleanUser = $this->call($arDataToNrmalize);
		$arCleanUser = $arCleanUser["data"][0][0];

		if($isStrict)
		{
			if($arCleanUser['qc'] == $this::QC_PASSED)
			{
				if($arCleanUser['gender'] != $this::GENDER_UNKNOWN)
				{
					if(($arCleanUser['surname'] != '') && ($arCleanUser['name'] != ''))
					{
						$arResult = $arCleanUser;
					}
					elseif($arCleanUser['surname'] == '')
					{
						throw new DaDataException('empty surname');
					}
					elseif($arCleanUser['name'] == '')
					{
						throw new DaDataException('empty name');
					}
				}
				else
				{
					throw new DaDataException('unknown gender');
				}
			}
			else
			{
				throw new DaDataException('dadata internal quality control failed');
			}
		}
		else
		{
			$arResult = $arCleanUser;
		}
		return $arResult;
	}
}