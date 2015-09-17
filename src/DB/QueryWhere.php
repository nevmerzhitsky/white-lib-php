<?php

/**
 * Filter for and SQL-queries by WHERE statement.
 */
interface QueryWhere {

    const OP_EQ = '=';

    const OP_NEQ = '!=';

    const OP_GT = '>';

    const OP_GE = '>=';

    const OP_LT = '<';

    const OP_LE = '<=';

    const OP_LIKE = 'LIKE';

    const OP_NLIKE = 'NOT LIKE';

    const OP_ILIKE = 'ILIKE';

    const OP_NILIKE = 'NOT ILIKE';

    const OP_CONTAINS = '@>';

    const OP_NCONTAINS = 'NOT @>';

    const OP_CONTAINED = '<@';

    const OP_NCONTAINED = 'NOT <@';

    const OP_OVERLAP = '&&';

    const OP_NOVERLAP = 'NOT &&';

    const OP_INET_CONTAINED = '<<';

    const OP_INET_NCONTAINED = 'NOT <<';

    const OP_INET_CONTAINED_EQUAL = '<<=';

    const OP_INET_NCONTAINED_EQUAL = 'NOT <<=';

    const OP_INET_CONTAINS = '>>';

    const OP_INET_NCONTAINS = 'NOT >>';

    const OP_INET_CONTAINS_EQUAL = '>>=';

    const OP_INET_NCONTAINS_EQUAL = 'NOT >>=';

    const OP_IN = 'IN';

    const OP_NIN = 'NOT IN';

    const OP_ISNULL = 'IS NULL';

    const OP_NOTNULL = 'IS NOT NULL';

    const OP_BETWEEN = 'BETWEEN';

    const ARRAY_SUF_ANY = 'ANY';

    const ARRAY_SUF_ALL = 'ALL';

    /**
     *
     * @return string[]
     */
    static function getAvailableOperators ();

    function __construct (array $fields = []);

    function addFields (array $fields);

    /**
     *
     * @param string $alias
     * @param string $sqlForm
     */
    function addField ($alias, $sqlForm = null);

    function addSimpleCondition ($field, $value, $operator = self::OP_EQ,
            $arraySuffix = null);

    function clearAllConditions ();

    /**
     *
     * @param string $sqlCondition
     */
    function addSqlCondition ($sqlCondition);

    /**
     *
     * @param integer $counter
     * @return string
     */
    function getWhere (&$counter = 1);

    /**
     *
     * @param integer $counter
     * @return scalar[]
     */
    function getBindParams (&$counter = 1);
}

abstract class AbstractQueryWhere implements QueryWhere {

    protected static $_UNARY_OPS = [
        self::OP_ISNULL,
        self::OP_NOTNULL
    ];

    protected static $_NOBIND_OPS = [
        self::OP_IN,
        self::OP_NIN
    ];

    static public function getAvailableOperators () {
        return [
            static::OP_EQ,
            static::OP_NEQ,
            static::OP_GT,
            static::OP_GE,
            static::OP_LT,
            static::OP_LE,
            static::OP_LIKE,
            static::OP_NLIKE,
            static::OP_ILIKE,
            static::OP_NILIKE,
            static::OP_CONTAINS,
            static::OP_NCONTAINS,
            static::OP_CONTAINED,
            static::OP_NCONTAINED,
            static::OP_OVERLAP,
            static::OP_NOVERLAP,
            static::OP_INET_CONTAINED,
            static::OP_INET_NCONTAINED,
            static::OP_INET_CONTAINED_EQUAL,
            static::OP_INET_NCONTAINED_EQUAL,
            static::OP_INET_CONTAINS,
            static::OP_INET_NCONTAINS,
            static::OP_INET_CONTAINS_EQUAL,
            static::OP_INET_NCONTAINS_EQUAL,
            static::OP_IN,
            static::OP_NIN,
            static::OP_ISNULL,
            static::OP_NOTNULL,
            static::OP_BETWEEN
        ];
    }

