<?php

use Cobalt\Documentation\Model\Documentation;
use Cobalt\Pages\Controllers\LandingPages;

Documentation::declare("Welcome", ["/admin/*"], __DIR__."/builtins/Welcome.md");
Documentation::declare("Pages & Posts", [LandingPages::className()."@__index",LandingPages::className()."@__new", LandingPages::className()."@__edit"], __DIR__ . "/builtins/Pages and Posts/Pages and Posts.md");
