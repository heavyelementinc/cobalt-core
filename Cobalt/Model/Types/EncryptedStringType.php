<?php

namespace Cobalt\Modal\Types;

use Cobalt\Model\Exceptions\ImmutableTypeError;
use Cobalt\Model\Types\StringType;
use Exception;
use RangeException;

use const Cobalt\Model\Types\DIRECTIVE_KEY_IMMUTABLE;

class EncryptedStringType extends StringType {
    function filter($value) {
        if(mb_strlen(__APP_SETTINGS__['app_secret'], '8bit') !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            throw new RangeException('Key is the incorrect size');
        }
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = base64_encode($nonce.sodium_crypto_secretbox($value, $nonce, __APP_SETTINGS__['app_secret']));
        sodium_memzero($value);
        return $cipher;
    }

    public function getValue() {
        return $this->decrypt(parent::getValue());
    }

    function decrypt($encrypted) {
        $key = __APP_SETTINGS__['app_secret'];
        $decoded = base64_decode($encrypted);
        $nonce = mb_substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
        $ciphertext = mb_substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');
        
        $plain = sodium_crypto_secretbox_open(
            $ciphertext,
            $nonce,
            $key
        );
        if (!is_string($plain)) {
            throw new Exception('Invalid MAC');
        }
        sodium_memzero($ciphertext);
        sodium_memzero($key);
        return $plain;
    }
}