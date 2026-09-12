<?php

namespace Cobalt\DataModel\Interfaces;
use Cobalt\DataModel\Types\DocumentType;
/**
 * @mixin DocumentType
 * @package Cobalt\DataModel\Interfaces
 */
interface Migration {
    /**
     * Called once before querying documents
     * @param array &$query 
     * @return void 
     */
    static function _getQuery(array &$query):void;

    static function _getOptions(array $query, array &$options):void;

    /**
     * Called for each document returned by _onLookup
     * @param array &$rawDocument 
     * @return void 
     */
    static function _onModify(array &$rawDocument, array &$updateDocument):void;

    /**
     * This method gives you the option to modify defaults for the update query
     * @param array $updateDocument 
     * @return void
     */
    static function _onUpdate(array &$updateQuery, array &$updateDocument, array &$updateOptions):void;
}