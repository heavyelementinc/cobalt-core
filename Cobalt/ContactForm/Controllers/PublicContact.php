<?php

namespace Cobalt\ContactForm\Controllers;

use Cobalt\Controllers\Controller;

class PublicContact extends Controller {
    public function form() {
        return view("Cobalt/ContactForm/templates/web/stage-1--contact-form.php");
    }

    public function submission_success() {
        return view("Cobalt/ContactForm/templates/web/stage-3--submission-success.php");
    }
}