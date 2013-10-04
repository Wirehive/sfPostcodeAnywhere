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
  * @param string $apiVersion
  * @param array $data
  * @return string
  */
  protected function prepareUrl($method, $apiVersion, array $data = null)
  {
    $url = $this->serviceUrl . '/' . $method . '/' . $apiVersion . '/' . $this->apiType . '.ws?';

    $params = array(
      'Key=' . urlencode($this->licenceKey)
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
    return json_decode($data, true);
  }


  /**
  * Validate a given email address using the PostcodeAnywhere API
  *
  * @param string $email
  * @param array $result
  * @param int $timeout
  * @return boolean
  */
  public function validateEmail($email, &$result, $timeout = 3)
  {
    $params = array(
      'Email' => $email,
      'Timeout' => $timeout
    );

    $url = $this->prepareUrl('EmailValidation/Interactive/Validate', 'v1.10', $params);

    $results = $this->getData($url);

    // the API returns an array but as we're only ever checking one result we can just take the first item off the array
    $result = $results['Items'][0];

    // check that the given address has a valid mail server, format and DNS record
    $valid = true;

    if (!($result['MailServer'] && $result['ValidFormat'] && $result['FoundDnsRecord']))
    {
      $valid = false;
    }

    return $valid;
  }


  /**
  * Validates a UK postal address.
  * Can validate just on postcode, or it can validate that a place or address matches a given postcode.
  *
  * @param array $matches
  * @param string $postcode
  * @param string $place
  * @param string $street
  * @param string $filter
  * @param string $preferredLanguage
  * @return boolean
  */
  public function validateAddressUK(&$matches, $postcode, $place = false, $street = false, $filter = 'None', $preferredLanguage = 'English')
  {
    $params = array(
      'SearchTerm' => $postcode,
      'PreferredLanguage' => $preferredLanguage,
      'Filter' => $filter
    );

    $url = $this->prepareUrl('PostcodeAnywhere/Interactive/Find', 'v1.10', $params);

    $results = $this->getData($url);

    if (array_key_exists('Items', $results) && count($results['Items']))
    {
      $matches = $results['Items'];

      // if a place or a street has been provided then try to do some validation against them
      if ($place || $street)
      {
        $valid = true;
        $validStreet = false;
        $validPlace = false;
        $street = strtolower(preg_replace('/[^\w]+/', '', $street));
        $place = strtolower(preg_replace('/[^\w]+/', '', $place));

        foreach ($matches as $match)
        {
          if ($street && strstr(strtolower(preg_replace('/[^\w]+/', '', $match['StreetAddress'])), $street))
          {
            $validStreet = true;
          }

          if ($place && $place == strtolower(preg_replace('/[^\w]+/', '', $match['Place'])))
          {
            $validPlace = true;
          }
        }

        if (($street && !$validStreet) || ($place && !$validPlace))
        {
          $valid = false;
        }

        return $valid;
      }
      // otherwise we found matches for the postcode so it must be valid
      else
      {
        return true;
      }
    }

    // no results found for the postcode so it's an invalid address
    return false;
  }


  /**
  * Validates an international postal address.
  * Can validate just on postcode, or it can validate that components of an address exist on a given postcode.
  *
  * @param array $matches
  * @param string $countryCode
  * @param string $postcode
  * @param string $state
  * @param string $city
  * @param string $street
  * @param int $buildingNumber
  * @return boolean
  */
  public function validateAddressInternational(&$matches, $countryCode, $postcode, $state = false, $city = false, $street = false, $buildingNumber = false)
  {
    $params = array(
      'Country' => $countryCode,
      'PostalCode' => $postcode
    );

    // if building has been provided then we can filter the retrieved results before having to do manual filtering
    if ($buildingNumber)
    {
      $params['Building'] = $buildingNumber;
    }

    $url = $this->prepareUrl('PostcodeAnywhereInternational/Interactive/RetrieveByPostalCode', 'v2.20', $params);

    $results = $this->getData($url);

    if (array_key_exists('Items', $results) && count($results['Items']))
    {
      $matches = $results['Items'];

      // if an address component has been provided then try to do some validation against it
      if ($state || $city || $street)
      {
        $valid = true;
        $validStreet = false;
        $validCity = false;
        $validState = false;
        $street = strtolower(preg_replace('/[^\w]+/', '', $street));
        $city = strtolower(preg_replace('/[^\w]+/', '', $city));
        $state = strtolower(preg_replace('/[^\w]+/', '', $state));

        foreach ($matches as $match)
        {
          if ($street && strstr(strtolower(preg_replace('/[^\w]+/', '', $match['Street'])), $street))
          {
            $validStreet = true;
          }

          if ($city && $city == strtolower(preg_replace('/[^\w]+/', '', $match['City'])))
          {
            $validCity = true;
          }

          if ($state && $state == strtolower(preg_replace('/[^\w]+/', '', $match['State'])))
          {
            $validState = true;
          }
        }

        if (($state && !$validState) || ($city && !$validCity) || ($street && !$validStreet))
        {
          $valid = false;
        }

        return $valid;
      }
      // otherwise we found matches for the postcode so it must be valid
      else
      {
        return true;
      }
    }

    return false;
  }


  /**
  * Look up address details for the given address parts
  *
  * Search Params:
  *  - 'Organisation'
  *  - 'Building'
  *  - 'Street'
  *  - 'Locality'
  *  - 'Postcode'
  *
  * @param array $params
  * @param string $country
  * @return array
  */
  public function lookupAddress($params, $country='UK')
  {
    if (!$country)
    {
      throw new sfPostcodeAnywhereException('Missing country');
    }
    else
    {
      return call_user_func_array(array($this, 'lookupAddress'.$country), array($params));
    }
  }


  /**
  * Look up address details for the given address parts
  *
  * Search Params:
  *  - 'Organisation'
  *  - 'Building'
  *  - 'Street'
  *  - 'Locality'
  *  - 'Postcode'
  *
  * @param array $params
  * @return array
  */
  public function lookupAddressUK($params)
  {
    $url = $this->prepareUrl('PostcodeAnywhere/Interactive/RetrieveByParts', 'v1.00', $params);

    $results = $this->getData($url);

    if (!is_array($results) || !array_key_exists('Items', $results) || array_key_exists('Error', $results['Items'][0]))
    {
      return false;
    }

    return $results['Items'];
  }


  public function freeLookupAddress($postcode, $country='UK')
  {
    if (!$country)
    {
      throw new sfPostcodeAnywhereException('Missing country');
    }
    else
    {
      return call_user_func_array(array($this, 'freeLookupAddress'.$country), array($postcode));
    }
  }


  public function freeLookupAddressUK($postcode)
  {
    $addresses = array();
    $counter = 0;
    $street = array();
    $first = true;

    foreach ($items['Items'] as $i => $address)
    {
      if (array_key_exists('Error', $address))
      {
        return sfView::ERROR;
      }

      $words = explode(' ', $address['StreetAddress']);
      $words = array_reverse($words);

      if (count($words) && !(count($words) == 1 && !$words[0]))
      {
        if ($first)
        {
          $counter = count($words) - 1;
          $street = $words;
          $first = false;
        }
        else
        {
          if (count($words) - 1 < $counter)
          {
            $counter = count($words) - 1;
          }

          foreach ($words as $j => $word)
          {
            if ($j > $counter)
            {
              break;
            }

            if ($street[$j] != $word)
            {
              $counter = $j - 1;
              break;
            }
          }
        }
      }
    }

    $street_string = array();

    for ($i = 0; $i <= $counter; $i++)
    {
      $street_string[] = $street[$i];
    }

    $street_string = implode(' ', array_reverse($street_string));

    if ($street_string)
    {
      foreach ($items['Items'] as $address)
      {
        $address1 = trim(str_replace($street_string, '', $address['StreetAddress']));

        $addresses[] = array(
          'address1' => $address1,
          'address2' => $street_string,
          'city' => $address['Place']
        );
      }
    }
    else // looks like we've got a result that might include company names which breaks the data format (sigh)
    {
      $counter = 0;
      $city = array();
      $first = true;

      foreach ($items['Items'] as $i => $address)
      {
        $words = explode(' ', $address['Place']);
        $words = array_reverse($words);

        if (count($words) && !(count($words) == 1 && !$words[0]))
        {
          if ($first)
          {
            $counter = count($words) - 1;
            $city = $words;
            $first = false;
          }
          else
          {
            if (count($words) - 1 < $counter)
            {
              $counter = count($words) - 1;
            }

            foreach ($words as $j => $word)
            {
              if ($j > $counter)
              {
                break;
              }

              if ($city[$j] != $word)
              {
                $counter = $j - 1;
                break;
              }
            }
          }
        }
      }

      $city_string = array();

      for ($i = 0; $i <= $counter; $i++)
      {
        $city_string[] = $city[$i];
      }

      $city_string = implode(' ', array_reverse($city_string));

      $counter = 0;
      $street = array();
      $first = true;

      // now that we've worked out the city string, go back over the "place" entries and extract the address portion
      foreach ($items['Items'] as $i => $address)
      {
        $string = trim(str_replace($city_string, '', $address['Place']));

        if ($string)
        {
          $words = explode(' ', $string);
          $words = array_reverse($words);

          if ($first)
          {
            $counter = count($words) - 1;
            $street = $words;
            $first = false;
          }
          else
          {
            if (count($words) - 1 < $counter)
            {
              $counter = count($words) - 1;
            }

            foreach ($words as $j => $word)
            {
              if ($j > $counter)
              {
                break;
              }

              if ($street[$j] != $word)
              {
                $counter = $j - 1;
                break;
              }
            }
          }
        }
      }

      $street_string = array();

      for ($i = 0; $i <= $counter; $i++)
      {
        $street_string[] = $street[$i];
      }

      $street_string = implode(' ', array_reverse($street_string));

      // so now we should have a city string and a street string we can loop back over the array one last time to build the results

      foreach ($items['Items'] as $address)
      {
        $address2 = trim(str_replace($city_string, '', $address['Place']));

        if ($address2)
        {
          $address1 = trim(str_replace($street_string, '', $address2));
          $address2 = trim(str_replace($address1, '', $address2));

          $addresses[] = array(
            'company' => $address['StreetAddress'],
            'address1' => $address1,
            'address2' => $address2,
            'city' => $city_string
          );
        }
        else
        {
          $address1 = trim(str_replace($street_string, '', $address['StreetAddress']));

          $addresses[] = array(
            'address1' => $address1,
            'address2' => $street_string,
            'city' => $city_string
          );
        }
      }
    }

    // finally sanitize the addresses so that # Street is on one line, but ShopX, House Name, etc is on a separate line
    foreach ($addresses as $i => $address)
    {
      if (preg_match('/^[0-9]+-?[a-z]{0,2}/i', $address['address1']))
      {
        $addresses[$i]['address1'] = $addresses[$i]['address1'] . ' ' . $addresses[$i]['address2'];
        unset($addresses[$i]['address2']);
      }
    }

    return $addresses;
  }
}


/**
* Custom class to handle any exceptions if required
*/
class sfPostcodeAnywhereException extends sfException
{

}