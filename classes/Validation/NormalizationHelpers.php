<?php

namespace Validation;

use Validation\Exceptions\ValidationIssue;
use Validation\Exceptions\ValidationFailed;

abstract class NormalizationHelpers {
    /**
     * After validation, private fields will be merged to the result.
     * 
     * @param array $mutant the validated fields
     * @return array an array of private fields
     */
    protected function __merge_private_fields($mutant): array {
        return [];
    }

    /** Set a field's value from within another function.
     * 
     * For example, if you want to validate a password AND change a flag that 
     * the password has been changed.
     * 
     * @param string $field the field name
     * @param mixed $value the value to be set
     * @return void 
     */
    protected function __modify($field, $value) {
        $this->__dataset[$field] = $value;
    }

    /**
     * Checks if value is a bool and throws a ValidationIssue if not.
     * 
     * @param mixed $value 
     * @return bool 
     * @throws ValidationIssue 
     */
    final protected function boolean_helper($value) {
        if (!is_bool($value)) throw new ValidationIssue("Must be true or false");
        return $value;
    }

    /**
     * Hex color validation. If $default is anything other than null, then $default
     * will be set as the value and no error will be thrown.
     * 
     * @param string $val The hex color starting with a #
     * @param null|string $val The default hex value to use
     * @return string Uppercased 6 digit hex color starting with #
     */
    final protected function hex_color($val, $default = null) {
        if (!$val && $default !== null) return $default;
        if (strlen($val) > 8) throw new ValidationIssue("Not a hex color.");
        $pattern = "/^#[0-9A-Fa-f]{3,6}$/";
        if (!preg_match($pattern, $val)) throw new ValidationIssue("Not a hex color.");
        if (strlen($val) === 4) $val = "#$val[1]$val[1]$val[2]$val[2]$val[3]$val[3]";
        return strtoupper($val);
    }

    /**
     * Returns the input $value if, and only if, the comparison of the two hex
     * value's luminosity meets the $threshold. Default is 5.
     * 
     * For best accessibility, 5 is optimum.
     * 
     * @param string $val the hex color to evaluate
     * @param string $comparisonHex the hex color used as a baseline for comparison
     * @param int|float $threshold minum luminosity difference. Min 0, max 5 (default);
     * @return int|float 
     * @throws ValidationIssue 
     */
    final protected function contrast_color($value, $comparisonHex, $threshold = 5) {
        // Normalize our inputs and error out if invalid hex
        $val = $this->hex_color($value);
        $comp = $this->hex_color($comparisonHex);

        // Color split
        [$R1, $G1, $B1] = $this->color_split($val);
        [$R2, $G2, $B2] = $this->color_split($comp);

        $L1 = 0.2126 * pow($R1 / 255, 2.2) +
            0.7152 * pow($G1 / 255, 2.2) +
            0.0722 * pow($B1 / 255, 2.2);

        $L2 = 0.2126 * pow($R2 / 255, 2.2) +
            0.7152 * pow($G2 / 255, 2.2) +
            0.0722 * pow($B2 / 255, 2.2);

        if ($L1 > $L2) {
            $result = ($L1 + 0.05) / ($L2 + 0.05);
            $scale = "brighter";
        } else {
            $result = ($L2 + 0.05) / ($L1 + 0.05);
            $scale = "darker";
        }
        if ($result < $threshold) {
            throw new ValidationIssue("This color must be $scale for readability purposes.");
        }
        return $val;
    }

    final protected function color_split($val) {
        if ($val[0] === "#") $val = substr($val, 1);
        return [
            hexdec($val[0] . $val[1]),
            hexdec($val[2] . $val[3]),
            hexdec($val[4] . $val[5]),
        ];
    }

    /**
     * Validates an email address
     * 
     * Trims and email and validates its formatting using filter_var
     * 
     * 
     * @param string $value 
     * @return string validated email address
     * @throws ValidationIssue upon failed validation
     */
    final protected function validate_email(string $value) {
        if (!$value) return "";
        $value = trim($value);
        if (!\filter_var($value, FILTER_VALIDATE_EMAIL)) throw new ValidationIssue("Malformed email");
        return strtolower($value); // We can return here because we know we have a valid email
    }

    /**
     * Validate a phone number and removes junk characters: () -
     * 
     * Removes junk characters /[()-\s]/ and checks if the resulting string contains
     * only digits.
     * 
     * @param string $value the phone number to be evaluated
     * @param int $min_length the minimum number of characters the string should be
     * @return string validated string of digits
     * @throws ValidationIssue 
     */
    final protected function validate_phone($value, $min_length = 10) {
        if (!$value) return "";
        $value = phone_number_normalize($value);

        // Check if the phone number is only digits and if not throw an exception.
        if (!ctype_digit($value)) throw new ValidationIssue("Malformed phone number");

        if (strlen($value) < $min_length) throw new ValidationIssue("Not long enough");

        return $value;
    }

    final protected function format_phone($value, $fmt = "(ddd) ddd-dddd") {
        return phone_number_format($value, $fmt);
    }

    /**
     * Make a microsecond timestamp from a date string. E.g. "2021-12-31 21:40"
     * 
     * If null is passed, the current time will be used.
     * 
     * If an integer is provided, it will be assumed to be a Unix timestamp in
     * seconds.
     * 
     * @param string|int $value the date string
     * @return object database driver's timestamp
     */
    final protected function make_date($value = null) {
        $date = new \Drivers\UTCDateTime($value);
        return $date->timestamp;
    }

