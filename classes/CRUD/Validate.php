<?php
/**
 * Validate.php - The Cobalt CRUD Validation Tool
 * 
 * Copyright 2021 - Heavy Element, Inc
 * 
 * This class is meant to provide a consistent method of validation for data 
 * across Cobalt engine and any project based on it.
 * 
 *  > $validate = new \CRUD\Validate();
 *  > $validate->register_schema("\Example\ClassValidator");
 *  > $result = $validate->validate();
 * 
 * If you provided a validator class with methods that match the field names of
 * the data you're trying to validate, $result will be a fully validated 
 * 
 * You can either instance a class and pass that as the sole argument to
 * register_schema or pass the name of the class (with namespace) as a string.
 * 
 * 
 * 
 * @license cobalt-core/license
 * @author Gardiner Bryant <gardiner@heavyelement.io>
 */
namespace CRUD;
use \Exceptions\HTTP\BadRequest;
use \CRUD\Exceptions\ValidationFailed;

class Validate{

  public $to_validate = [];
  
  protected $schema;
  
  public $result = [];
  
  public $failures = [];

  function __construct($to_validate,$http_mode = true){
    $this->to_validate = $to_validate;
    $this->mode = $mode;
  }

  function register_schema($instance_or_name,$args = []){
    if( is_string($instance_or_name) ) $instance_or_name = new $instance_or_name( ...array_values($args) );
    if( gettype($instance_or_name) !== "object" ) throw new Exception("Argument 1 of register_schema must be a string or object.");
    $this->schema = $instance_or_name;
  }

  function validate(){
    foreach($this->allowed_names as $name => $value){
      if(!method_exists($this->schema,$name)) continue;
      if( isset($this->schema->__disallowed_names) && key_exists($name,$this->schema->__disallowed_names) ) continue;
      $this->validate_field($value,$name);
    }
    if(!empty($this->failures) && $this->mode) throw new BadRequest("Validation failed",$this->failures);
    else if ($this->mode) return false;
    
    $complete = [];
    if(method_exists($this->schema, "__on_validation_complete")) $complete = $this->schema->__on_validation_complete($this->result,$this->to_validate);
    if(!is_array($complete)) throw new Exception("Results of __on_validation_complete must be an array.");
    return array_merge($complete,$this->result);
  }
  
  function validate_field($value,$name){
    try {
      // Execute the method for the current field
      $this->result[$name] = $this->schema->{$name}($value,$this->to_validate,$this->result);
    } catch ( ValidationFailed $e ){
      $this->failures[$name] = $e->getMessage();
    }
  }

}