<?php
// Let's make sure we're running a suppoted version of PHP (since we use 
// the not-insane array syntax and the spread "..." syntax)

if (!version_compare(PHP_VERSION, "8.1", ">=")) die("You must be running PHP version 8.1.0 or greater (".PHP_VERSION.")");

/* Cobalt Version Number */
define("__COBALT_VERSION", "2.0");



// The following are PHP dependencies
$dependencies = [
    "gd",          "curl",       "dom",      "json",     "mongodb",
    "mbstring",    "bcmath",     "libxml",   "mcrypt",
    "openssl",     "protobuf",   "yaml",
    // "ERROR FOR TESTING PURPOSES"
];

$missing = "";

// Let's ensure that we have all the required dependencies.
foreach($dependencies as $dependency) {
    if(!extension_loaded($dependency)) $missing .= " $dependency";
}

if($missing !== "") die("Your environment is misconfigured! Please install the following required packages.<br>$missing");