<?php

namespace Cobalt\Payments;

use Validation\Normalize;

class PaymentGatewaySchema extends Normalize {

    public function __get_schema(): array {
        return [
            'type' => [
                'set' => fn() => 'payment-gateway'
            ],
            'enable' => [
                'set' => fn($val) => $this->boolean_helper($val)
            ],
            'secret' => [],
            'token' => [],
            'provider' => [
                'valid' => [
                    'stripe' => 'Stripe',
                    'paypal' => 'PayPal'
                ]
            ]
        ];
    }
    
}