<?php

namespace Cobalt\Database\Interfaces;


interface DeleteResult {
    // public function __construct(private $result, private mixed $insertedId);
    public function getDeletedCount():int;

    public function isAcknowledged():bool;
}