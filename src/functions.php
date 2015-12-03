<?php
if (!function_exists('array_column')) {

    /**
     *
     * @param array $array
     * @param mixed $column_key
     * @param mixed $index_key
     * @return array
     * @link http://php.net/manual/en/function.array-column.php#116301
     */
    function array_column (array $array, $column_key, $index_key = null) {
        $result = [];

        foreach ($array as $element) {
            if (!is_array($element)) {
                continue;
            }

            if (!array_key_exists($column_key, $element)) {
                continue;
            }

            $value = $element[$column_key];

            if (!is_null($index_key) && array_key_exists($index_key, $element)) {
                $result[$element[$index_key]] = $value;
            } else {
                $result[] = $value;
            }
        }

        return $result;
    }
}

/**
 *
 * @param DateInterval $di
 * @return integer
 */
function di_to_seconds (DateInterval $di) {
    return ($di->days * 24 * 60 * 60) + ($di->h * 60 * 60) + ($di->i * 60) +
         $di->s;
}

/**
 * Convert nested array to nested set with lft, rgt and level values.
 *
 * @param array[] $data Nested arrays
 * @param string $childrenName Name of children array attribute
 * @return array[]
 * @link
 *       http://www.slideshare.net/ustimenko-alexander/nested-set?ref=http://www.slideshare.net/slideshow/embed_code/15338507
 */
function enumerate_nested_array (array $data, $childrenName, &$counter = null,
    $level = 1) {
    $result = [];

    if (is_null($counter)) {
        $counter = 0;
    }

    foreach ($data as $node) {
        if (is_array($node) && array_key_exists($childrenName, $node) &&
             is_array($node[$childrenName])) {
            $children = $node[$childrenName];
        } else {
            $children = [];
        }

        unset($node[$childrenName]);

        $temp = $node;
        $temp['ns_lft'] = ++$counter;
        $newresult = enumerate_nested_array($children, $childrenName, $counter,
            $level + 1);
        $temp['ns_rgt'] = ++$counter;
        $temp['ns_lvl'] = $level;

        $result[] = $temp;
        $result = array_merge($result, $newresult);
    }

    return $result;
}

/**
 *
 * @param string $data
 * @return boolean
 */
function is_base64_string ($data) {
    // @link http://stackoverflow.com/a/10797086/3155344
    return base64_encode(base64_decode($data, true)) === $data;
}

/**
 * Use with caution!
 *
 * @param string $input
 * @return string
 * @link http://stackoverflow.com/a/1993772/3155344
 */
function from_camel_case ($input) {
    preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!',
        $input, $matches);
    $ret = $matches[0];
    foreach ($ret as &$match) {
        $match = $match == strtoupper($match) ? strtolower($match) : lcfirst(
            $match);
    }
    return implode('_', $ret);
}

/**
 * Join all values of given arrays to each other.
 * Produce array in which each
 * row is a vector of points in the all given arrays.
 *
 * @param array $arr1
 * @param array ...
 * @throws InvalidArgumentException
 * @return array
 */
function join_arrays () {
    $result = [];

    $args = func_get_args();
    if (count($args) == 1 && is_array($args)) {
        $args = current($args);
    }
    if (!is_array($args)) {
        throw new InvalidArgumentException('Arguments should be arrays');
    }
    foreach ($args as &$arg) {
        if (!is_array($arg)) {
            throw new InvalidArgumentException('Arguments should be arrays');
        }
    }
    if (count($args) == 1) {
        return $args;
    }

    reset($args);
    while (!empty($args)) {
        $key = key($args);
        $loopArray = array_shift($args);
        $prevResult = $result;
        $result = [];

        if (empty($prevResult)) {
            $prevResult = [
                []
            ];
        }

        foreach ($prevResult as $prevResultRow) {
            foreach ($loopArray as $value) {
                if (!is_numeric($key)) {
                    $result[] = array_merge($prevResultRow,
                        [
                            $key => $value
                        ]);
                } else {
                    $result[] = array_merge($prevResultRow,
                        [
                            $value
                        ]);
                }
            }
        }
    }

    return $result;
}

