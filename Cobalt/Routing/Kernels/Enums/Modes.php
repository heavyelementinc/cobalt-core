<?php

namespace Cobalt\Routing\Kernels\Enums;

enum Modes: string {
    case PLAINTEXT = "text/plain";
    case TEXT_HTML = "text/html";
    case APPLICATION_JSON = "application/json";
    case TEXT_MARKDOWN = "text/markdown";
    case MULTIPART_FORMDATA = "multipart/form-data";
}
