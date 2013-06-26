<?php
class sfPostcodeAnywhere
{
  private $accountCode;
  private $licenceKey;
  private $serviceUrl;

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
      $this->serviceUrl = sfConfig::get('app_postcodeanywhere_service_url', 'http://services.postcodeanywhere.co.uk/');
    }
  }


  protected function prepareUrl($data)
  {
    /* Build up the URL to request the data from. */
   $sURL = "http://services.postcodeanywhere.co.uk/xml.aspx?";
   $sURL .= "account_code=" . urlencode($ACCOUNTCODE);
   $sURL .= "&license_code=" . urlencode($LICENSEKEY);
   $sURL .= "&action=fetch";
   $sURL .= "&style=simple";
   $sURL .= "&id=" . $AddressID;
  }


  public function validateEmail($email)
  {

  }
}

