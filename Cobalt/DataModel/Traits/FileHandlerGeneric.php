<?php

namespace Cobalt\DataModel\Traits;

use Cobalt\DataModel\Types\Generic;

/**
 * @mixin Generic
 */
trait FileHandlerGeneric {
    use FileHandler;
    protected function filterFilenameDirective(array &$toValidate, string &$filename, bool &$addExtension) {
        $filename_directive = $this->directives->filename;
        
        if($filename_directive) {
            $filename = $filename_directive->call($filename);
            $addExtension = true;
        }
    }

    protected function filterObscureFilename(array &$toValidate, string &$filename, bool &$addExtension) {
        $obscure_filename = $this->directives->obscure_filename?->value ?? false;
        if($obscure_filename) {
            $filename = guidv4($filename);
            $addExtension = true;
        }
    }
    
    protected function filterFilenameIsUnique(array &$toValidate, string &$filename, bool &$addExtension) {
        $count = $this->__count(['filename' => $filename], []);
        if($count >= 1) {
            $filename = "$filename-$count";
        }
        $addExtension = true;
    }
    
}