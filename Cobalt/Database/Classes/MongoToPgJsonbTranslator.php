<?php
namespace Cobalt\Database\Classes;

use Exception;

/**
 * 
 * @package Cobalt\Database\Classes
 */

class MongoToPgJsonbTranslator
{
    private $bindings = [];

    public function __construct(private string $jsonColumn = 'data') {
        
    }

    /**
     * Translates a MongoDB find() query.
     */
    public function translateFind(string $table, array $filter = [], array $options = []): array {
        $this->bindings = [];
        $where = $this->buildWhere($filter);
        
        $sql = "SELECT * FROM {$table}";
        if ($where !== '') {
            $sql .= " WHERE {$where}";
        }

        if (isset($options['sort'])) {
            $sql .= $this->buildSort($options['sort']);
        }
        if (isset($options['limit'])) {
            $sql .= " LIMIT " . (int)$options['limit'];
        }
        if (isset($options['skip'])) {
            $sql .= " OFFSET " . (int)$options['skip'];
        }

        return [
            'sql' => $sql,
            'bindings' => $this->bindings
        ];
    }

    /**
     * Translates a MongoDB updateOne() or updateMany() query.
     */
    public function translateUpdate(string $table, array $filter, array $update): array {
        $this->bindings = [];
        $where = $this->buildWhere($filter);
        
        if (!isset($update['$set'])) {
            throw new Exception("Currently only \$set updates are supported.");
        }

        $setSql = $this->buildSet($update['$set']);
        
        $sql = "UPDATE {$table} SET {$setSql}";
        if ($where !== '') {
            $sql .= " WHERE {$where}";
        }

        return [
            'sql' => $sql,
            'bindings' => $this->bindings
        ];
    }

    private function buildWhere(array $filter): string {
        if (empty($filter)) {
            return '';
        }

        $conditions = [];
        foreach ($filter as $field => $value) {
            if ($field === '_id') {
                $field = 'id'; // Map Mongo _id to Postgres id if applicable
            }

            if (is_array($value)) {
                // Handle operators like $gt, $lt, $in
                foreach ($value as $op => $opValue) {
                    $conditions[] = $this->parseOperator($field, $op, $opValue);
                }
            } else {
                // Exact match
                $param = $this->addBinding($value);
                // Use JSONB containment for exact matches in the JSON object
                $conditions[] = "{$this->jsonColumn}->>'{$field}' = {$param}";
            }
        }

        return implode(' AND ', $conditions);
    }

    private function parseOperator(string $field, string $operator, $value): string {
        $param = $this->addBinding($value);
        
        // Cast to numeric for mathematical comparisons
        $jsonField = "({$this->jsonColumn}->>'{$field}')::numeric";

        switch ($operator) {
            case '$gt': return "{$jsonField} > {$param}";
            case '$gte': return "{$jsonField} >= {$param}";
            case '$lt': return "{$jsonField} < {$param}";
            case '$lte': return "{$jsonField} <= {$param}";
            case '$ne': return "{$this->jsonColumn}->>'{$field}' != {$param}";
            case '$in': 
                if (!is_array($value)) throw new Exception('$in requires an array');
                $inBindings = array_map([$this, 'addBinding'], $value);
                $inString = implode(', ', $inBindings);
                return "{$this->jsonColumn}->>'{$field}' IN ({$inString})";
            default:
                throw new Exception("Unsupported operator: {$operator}");
        }
    }

    private function buildSet(array $setArgs): string {
        $currentColumn = $this->jsonColumn;
        
        // Postgres jsonb_set needs to be nested for multiple fields
        foreach ($setArgs as $field => $value) {
            $jsonValue = json_encode($value);
            $param = $this->addBinding($jsonValue);
            // jsonb_set(target, path, new_value, create_missing)
            $currentColumn = "jsonb_set({$currentColumn}, '{ {$field} }', {$param}::jsonb, true)";
        }
        
        return "{$this->jsonColumn} = {$currentColumn}";
    }

    private function buildSort(array $sortArgs): string {
        $sorts = [];
        foreach ($sortArgs as $field => $direction) {
            $dir = ($direction === 1 || $direction === 'asc') ? 'ASC' : 'DESC';
            $sorts[] = "{$this->jsonColumn}->>'{$field}' {$dir}";
        }
        return empty($sorts) ? '' : " ORDER BY " . implode(', ', $sorts);
    }

    private function addBinding($value): string {
        $this->bindings[] = $value;
        return '?';
    }
}