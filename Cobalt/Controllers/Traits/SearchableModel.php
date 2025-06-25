<?php

namespace Cobalt\Controllers\Traits;

use Exceptions\HTTP\Unauthorized;
use MongoDB\BSON\Regex;

trait SearchableModel {
    protected array $searchableFields = [];

    private function getSearchQuery(string &$searchParam, array &$searchQuery, array &$searchOptions, array &$searchableFields, array &$searchableOptions) {
        $this->parseSearchParam($searchParam, $searchQuery, $searchOptions, $searchableFields, $searchableOptions);
    }

    private function parseSearchParam(
        string &$searchParam,
        array &$searchQuery,
        array &$searchOptions,
        array &$searchableFields,
        array &$searchableOptions
    ):void {
        if($searchParam[0] !== QUERY_PARAM_SEARCH_FIELD_TOKEN) {
            // If there are no fields specified in the user query, let's use the
            // searchable fields from the model
            $searchableFields = $this->searchableFields;
            $searchQuery = ['$text' => [
                '$search' => $searchParam
            ]];
            $searchOptions = [
                'projection' => [
                    QUERY_SEARCH_MATCH_SCORE_FIELD => ['$meta' => 'textScore']
                ],
                'sort' => [
                    QUERY_SEARCH_MATCH_SCORE_FIELD => ['$meta' => 'textScore']
                ]
            ];
            return;
        }
        // Let's assume we've been sent a search string with multiple fields. It would look like this
        // @field:value:somevalue,@field2.child:value2,@field3:value
        $fields = explode(QUERY_PARAM_SEARCH_DELIMITER_TOKEN . QUERY_PARAM_SEARCH_FIELD_TOKEN, substr($searchParam,1));
        // Fields will now be an array of this structure:
        // ['field:value:somevalue', 'field2.child:value2', 'field3:value']
        foreach($fields as $f) {
            // Iteration 0 through this loop will result in $f looking like this:
            // 'field:value:somevalue'
            $firstIndexOfToken = strpos($f, QUERY_PARAM_SEARCH_VALUE_TOKEN);
            $field = substr($f, 0, $firstIndexOfToken);
            $value = substr($f, $firstIndexOfToken + 1);
            // $search[$field] = $value;
            $this->parseSearchValue($value, $field, $searchQuery, $searchOptions, $searchableFields, $searchableOptions);
            // After the above instruction, we should have an array that looks like this:
            // $search = ["field" => "value:somevalue"];
        }
        if(!empty($searchableFields)) {
            $searchQuery = ['$text' => [
                '$search' => $searchParam
            ]];
            $searchOptions = [
                'projection' => [
                    QUERY_SEARCH_MATCH_SCORE_FIELD => ['$meta' => 'textScore']
                ],
                'sort' => [
                    QUERY_SEARCH_MATCH_SCORE_FIELD => ['$meta' => 'textScore']
                ]
            ];
        }
    }

    private function parseSearchValue(
        string &$searchParam,
        string &$fieldName,
        array &$searchQuery,
        array &$searchOptions,
        array &$searchableFields,
        array &$searchableOptions
    ):void {
        $regexStartEndDelimiter = "/";
        $validRegexTerminationChars = [
            $regexStartEndDelimiter,
            REGEXP_CASE_INSENSITIVE,
            REGEXP_MULTILINE_START_END,
            REGEXP_EXTENDED_IGNORE_WHITESPACE,
            REGEXP_MATCH_NEW_LINE_SPACE_CHAR,
            REGEXP_UNICODE_SUPPORT,
        ];
        $finalSearchParamChar = $searchParam[strlen($searchParam) - 1];
        // Check if the first character of the search param is a '/'
        if($searchParam[0] !== $regexStartEndDelimiter
            // Check if the last character of the search param is a valid final character for a regex
            && !in_array($finalSearchParamChar, $validRegexTerminationChars)
        ) {
            // $searchQuery[$fieldName] = $searchParam;
            if(!key_exists($fieldName, $this->searchableFields) 
            && !has_permission("Model_advanced_search_permission", null, null, false)) {
                throw new Unauthorized("`$fieldName` is outside of model's searchable fields", "Unauthorized");
            }
            $searchableFields[$fieldName] = "text";
            return;
        }
        if(!key_exists($fieldName, $this->searchableFields) 
            && !has_permission("Model_advanced_search_permission", null, null, false)
        ) {
            throw new Unauthorized("`$fieldName` is outside of model's searchable fields", "Unauthorized");
        }
        $regexFlags = "";
        $finalSearchParamDelimiterPosition = strrpos($searchParam, $regexStartEndDelimiter);
        if($finalSearchParamChar !== $regexStartEndDelimiter) {
            $regexFlags = substr($searchParam, $finalSearchParamDelimiterPosition + 1);
            $searchParam = substr($searchParam, 0, $finalSearchParamDelimiterPosition + 1);
        }
        $searchQuery[$fieldName] = new Regex(substr($searchParam,1,-1), $regexFlags);
    }

    final protected function get_search_field() {
        QUERY_PARAM_COMPARISON_STRENGTH;

        return "<input type=\"search\" name=\"".QUERY_PARAM_SEARCH."\" value=\"".htmlspecialchars($_GET[QUERY_PARAM_SEARCH])."\" placeholder=\"Search\">
        <button type=\"submit\" native><i name=\"magnify\"></i></button>
        <inline-menu>
            <label><input type=\"checkbox\" name=\"".QUERY_PARAM_SEARCH_CASE_SENSITVE."\"".(($_GET[QUERY_PARAM_SEARCH_CASE_SENSITVE] === 'on') ? " checked=\"checked\"": "")."> Case Sensitve</label>
        </inline-menu>
        ";
    }

    final protected function get_searchable(string $field, array $directives) {
        $index = $directives['index'] ?? [];
        $searchable = $index['searchable'] ?? false;
        if(is_callable($searchable)) $searchable = $searchable($field, $directives);
        if($searchable === true) $this->searchableFields[$field] = "text";
        return $searchable;
    }
}