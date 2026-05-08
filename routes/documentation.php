<?php

use Cobalt\Documentation\Controllers\Documentation;
use Routes\Options;

Documentation::get(new Options("/index/", "list"));
Documentation::get(new Options("/read/{id}", "individual"));

