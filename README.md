sfPostcodeAnywhere
==================

Symfony 1 plugin to support the PostcodeAnywhere API

Setup & Configuration
---------------------

You will need to register an account with [PostcodeAnywhere](http://www.postcodeanywhere.co.uk).
Copy or rename the app.yml.dist file to app.yml, or copy the content to your project or application app.yml.
Fill in the details (minimum required is licence key, obtained from PostcodeAnywhere).

Examples
--------

### Validating an email address ###

    $pa = new sfPostcodeAnywhere();
    $isValid = $pa->validateEmail('test@address.com');

You can also capture back which bits of the email validator passed/failed - this can be used if you only care about syntax and not if there is a valid mail server, etc.

    $pa = new sfPostcodeAnywhere();
    $pa->validateEmail('test@address.com', $result);
    $isValid = $result['ValidFormat'];

### Validating a UK postal address ###

    $pa = new sfPostcodeAnywhere();
    $isValid = $pa->validateAddressUK($results, 'AA11 1AA');

The results array will contain a list of all the address found against the supplied postcode (if valid).

You can also validate that a place or street address exists for a given postcode.

    $pa = new sfPostcodeAnywhere();
    $isValid = $pa->validateAddressUK($results, 'AA11 1AA', 'Some Town', '1 Some Road');

TODO
----

Implement the rest of the API calls.