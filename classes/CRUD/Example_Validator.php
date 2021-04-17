<?php
/**
 * Example_Validator.php - The Cobalt CRUD Validation Tool
 * 
 * Copyright 2021 - Heavy Element, Inc
 * 
 * This is an example of how to create a validator.
 * 
 * First, consider the form data that will be sent to your Validator. We have 
 * done this with $example_data_needing_validation below.
 *
 * Next, define your schema. This is done by creating a series of methods inside
 * your validator class. Each method's name must match the field name in your
 * schema.
 * 
 * This being the case there are a few limitations on this method of validation.
 * 
 * There are several illegal names that mustn't be used for your schema. These 
 * names include:
 * 
 *    * `__construct`, `__destruct`, `__call`, or any other magic class method
 *    * __allowed_names
 *    * __disallowed_names // Reserved for future use
 *    * __on_validation_complete
 * 
 * If the submitted data has a field that is not allowed, 
 * 
 * @license cobalt-core/license
 * @author Gardiner Bryant <gardiner@heavyelement.io>
 */

namespace CRUD; // Make sure you use the appropriate namespace for your validator 

// If validation fails, throw this exception. The validator will catch these
// exceptions, accumulate the them, and throw an \Exceptions\HTTP\BadRequest
// containing whatever failed messages as the data argument.
use \CRUD\Exceptions\ValidationFailed;

$example_data_needing_validation = [ // This is for illustrative purposes only.
  'name'   => 'Todd Simons ', // Note the trailing space
  'email'  => 'todd simons@example.com', // This is intentinally malformed
  'phone'  => '(818) 555-8558', // Formatted US phone number
  'region' => 'us-east', 
  'order_count' => '8', // An integer as a string
  'extra' => 'something else'
];

class Example_Validator{
  
  // A list of names that are allowed as part of the final dataset.
  protected $__allowed_names = [
    'name',
    'email',
    'phone',
    'region',
    'order_count',
  ];

   
  /**
   * This function is called at the end of the validation routine. Its results
   * are array_merged with the validated results.
   *
   * @return array Any data you want to have specified. Is overridden by 
   *               validated results
   */
  function __on_validation_complete(){

  }

  /** Every method will be passed the same args in the same order. The $value of
   * the current field along with the rest of the $submitted info if you need
   * to reference it.
   * 
   * Finally, the $already_validated field will contain the fields that have
   * already been processed.
   * */
  function name($value, $submitted, $already_validated){
    return filter_var(trim($value),FILTER_SANITIZE_STRING); // Returning a value set the field name 
  }

  function email($value, $sub, $validated){
    $value = trim($value);
    if(!\filter_var($value,FILTER_VALIDATE_EMAIL)) throw new ValidationFailed("Malformed email");
    return $value; // We can return here because we know we have a valid email
  }

  function phone($value,$sub,$validated){
    // List of characters we don't want to store in our db
    $junk = ["(",")"," ","-","."]; 
    
    // Strip the junk characters out of the string
    $value = str_replace($junk,"",$value); 
    
    // Check if the phone number is only digits and if not throw an exception.
    if(!ctype_digit($value)) throw new ValidationFailed("Malformed phone number");
    
    if( strlen($value) < 10 ) throw new ValidationFailed("Not long enough");

    return $value;
  }

  function region($value){
    $valid_regions = ['us-east','uk-south'];
    if(!in_array($value,$valid_regions)) throw new ValidationFailed("Invalid region");
    return $value;
  }

  function order_count($value){
    if(!ctype_digit($value)) throw new ValidationFailed("Malformed orders");
    $value = (int)$value;
    return $value;
  }

}