<?php

namespace Cobalt\Routing\Enums;

enum HttpMethods:string {
    case HEAD = "head";
    case GET = "get";
    case POST = "post";
    case PUT = "put";
    case DELETE = "delete";
}
