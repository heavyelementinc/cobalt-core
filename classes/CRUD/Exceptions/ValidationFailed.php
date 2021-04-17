<?php
namespace CRUD\Exceptions;
class ValidationFailed extends \Exception{
  function __construct($message){
    parent::__construct($message);
  }
}