    /**
     * Checks if a numeric value is between a specific range.
     * 
     * @param int $val The value to be considered
     * @param int $min The minimum value
     * @param int $max The maximum value
     * @return int the original value
     * @throws ValidationIssue 
     */
    final protected function min_max($val, $min, $max) {
        if ($val < $min) throw new ValidationIssue("Value must be greater than $min");
        if ($val > $max) throw new ValidationIssue("Value must be less than $max");
        return $val;
    }

    /**
     * Use this in your `set` method to store the timestamp as a single value in
     * one field.
     * 
     * ```php
     * 'date' => [
     *      'set' => false
     * ],
     * 'time' => [
     *      'set' => fn ($val) => $this->set_date_time('date',$val)
     * ]
     * ```
     * 
     * @param string $date the key to look up the correct date string
     * @param mixed $time the actual time value
     * @return object 
     * @throws ValidationIssue 
     */
    final protected function set_date_time($date, $time) {
        if (!key_exists($date, $this->__to_validate)) throw new ValidationIssue("Missing field $date");
        $str = $this->__to_validate[$date] . " $time";
        return $this->make_date($str);
    }

    /**
     * Date comparison and sanity check.
     * 
     * Determines if the start and end times happen in chronological order,
     * throws an Issue if not so.
     * 
     * @param string $date The submitted data's key to use as date/time  
     * @param string $start_time The submitted data's key to use as date/time  
     * @param string $end_time The submitted data's key to use as date/time  
     * @param string|null $end_date The submitted data's key to use as date/time  
     * @return string $value 
     * @throws ValidationIssue 
     */
    final protected function date_sanity_check($value, $date, $start_time, $end_time, $end_date = null) {
        if ($end_date === null) $end_date = $date;
        $start = $this->make_date($this->__to_validate[$date] . " " . $this->__to_validate[$start_time]);
        $end = $this->make_date($this->__to_validate[$end_date] . " " . $this->__to_validate[$end_time]);
        if ($end < $start) {
            throw new ValidationIssue("This event must start before it ends.");
        }
        return $value;
    }

    /**
     * Fails if most falsey values are found
     * 
     * **NOTE** that values *0, 0.0,* and *"0"* are allowed but other falsey
     * values will result in a failure of this check.
     * 
     * Boolean `false` is, by default, considered a value.
     * 
     * @param mixed $value the value to check if empty
     * @param bool $allow_false [true] bool false should be considered a value
     * @return mixed $value does not modify the value
     * @throws ValidationIssue 
     */
    final protected function required_field($value, $allow_false = true) {
        if ($allow_false && $value === false) return $value;
        if (empty($value) && !is_numeric($value)) throw new ValidationIssue("This field is required");
        return $value;
    }

    /**
     * Test if $value and a comparison field are empty and raise issue.
     * 
     * Meant to be used when one or another field are required to have a value.
     * For example, if a form has a phone number or email and one is required.
     * If both fields are empty then a ValidationIssue will be thrown.
     * 
     * 
     * @param mixed $value the value of the current field
     * @param mixed $other_field the other field name to test
     * @param string $message 
     * @param bool $allow_false [true] if false should be considered a value
     * @return mixed $value this method does not modify $value
     * @throws Exception if $other_field not found in __to_validate
     * @throws ValidationIssue if both values are considered empty
     */
    final protected function one_required($value, $other_field, $message = "One of these fields needs to be specified", $allow_false = true) {
        if (!isset($this->__to_validate[$other_field])) throw new \Exception("Field '$other_field' does not exist");
        if (empty($value) && empty($this->__to_validate[$other_field]))
            throw new ValidationIssue($message);

        return $value;
    }


    /**
     * Convert dollars to cents
     * 
     * @param int|float|string $val the value to be converted
     * @return int|float 
     * @throws ValidationIssue 
     */
    final protected function dollars_to_cents($val) {
        if (gettype($val) === "string") $val = str_replace(['$', ','], "", $val);
        if (!is_numeric($val)) throw new ValidationIssue("Must be a dollar value");
        return $val * 100;
    }

    /**
     * Convert cents to dollars
     * 
     * @param int|string $val 
     * @return string 
     */
    final protected function cents_to_dollars($val) {
        return cents_to_dollars($val);
    }

    /**
     * Escape HTML and trim whitespace
     * 
     * @param string $value the value to sanitize
     * @return string sanitized user input
     */
    final protected function sanitize($value) {
        return trim(htmlspecialchars($value));
    }

    final protected function url_fragment_sanitize($value) {
        $mutant = strtolower($value);
        // Remove any character that isn't alphanumerical and replace it with a dash
        $mutant = preg_replace("/([^a-z0-9])/", "-", $mutant);
        // Remove any consecutive dash
        $mutant = preg_replace("/(-){2,}/", "", $mutant);

        if (!$mutant || $mutant === "-") throw new ValidationIssue("\"$value\" is not suitable to transform into a URL fragment");

        return $mutant;
    }

    final protected function valid_url($url) {
        if (filter_var($url, FILTER_VALIDATE_URL)) return $url;
        throw new ValidationIssue("Must be a valid url");
    }
}
