<?php

namespace Bga\Games\Sorry\Helpers;

trait DatabaseHelpers {
    /**
     * Execute a database query and return the result as an array of stdClass objects.
     *
     * This method executes the provided SQL query and maps the rows of the result set to an array of stdClass objects.
     * Snake-case database column names converted to camel-case property names.
     *
     * @param string $query The SQL query to execute.
     * @return \stdClass[] An array of stdClass objects representing the query result.
     */
    public static function getRowsFromDb(string $query): array {
        $result = [];
        foreach (self::DbQuery($query) as $row) {
            $stdClassResult = new \stdClass();
            foreach ($row as $dbKey => $value) {
                $phpKey = lcfirst(str_replace('_', '', ucwords($dbKey, '_')));
                $stdClassResult->$phpKey = $value;
            }
            $result[] = $stdClassResult;
        }
        return $result;
    }

    /**
     * Execute a database query and return a single row as an stdClass object.
     *
     * This method executes the provided SQL query and maps the first row of the result set to an stdClass object.
     * Snake-case database column names converted to camel-case property names.
     *
     * @param string $query The SQL query to execute.
     * @return \stdClass An stdClass object representing the first row of the query result.
     */
    public static function getFirstRowFromDb(string $query): \stdClass {
        $row = self::DbQuery($query)->getIterator()->current();
        $stdClassResult = new \stdClass();
        if (is_null($row))
            return $stdClassResult;

        foreach ($row as $dbKey => $value) {
            $phpKey = lcfirst(str_replace('_', '', ucwords($dbKey, '_')));
            $stdClassResult->$phpKey = $value;
        }
        return $stdClassResult;
    }
}
