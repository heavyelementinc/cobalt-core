<?php
class CoreAdmin extends \Controllers\Pages {
    function index(){
        add_vars(['title' => "Admin Panel"]);
        add_template("/authentication/admin-dashboard/index.html");
    }
    function manage_users(){
        $collection = \db_cursor('users');
        $list = "<ul>";
        foreach($collection->find([]) as $user){
            $list .= "<li><a href='/auth/manage/user/$user[uname]'>@$user[uname] &mdash; $user[fname] $user[lname]</a></li>";
        }
        $list .= "</ul>";
        add_vars([
            'title' => "Manage users",
            "users" => $list
        ]);
        add_template("/authentication/user-management/list-users.html");
    }
}