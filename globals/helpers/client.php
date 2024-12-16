<?php

use Handlers\ApiHandler;

/**
 * Special Query Keywords
 *   @form - References the form-request element that submitted the request (if any)
 *   @cookie - [set, remove] Updates a cookie value through an async reques
 *   sessionStorage - [set, remove] Stores or removes sessionStorage data
 *   localStorage - [set, remove] Stores or removes localStorage data
 * 
 * Implemented Update Parameters
 *   value - <mixed> Modify the value property of the target
 *   dispatchEvent - [event => [detail => [...]]] Dispatches an event on the target
 *   innerHTML - <string> Updates the innerHTML of the element
 *   outerHTML - <string> Updates the outerHTML of the element
 *   invalid - [true|false] Sets the `invalid` property
 *   delete - [true] Deletes the target element
 *   remove - [true] Removes the target element | <string> Queries the target for the given string and removes matching element(s)
 *   message - <string> will provide a message to the end user. It will look like a ValidationIssue
 *   src - <string> update the src attribute for an img tag
 *   attribute - <string> update arbitrary attribute
 *   attributes - <array> update a list of attributes
 *   style - <array> update a list of styles
 *   clear - [true] (@form only) - Clears the form that initiated this request
 * 
 * @param string $query 
 * @param array $value 
 * @return void 
 */
function update(string $query, array $value) {
    global $context_processor;
    if($context_processor instanceof ApiHandler === false) return;
    $context_processor->update_instructions[] = ['target' => $query, ...$value];
}

/**
 * Redirect will set a response header of either "X-Location" if "X-Request-Source"
 * is among request headers or "Location" if it's not.
 * @param string $path - The path to redirect to
 * @return void 
 */
function redirect(string $path) {
    $headers = getHeader("X-Request-Source", null, true, false);
    // If the request was sent via AsyncFetch, return `X-Location` header
    if($headers) {
        header("X-Redirect: $path");
        return;
    }
    // Otherwise, return `Location` header
    header("Location: $path");
}

/**
 * Sets a response header using the `redirect` function and then exits.
 * @param string $path 
 * @return never 
 */
function redirect_and_exit(string $path): never {
    redirect($path);
    exit;
}

/**
 * Supply a custom content group name and this function will return a hyperlink
 * for authorized user accounts where they can edit content.
 * 
 * Use this to manually place an edit link for groups of like content.
 * @param string $group 
 * @return string 
 * @throws Exception 
 */
function edit_link($group) {
    try {
        if(!has_permission("Customizations_modify", null, null, false)) return "";
    } catch (\Exceptions\HTTP\Unauthorized $e) {
        return "";
    }
    return "<a class='custom-element-edit-link' href='/admin/customizations/".urlencode($group)."'><i name='pencil'></i><span style='display: none'>Edit This Customization</span></a>";
}