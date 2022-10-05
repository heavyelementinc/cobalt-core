<?php

namespace Cobalt\Payments;

class PaymentGateway extends \Drivers\Database{

    public function get_collection_name() {
        return "CobaltSettings";
    }

    public function get_gateway_data($gateway = "paypal") {
        return $this->findOneAsSchema(['provider' => $gateway,'type' => 'payment-gateway']);
    }

}