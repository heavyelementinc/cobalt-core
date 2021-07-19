<?php

/**
 * FlexTable - Render a <flex-table/> in a simple, consistent way.
 * 
 */

namespace Render;

interface FlexTableInterface extends FlexTable {
    public function table_layout(): array;
}
