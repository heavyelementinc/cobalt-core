<?php
namespace MongoDB\BSON;

class UTCDateTime {
    public function toDateTime() {
        return new \DateTime();
    }
}