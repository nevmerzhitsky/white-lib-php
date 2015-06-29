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

    const OP_IN = 'IN';

    const OP_NIN = 'NOT IN';

    const OP_ANY = '= ANY';

    const OP_NANY = '!= ANY';

    const OP_SOME = '= SOME';

    const OP_NSOME = '!= SOME';

    const OP_ALL = '= ALL';

    const OP_NALL = '!= ALL';

    const OP_ISNULL = 'IS NULL';

    const OP_NOTNULL = 'IS NOT NULL';

    const OP_BETWEEN = 'BETWEEN';

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

    function addSimpleCondition ($field, $value, $operator = self::OP_EQ);

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

    protected static $_ARRAY_OPS = [
        self::OP_ANY,
        self::OP_NANY,
        self::OP_SOME,
        self::OP_NSOME,
        self::OP_ALL,
        self::OP_NALL
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
            static::OP_IN,
            static::OP_NIN,
            static::OP_ANY,
            static::OP_NANY,
            static::OP_SOME,
            static::OP_NSOME,
            static::OP_ALL,
            static::OP_NALL,
            static::OP_ISNULL,
            static::OP_NOTNULL,
            static::OP_BETWEEN
        ];
    }

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

    public function addSimpleCondition ($field, $value, $operator = self::OP_EQ) {
        if (!array_key_exists($field, $this->_simpleConditions)) {
            throw new ApplicationException("Not inited field '{$field}'");
        }

        $this->_simpleConditions[$field][] = [
            $operator,
            $value
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
            foreach ($values as $value) {
                $result[] = $this->_valueToWhere($this->_fields[$alias],
                        $alias . $counter++, $value[0]);
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
            foreach ($values as $value) {
                if (in_array($value[0], static::$_UNARY_OPS)) {
                    $counter++;
                    continue;
                }

                if (in_array($value[0],
                        [
                            static::OP_BETWEEN
                        ]) && count($value[1]) == 2) {
                    $result[$alias . $counter . '_from'] = $value[1][0];
                    $result[$alias . $counter . '_to'] = $value[1][1];
                    $counter++;
                } else {
                    $result[$alias . $counter++] = $value[1];
                }
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
     * @param string $operator
     * @return string
     */
    protected function _valueToWhere ($sqlForm, $paramName, $operator) {
        if (in_array($operator, static::$_UNARY_OPS)) {
            return sprintf('%s %s', $sqlForm, $operator);
        }

        if (in_array($operator, static::$_ARRAY_OPS)) {
            return sprintf(':%s %s (%s)', $paramName, $operator, $sqlForm);
        }

        if (in_array($operator,
                [
                    static::OP_BETWEEN
                ])) {
            return sprintf('%s %s :%3$s_from AND :%3$s_to', $sqlForm, $operator,
                    $paramName);
        }

        return sprintf('%s %s (:%s)', $sqlForm, $operator, $paramName);
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
