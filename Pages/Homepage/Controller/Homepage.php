<?php

namespace Pages\Homepage\Controller;

use Cobalt\Routing\Controllers\BasicController;
use Override;

class Homepage extends BasicController {
    #[Override]
    public function index(): mixed {
        return view("Pages/Homepage/templates/homepage.php");
    }
}
