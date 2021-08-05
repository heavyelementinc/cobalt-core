<?php

namespace Auth;

abstract class UserFields {
    /**
     * Returns a schema array. Example:
     * 
     * ```php
     * [
     *   'some_field' => [
     *     'get' => fn ($val) => $val ?? "default"
     *     'set' => function ($val) {
     *       return $val;
     *     }
     *   ]
     * ]
     * ```
     * 
     * @return array 
     */
    abstract function __get_additional_schema(): array;

    /**
     * Returns a path to a template to be used as the additional tab
     * 
     * The template will be passed all the data the normal individual page 
     * recieves. Check `/cobalt-core/controllers/CoreAdmin.php` for details.
     * 
     * @return string
     */
    abstract function __get_additional_user_tab(): string;
}
