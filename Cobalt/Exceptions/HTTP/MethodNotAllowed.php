<?php
/** The MethodNotAllowed Exception denotes a request method (for example,
 * a GET request) missing the required POST body, or a PUT request
 * on a read-only resource.
 */
namespace Exceptions\HTTP;
class MethodNotAllowed extends HTTPException{
    public $status_code = 405;
    public $name = "Method Not Allowed";
}