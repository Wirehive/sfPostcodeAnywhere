<?php
/**
 * sfPostcodeAnywhere
 *
 * Interface with the PostcodeAnywhere API
 *
 * @package    symfony
 * @subpackage plugin
 * @author     Robin Corps
 */
class sfPostcodeAnywhere
{
  private $accountCode;
  private $licenceKey;
  private $serviceUrl;
  private $apiVersion = 'v1.10';
  private $apiType = 'json3';


  /**
  * Construct the object, setting the default values
  *
  * @param string $accountCode
  * @param string $licenceKey
  * @param string $serviceUrl
  * @return sfPostcodeAnywhere
  */
  public function __construct($accountCode = false, $licenceKey = false, $serviceUrl = false)
  {
    if ($accountCode)
    {
      $this->accountCode = $accountCode;
    }
    else
    {
      $this->accountCode = sfConfig::get('app_postcodeanywhere_account_code');
    }

    if ($licenceKey)
    {
      $this->licenceKey = $licenceKey;
    }
    else
    {
      $this->licenceKey = sfConfig::get('app_postcodeanywhere_licence_key');
    }

    if ($serviceUrl)
    {
      $this->serviceUrl = $serviceUrl;
    }
    else
    {
      $this->serviceUrl = sfConfig::get('app_postcodeanywhere_service_url', 'http://services.postcodeanywhere.co.uk');
    }
  }


  /**
  * Prepare the URL to fetch, given an array of key-value pairs
  *
  * @param string $method
  * @param array $data
  * @return string
  */
  protected function prepareUrl($method, array $data = null)
  {
    $url = $this->serviceUrl . '/' . $method . '/' . $this->apiVersion . '/' . $this->apiType . '.ws?';

    $params = array(
      'Key' => urlencode($this->licenceKey)
    );

    foreach ($data as $key => $value)
    {
      $params[] = $key . '=' . urlencode($value);
    }

    $url .= implode('&', $params);

    return $url;
  }


  /**
  * Fetch the data from the given URL
  *
  * @param string $url
  * @param int $timeout
  * @return array
  */
  protected function getData($url, $timeout = 30)
  {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_FAILONERROR, true);

    $data = curl_exec($ch);

    curl_close($ch);

    // because we're always using the JSON API interface we can just return the json_decode()'d results
    return json_decode($data);
  }


  /**
  * Validate a given email address using the PostcodeAnywhere API
  *
  * @param string $email
  * @param int $timeout
  * @return boolean
  * @todo implement!
  */
  public function validateEmail($email, $timeout = 3)
  {
    $params = array(
      'Email' => $email,
      'Timeout' => $timeout
    );

    $url = $this->prepareUrl('EmailValidation/Interactive/Validate', $params);

    $result = $this->getData($url);

    return true;
  }
}

