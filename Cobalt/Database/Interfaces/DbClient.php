<?php

namespace Cobalt\Database\Interfaces;

interface DbClient {
    /**
     * Retrieves an arbitrary collection/table from the database
     * @param string $collectionName 
     * @return DbDatabase 
     */
    function getDatabase(string $collectionName, array $options = []):DbDatabase;
    
    // public function __get(string $string):DbDatabase;

    /**
     * @return void 
     */
    function configureDatabase():void;

    /**
     * Set the name of the current database
     * @return string 
     */
    function __toString():string;

}