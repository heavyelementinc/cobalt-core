<?php
/**
 * This is the bootstrap config file. We use this to
 * Set up our database access. This file is read every
 * time the app is instantiated.
 */

$GLOBALS['CONFIG'] = [
    'db_driver'      => "MongoDB",         // The Cobalt Engine database driver to use to access the database (MongoDB is the only supported driver)
    'db_addr'        => "localhost:27017", // The database's address
    'db_port'        => '27017',           // The database port number
    'database'       => "" ,               // The name of your app's database
    'db_usr'         => "" ,               // The username for your database
    'db_pwd'         => "" ,               // The password for your database
    'db_ssl'         => false,             // Enable SSL communication between the app and database
    'db_sslFile'     => "" ,               // The SSL cert file for communicating with the database
    'db_invalidCerts'=> false,             // Allow self-signed certificates
];