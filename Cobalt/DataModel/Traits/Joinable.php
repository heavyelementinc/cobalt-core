<?php

namespace Cobalt\DataModel\Traits;

use Cobalt\DataModel\Types\ArrayType;
use Cobalt\DataModel\Types\DictionaryType;
use Cobalt\DataModel\Types\DocumentType;
use MongoDB\BSON\ObjectId;

trait Joinable {
    readonly DocumentType $__foreignDocument;
    /**
     * Adds the values necessary for a `$lookup` operation
     * @param array &$pipeline 
     * @return void 
     */
    abstract function __getLookupPipeline(array &$pipeline):void;
    function __getJoinedDocument():DocumentType {
        return $this->__foreignDocument;
    }
    function __getJoinedDocumentId():ObjectId {
        return $this->getValue();
    }
    function __setJoinedDocument(array|DocumentType $document):void {
        $this->setValue($document->__id);
        $this->__foreignDocument = $document;
    }
}