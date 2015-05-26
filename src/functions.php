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