    static public function getAvailableArraySuffixes () {
        return [
            static::ARRAY_SUF_ALL,
            static::ARRAY_SUF_ANY
        ];
    }

    const SIMPLE_VALUE = 'value';

    const SIMPLE_OPERATOR = 'operator';

    const SIMPLE_ARRAY_SUFFIX = 'array_suffix';

    /**
     *
     * @var scalar[]
     */
    protected $_simpleConditions = [];

    /**
     *
     * @var string[]
     */
    protected $_rawConditions = [];

    /**
     *
     * @var QueryWhere[]
     */
    protected $_conditions = [];

    /**
     *
     * @var string[]
     */
    protected $_fields = [];

    public function __construct (array $fields = []) {
        $this->clearAllConditions();
        $this->addFields($fields);
    }

    public function addFields (array $fields) {
        $this->_fields = [];

        foreach ($fields as $alias => $sqlForm) {
            if (is_numeric($alias)) {
                $this->addField($sqlForm);
            } else {
                $this->addField($alias, $sqlForm);
            }
        }
    }

    public function addField ($alias, $sqlForm = null) {
        if (empty($sqlForm)) {
            $sqlForm = $alias;
        }

        if (array_key_exists($alias, $this->_fields) &&
                 $this->_fields[$alias] != $sqlForm) {
            throw new ApplicationException(
                    "You have collision for QueryWhere field '{$alias}'");
        }

        $this->_fields[$alias] = $sqlForm;

        if (!array_key_exists($alias, $this->_simpleConditions)) {
            $this->_simpleConditions[$alias] = [];
        }
    }

    public function addSimpleCondition ($field, $value, $operator = self::OP_EQ,
            $arraySuffix = null) {
        if (!in_array($operator, static::getAvailableOperators())) {
            throw new ApplicationException("Unknown operator '{$operator}'");
        }
        if (!is_null($arraySuffix) &&
                 !in_array($arraySuffix, static::getAvailableArraySuffixes())) {
            throw new ApplicationException(
                    "Unknown array suffix '{$arraySuffix}'");
        }
        if (!array_key_exists($field, $this->_simpleConditions)) {
            throw new ApplicationException("Not inited field '{$field}'");
        }

        $this->_simpleConditions[$field][] = [
            static::SIMPLE_VALUE => $value,
            static::SIMPLE_OPERATOR => $operator,
            static::SIMPLE_ARRAY_SUFFIX => $arraySuffix
        ];
    }

    public function addSqlCondition ($sqlCondition) {
        // @TODO Get params and bind it dynamically.
        $this->_rawConditions[] = $sqlCondition;
    }

    public function addConditions (AbstractQueryWhere $qw) {
        if (in_array($qw, $this->_conditions)) {
            throw new ApplicationException(
                    "This QueryWhere object already added to conditions list!");
        }

        $this->_conditions[] = $qw;

        foreach ($qw->_fields as $alias => $sqlForm) {
            $this->addField($alias, $sqlForm);
        }
    }

    public function clearAllConditions () {
        $this->_simpleConditions = [];
        $this->_rawConditions = [];
        $this->_conditions = [];
    }

    /**
     *
     * @param string $field
     */
    public function clearSimpleCondition ($field) {
        if (array_key_exists($field, $this->_simpleConditions)) {
            $this->_simpleConditions[$field] = [];
        }
    }

    /**
     *
     * @return boolean
     */
    protected function _isHaveConditions () {
        $counter = 1;
        return count($this->_joinConditions($counter)) > 0;
    }

    /**
     *
     * @param integer $counter
     * @return array
     */
    protected function _joinConditions (&$counter) {
        $result = [];

        // @TODO Complete logic for various comparison operators (BETWEEN).
        foreach ($this->_simpleConditions as $alias => $values) {
            foreach ($values as $settings) {
                $result[] = $this->_valueToWhere($this->_fields[$alias],
                        $alias . $counter++, $settings);
            }
        }

        /* @var $qw QueryWhere */
        foreach ($this->_conditions as $qw) {
            if (!$qw->_isHaveConditions()) {
                continue;
            }

            $result[] = '(' . $qw->getWhere($counter) . ')';
            $counter++;
        }

        $result = array_merge($result, $this->_rawConditions);

        return $result;
    }

