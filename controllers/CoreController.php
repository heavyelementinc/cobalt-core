<?php
class CoreController extends \Controllers\Pages {
    function index() {
    }


    function admin_redirect() {
        // Redirect to the admin panel
        header("Location: /admin/");
        exit;
    }
}
