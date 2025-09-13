<?php

namespace Cobalt\Settings\Define;

enum FieldTypes: string {
    case input = "input";
    case url = "url";
    case number = "number";
    case textarea = "textarea";
    // case input-number = "input-number";
    case password = "password";
    case bool = "bool";
    case array = "array";
    case radio = "radio";
    case binary = "binary";
    case select = "select";
    case date = "date";
}