    public function getBindParams (&$counter = 1) {
        $result = [];

        foreach ($this->_simpleConditions as $alias => $values) {
            foreach ($values as $settings) {
                if (static::OP_BETWEEN == $settings[static::SIMPLE_OPERATOR]) {
                    if (count($settings[static::SIMPLE_VALUE]) != 2) {
                        throw new ApplicationException(
                                'Value for BETWEEN operator should be array of two elements');
                    }

                    $result[$alias . $counter . '_from'] = $settings[static::SIMPLE_VALUE][0];
                    $result[$alias . $counter . '_to'] = $settings[static::SIMPLE_VALUE][1];
                } elseif (!in_array($settings[static::SIMPLE_OPERATOR],
                        static::$_UNARY_OPS) && !in_array(
                        $settings[static::SIMPLE_OPERATOR], static::$_NOBIND_OPS)) {
                    $result[$alias . $counter] = $settings[static::SIMPLE_VALUE];
                }

                $counter++;
            }
        }

        /* @var $qw QueryWhere */
        foreach ($this->_conditions as $qw) {
            if (!$qw->_isHaveConditions()) {
                continue;
            }

            $result = array_merge($result, $qw->getBindParams($counter));
            $counter++;
        }

        return $result;
    }

    /**
     *
     * @param string $sqlForm
     * @param string $paramName
     * @param array[] $settings
     * @return string
     */
    protected function _valueToWhere ($sqlForm, $paramName, array $settings) {
        if (in_array($settings[static::SIMPLE_OPERATOR], static::$_UNARY_OPS)) {
            return sprintf('%s %s', $sqlForm,
                    $settings[static::SIMPLE_OPERATOR]);
        }

        if (!is_null($settings[static::SIMPLE_ARRAY_SUFFIX])) {
            return sprintf(':%s %s %s (%s)', $paramName,
                    $settings[static::SIMPLE_OPERATOR],
                    $settings[static::SIMPLE_ARRAY_SUFFIX], $sqlForm);
        }

        if (in_array($settings[static::SIMPLE_OPERATOR], static::$_NOBIND_OPS)) {
            if (!is_array($settings[static::SIMPLE_VALUE])) {
                throw new ApplicationException(
                        "Argument for '{$sqlForm}' ({$paramName}) should be array");
            }

            return sprintf('%s %s (%s)', $sqlForm,
                    $settings[static::SIMPLE_OPERATOR],
                    implode(',', quote_array($settings[static::SIMPLE_VALUE])));
        }

        if (in_array($settings[static::SIMPLE_OPERATOR],
                [
                    static::OP_BETWEEN
                ])) {
            return sprintf('%s %s :%3$s_from AND :%3$s_to', $sqlForm,
                    $settings[static::SIMPLE_OPERATOR], $paramName);
        }

        return sprintf('%s %s (:%s)', $sqlForm,
                $settings[static::SIMPLE_OPERATOR], $paramName);
    }
}

/**
 * SQL-queries WHERE constructor by AND clause.
 */
class QueryWhereAnd extends AbstractQueryWhere {

    public function getWhere (&$counter = 1) {
        $conditions = $this->_joinConditions($counter);

        if (empty($conditions)) {
            return 'true';
        }

        return implode(' AND ', $conditions);
    }
}

/**
 * SQL-queries WHERE constructor by OR clause.
 */
class QueryWhereOr extends AbstractQueryWhere {

    public function getWhere (&$counter = 1) {
        $conditions = $this->_joinConditions($counter);

        if (empty($conditions)) {
            return 'false';
        }

        return implode(' OR ', $conditions);
    }
}
