<?php
namespace Cobalt\Model\Traits;



trait Viewable {
    /**
     * Returns the full path of the $path provided if it exists
     * or `null` if it does not.
     * 
     * @param string $path - The unique name of the template
     * @return string|null - The full path of the view OR null if file doesn't exist
     */
    static function get_view_path(string $path):string|null {
        return __DIR__;
    }
}