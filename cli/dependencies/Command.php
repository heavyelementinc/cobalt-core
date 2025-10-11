<?php
function versions($spacer = "  ") {
    say($spacer."ENG: ". fmt("v".__COBALT_VERSION, "s"));
    say($spacer."APP: " . fmt("v".__APP_SETTINGS__['version'], "i"));
    say($spacer."CID: " . fmt(VERSION_HASH,"w"));
    say($spacer."PHP: " . fmt(PHP_VERSION, "white"));
    print("\n");
    say($spacer."APP_ROOT: " . __APP_ROOT__);
    say($spacer."ENV_ROOT: " . __ENV_ROOT__);
}
class Command {
    
}