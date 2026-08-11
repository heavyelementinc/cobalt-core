<?php
namespace Exceptions\HTTP;
class PaymentRequired extends HTTPException{
    public $status_code = 402;
    public $name = "Payment Required";

}