/**
 *
 * @param array $data
 * @return array
 */
function quote_array (array $data) {
    return array_map([
        getDb(),
        'quote'
    ], $data);
}

/**
 *
 * @param scalar[] $values
 * @param \PDO $db If null then getDb() will called.
 * @return [scalar[], boolean] Quoted strings; boolean flag of NULL existance.
 */
function db_quote_array (array $values, \PDO $db = null) {
    if (is_null($db)) {
        $db = getDb();
    }

    $result = $values;
    $result = array_map(
        function  ($v) use( $db) {
            return !is_null($v) ? $db->quote($v) : null;
        }, $result);
    $result = array_filter($result);
    $nullExists = count($values) != count($result);

    return [
        $result,
        $nullExists
    ];
}

/**
 *
 * @param string[] $names
 */
function db_quote_names (array $names) {
    return array_map(function  ($f) {
        return "\"{$f}\"";
    }, $names);
}

/**
 *
 * @param string[] $names
 * @param string[] $casts Map field name to casting type. [name => TEXT] for
 *        example.
 */
function db_create_placeholders (array $names, array $casts = []) {
    return array_map(
        function  ($f) use( $casts) {
            $result = ":{$f}";

            if (array_key_exists($f, $casts)) {
                $result .= "::{$casts[$f]}";
            }

            return $result;
        }, $names);
}

/**
 *
 * @param string[] $fields
 * @param string[] $placeholders
 * @param boolean $fieldsPrepared
 * @param boolean $placeholdersPrepared
 * @return string
 */
function db_update_set (array $fields, array $placeholders,
    $fieldsPrepared = true, $placeholdersPrepared = true) {
    if (empty($fieldsPrepared)) {
        $fields = db_quote_names($fields);
    }
    if (empty($placeholdersPrepared)) {
        $placeholders = db_create_placeholders($placeholders);
    }

    $result = array_map(
        function  ($f, $p) {
            return "{$f} = {$p}";
        }, $fields, $placeholders);

    return implode(', ', $result);
}

/**
 *
 * @param string $field SQL-condition of field name.
 * @param array $data List of strings.
 * @param \PDO $db If null then getDb() will called.
 * @return string SQL-condition for WHERE.
 */
function db_in_or_null_condition ($field, array $data, \PDO $db = null) {
    list ($quoted, $nullExists) = db_quote_array($data, $db);

    $sqlConds = [];
    if ($nullExists) {
        $sqlConds[] = "{$field} IS NULL";
    }
    if (count($quoted) - intval($nullExists) > 0) {
        $sqlConds[] = sprintf("{$field} IN (%s)", implode(',', $quoted));
    }

    return !empty($sqlConds) ? '(' . implode(' OR ', $sqlConds) . ')' : 'true';
}

const PHP_URL_FULL_HOST = -1;

const PHP_URL_FULL_HOST_WITHOUT_SCHEME = -2;

const PHP_URL_FULL_PATH = -3;

/**
 * Parse a URL in more usable way as the parse_url().
 * Use PHP_URL_FULL_* constants.
 *
 * @param string $url
 * @param integer[] $components Array of PHP_URL_* constants.
 * @return scalar[]|false
 */
