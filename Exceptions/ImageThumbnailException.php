<?php
namespace Exceptions;

use Exception;

class ImageThumbnailException extends Exception{
    function __construct($message){
        parent::__construct($message);
    }
}