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


  /**
  * Construct the object, setting the default values.
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
    $url = $this->serviceUrl . '/' . $method . '?';

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
  * Validate a given email address using the PostcodeAnywhere API
  *
  * @param string $email
  * @return boolean
  * @todo implement!
  */
  public function validateEmail($email)
  {
    return true;
  }
}

