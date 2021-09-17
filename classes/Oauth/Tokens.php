<?php

namespace Oauth;

use Exception;

abstract class Tokens extends \Drivers\Database {

    abstract function type_name(): string;
    abstract function translation_matrix(): array;

    public function fetch_tokens() {
        $type_name = $this->type_name();
        $result = $this->findOne(['type' => $type_name]);
        if ($result === null) throw new Exception("No $type_name tokens registered.");

        return $result;
    }

    public function register_tokens(array $tokens) {
        $type_name = $this->type_name();
        $mutant = ['type' => $type_name];
        $mutant += $this->prep_for_storage($tokens);

        $result = $this->updateOne(['type' => $type_name], ['$set' => $mutant]);
    }

    public function translate_back($tokens) {
    }

    /**
     * Uses the $translation_matrix to conver the API's resulting data into a
     * common storage format.
     * @param array $tokens 
     * @return array tokens in a format fit for storage
     * @throws Exception 
     */
    private function prep_for_storage(array $tokens, $matrix = null) {
        if ($matrix === null) $matrix = $this->translation_matrix();
        $storable = [];
        foreach ($matrix as $output => $input) {
            if (!in_array($input, $tokens)) throw new Exception("Invalid data");
            $storable[$output] = $tokens[$input];
        }
        $this->storage_sanity_check($storable); // Will throw if $required_fields are missing from $storable
        return $storable;
    }



    private function storage_sanity_check($processed) {
        $required_fields = ['type', 'client_id', 'api_version', 'client_secret', 'access_token', 'refresh_token'];
        foreach ($required_fields as $field) {
            if (!key_exists($field, $processed)) {
                throw new Exception("A required token field was not found");
            }
        }
    }


    public function get_collection_name() {
        return "OauthTokens";
    }
}
