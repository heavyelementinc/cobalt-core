<?php

namespace Cobalt\DataModel\Models;

use Cobalt\DataModel\Types\DateType;
use Cobalt\DataModel\Types\DocumentType;
use Cobalt\DataModel\Types\NumberType;
use Cobalt\DataModel\Types\StringType;
use Override;

class FilesystemModel extends DocumentType {
    readonly NumberType $chunkSize;
    readonly StringType $filename;
    readonly NumberType $length;
    readonly DateType $uploadDate;
    // readonly StringType $for;
    readonly FilesystemMetaModel $meta;

    #[Override]
    public function getDefaultField(): StringType {
        return $this->filename;
    }

    #[Override]
    public function getCollectionName($string = null): string {
        return 'fs.files';
    }
    
}