function parse_url_smart ($url, array $components) {
    static $componentsMap = [
        PHP_URL_SCHEME => 'scheme',
        PHP_URL_HOST => 'host',
        PHP_URL_PORT => 'port',
        PHP_URL_USER => 'user',
        PHP_URL_PASS => 'pass',
        PHP_URL_PATH => 'path',
        PHP_URL_QUERY => 'query',
        PHP_URL_FRAGMENT => 'fragment'
    ];

    $components = array_unique($components);
    $parts = parse_url($url);

    if (false === $parts) {
        return false;
    }

    $result = [];

    foreach ($components as $component) {
        if (array_key_exists($component, $componentsMap)) {
            if (array_key_exists($componentsMap[$component], $parts)) {
                $value = $parts[$componentsMap[$component]];
            } else {
                $value = null;
            }

            $result[$component] = $value;
        } elseif (PHP_URL_FULL_HOST == $component ||
             PHP_URL_FULL_HOST_WITHOUT_SCHEME == $component) {
            $value = [];

            if (PHP_URL_FULL_HOST == $component &&
                 array_key_exists('scheme', $parts)) {
                $value[] = "{$parts['scheme']}://";
            }
            if (array_key_exists('user', $parts)) {
                $temp = $parts['user'];

                if (array_key_exists('pass', $parts)) {
                    $temp .= ":{$parts['pass']}";
                }

                $value[] = "{$temp}@";
            }
            if (array_key_exists('host', $parts)) {
                $value[] = "{$parts['host']}";
            }

            $value = implode('', $value);
            $result[$component] = '' !== $value ? $value : null;
        } elseif (PHP_URL_FULL_PATH == $component) {
            $value = [];

            if (array_key_exists('path', $parts)) {
                $value[] = $parts['path'];
            }
            if (array_key_exists('query', $parts)) {
                $value[] = "?{$parts['query']}";
            }
            if (array_key_exists('fragment', $parts)) {
                $value[] = "#{$parts['fragment']}";
            }

            $value = implode('', $value);
            $result[$component] = '' !== $value ? $value : null;
        }
    }

    return $result;
}

/**
 * Order associative $source by order of $order elements.
 *
 * @param array $source
 * @param array $order
 * @return array
 */
function sortArrayKeysByOther (array $source, array $order) {
    return array_merge(array_flip(array_values($order)), $source);
}

/**
 * Trim variable value only if it's string type.
 *
 * @param mixed $var
 * @param string $charlist
 * @return mixed
 */
function trim_typesafe ($var, $charlist = " \t\n\r\0\x0B") {
    if (!is_string($var)) {
        return $var;
    }

    return trim($var, $charlist);
}

/**
 *
 * @link http://php.net/manual/ru/function.str-split.php#115703
 * @param string $string
 * @param integer $split_length
 * @return string[]|false
 */
function str_split_unicode ($string, $split_length = 1) {
    $split_length = intval($split_length);

    if ($split_length < 1) {
        return false;
    }

    return preg_split('/(.{' . $split_length . '})/us', $string, -1,
        PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
}

/**
 *
 * @param PDO $db
 * @param string $tableName
 * @param string[] $fields List of fields names.
 * @param array[] $records Two-demension array of cells (array of rows).
 * @return boolean
 */
function pgInsertByCopy (PDO $db, $tableName, array $fields, array $records) {
    static $delimiter = "\t", $nullAs = '\N';
    $escapingChars = "{$delimiter}\n\r\\";

    $rows = [];

    foreach ($records as $record) {
        $record = array_map(
            function  ($field) use( $record, $delimiter, $nullAs, $escapingChars) {
                $value = array_key_exists($field, $record) ? $record[$field] : null;

                if (is_null($value)) {
                    $value = $nullAs;
                } elseif (is_bool($value)) {
                    $value = $value ? 't' : 'f';
                } elseif (is_string($value)) {
                    $value = addcslashes($value, $escapingChars);
                }

                return $value;
            }, $fields);
        $rows[] = implode($delimiter, $record) . "\n";
    }

    return $db->pgsqlCopyFromArray($tableName, $rows, $delimiter,
        addslashes($nullAs), implode(',', $fields));
}

/**
 *
 * @param array $result
 * @param string[] $sortRules Assoc array of "fields name" => ORDER
 *        (SORT_ASC/SORT_DESC)
 * @return unknown
 */
function sortDbRowset (array &$result, array $sortRules) {
    if (empty($result)) {
        return $result;
    }
    $fields = array_keys($result[0]);
    $columns = array_combine($fields, array_fill(0, count($fields), []));

    foreach ($result as $key => $data) {
        foreach (array_keys($columns) as $field) {
            $columns[$field][$key] = $data[$field];
        }
    }

    $sortParams = [];
    foreach ($sortRules as $field => $order) {
        $sortParams[] = $columns[$field];
        $sortParams[] = $order;
    }
    $sortParams[] = &$result;

    call_user_func_array('array_multisort', $sortParams);
}
