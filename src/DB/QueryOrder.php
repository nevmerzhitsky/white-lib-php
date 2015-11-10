<?php

/**
 * Constructir of ORDER BY clause of SQL-queries.
 */
class QueryOrder {

    const DIR_ASC = 'ASC';

    const DIR_DESC = 'DESC';

    const DEFAULT_DIR = self::DIR_ASC;

    /**
     *
     * @return string[]
     */
    static public function getAvailableDirections () {
        return [
            static::DIR_ASC,
            static::DIR_DESC
        ];
    }

    /**
     *
     * @var string[]
     */
    protected $_fields = [];

    /**
     *
     * @var scalar[]
     */
    protected $_conditions = [];

    public function __construct (array $fields = []) {
        $this->clear();
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
                "You have collision for QueryOrder field '{$alias}'");
        }

        $this->_fields[$alias] = $sqlForm;
    }

    public function addCondition ($field, $direction = self::DEFAULT_DIR) {
        if (!in_array($direction, static::getAvailableDirections())) {
            throw new ApplicationException("Unknown direction '{$direction}'");
        }
        if (!array_key_exists($field, $this->_fields)) {
            throw new ApplicationException("Not inited field '{$field}'");
        }

        $this->_conditions[$field] = $direction;
    }

    public function clear () {
        $this->_conditions = [];
    }

    public function getOrderBy () {
        $conds = [];

        foreach ($this->_fields as $alias => $sqlCond) {
            if (!array_key_exists($alias, $this->_conditions)) {
                continue;
            }

            $conds[] = sprintf('%s %s', $sqlCond, $this->_conditions[$alias]);
        }

        if (empty($conds)) {
            return '';
        }

        return 'ORDER BY ' . implode(', ', $conds);
    }
}
