<?php

namespace Cobalt\DefinedModel\Traits;

use Cobalt\Controllers\Traits\CreateableModel;
use Cobalt\Controllers\Traits\DestroyableModel;
use Cobalt\Controllers\Traits\EditableModel;
use Cobalt\Controllers\Traits\IndexableModel;
use Cobalt\Controllers\Traits\ModalQueryMethods;
use Cobalt\Controllers\Traits\ReadableModel;
use Cobalt\Controllers\Traits\SearchableModel;
use Cobalt\Controllers\Traits\SortableModel;
use Cobalt\Controllers\Traits\UpdateableModel;

trait ModelCRUD {
    use ModelUpdate;
}