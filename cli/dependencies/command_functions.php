<?php
// require __CLI_ROOT__ . "/new_project.php";
function __help() {
    print("\n== COBALT HELP ==\n");
    foreach ($GLOBALS['cobalt_cli_commands'] as $cmd => $items) {
        print("$cmd\t\t$items[description]\n");
    }
    return "";
}

function __update() {
    return "Running update";
}


function __create_new_user() {
    print("Add a new user\n\n");
    $user = [];
    $user['uname'] = trim(readline("  Username > "));
    $user['pword'] = trim(readline("  Password > "));
    dbg($user);
    return "New user was not created";
}

function __new_project() {
    require_once __CLI_ROOT__ . "/new_project.php";
    $project = new NewProject();
    $project->__collect_new_project_settings();
    return "";
}

function __exit() {
    print("Goodbye\n");
    exit;
}

$GLOBALS['cobalt_cli_commands'] = [
    'help' => [
        'description' => 'Print this message',
        'callback' => '__help',
        'parse' => 0
    ],
    'init' => [
        'description' => 'Create a new project',
        'callback' => '__new_project',
        'parse' => 5
    ],
    // 'useradd' => [
    //     'description' => 'Add a new user',
    //     'callback' => '__create_new_user',
    // ],
    'update' => [
        'description' => 'Update your current project',
        'callback' => '__update',
        'parse' => 0
    ],
    'exit' => [
        'description' => 'Exit the current program',
        'callback' => '__exit',
        'parse' => 0
    ]
];

/*

Copyright (c) 2010, dealnews.com, Inc.
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:

 * Redistributions of source code must retain the above copyright notice,
   this list of conditions and the following disclaimer.
 * Redistributions in binary form must reproduce the above copyright
   notice, this list of conditions and the following disclaimer in the
   documentation and/or other materials provided with the distribution.
 * Neither the name of dealnews.com, Inc. nor the names of its contributors
   may be used to endorse or promote products derived from this software
   without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
POSSIBILITY OF SUCH DAMAGE.

 */

/**
 * show a status bar in the console
 * 
 * <code>
 * for($x=1;$x<=100;$x++){
 * 
 *     show_status($x, 100);
 * 
 *     usleep(100000);
 *                           
 * }
 * </code>
 *
 * @param   int     $done   how many items are completed
 * @param   int     $total  how many items are to be done total
 * @param   int     $size   optional size of the status bar
 * @return  void
 *
 */

function cli_show_status($done, $total, $size = 30) {

    static $start_time;

    // if we go over our bound, just ignore it
    if ($done > $total) return;

    if (empty($start_time)) $start_time = time();
    $now = time();

    $perc = (float)($done / $total);

    $bar = floor($perc * $size);

    $status_bar = "\r[";
    $status_bar .= str_repeat("=", $bar);
    if ($bar < $size) {
        $status_bar .= ">";
        $status_bar .= str_repeat(" ", $size - $bar);
    } else {
        $status_bar .= "=";
    }

    $disp = number_format($perc * 100, 0);

    $status_bar .= "] $disp%  $done/$total";

    if ($done !== 0) $rate = ($now - $start_time) / $done;
    else $rate = 0;
    $left = $total - $done;
    $eta = round($rate * $left, 2);

    $elapsed = $now - $start_time;

    $status_bar .= " remaining: " . number_format($eta) . " sec.  elapsed: " . number_format($elapsed) . " sec.";

    echo "$status_bar  ";

    flush();

    // when done, send a newline
    if ($done == $total) {
        echo "\n";
    }
}
