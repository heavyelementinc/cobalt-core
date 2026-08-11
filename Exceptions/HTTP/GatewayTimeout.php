<?php
namespace Exceptions\HTTP;
class GatewayTimeout extends HTTPException{
    public $status_code = 504;
    public $name = "Gateway Timeout";
}