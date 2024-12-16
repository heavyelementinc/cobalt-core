<?php

class CoreInit extends \Controllers\Pages {
  function prompt() {
    $isset = isset($_GET['e']);
    if ($isset) add_vars(['error' => "Oops. There was an error:\n\n" . base64_decode($_GET['e'])]);
    set_template("/initialization/create_user_prompt.html");
  }

  function insert() {
    // $location = $_SERVER['SERVER_NAME'];
    try {
      // Use the intialization create_user function to try and create a user
      __cobalt_initialize_create_user($_POST);
    } catch (Exception $e) {
      // If we have an error, let's redirect with an error message that will be
      // displayed by our template:
      $l = "Location: /?e=" . base64_encode($e->getMessage());
      header($l);
      die("You're being redirected");
    }
    // If we're here, the user insert has succeeded, so we redirect away from
    // this request which should result in an initialized site.
    header("Location: /");

    return view("/initialization/create_user_prompt.html");
  }
}
