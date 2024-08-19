<?php
/** This file is invoked when Cobalt detects that HTTP/2 is supported by the 
 * current webserver.
 */
header("Link: </core-content/css/material-design/css/material.min.css>; rel=preload; as=style");
if(config()['mode'] === COBALT_MODE_PRODUCTION) {
    // Handle JavaScript package loading
    header("Link: </core-content/js/package.js?".__APP_SETTINGS__['version'].">; rel=preload; as=script", false);
    header("Link: </core-content/css/package.css?".__APP_SETTINGS__['version'].">; rel=preload; as=style", false);
}