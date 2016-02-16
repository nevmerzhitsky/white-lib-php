<?php

/**
 * Constructor of ORDER BY clause of SQL-queries.
 */
class QueryOrder {
    const _STRUCT_SQL = 'sql_cond';
    const _STRUCT_DIRECTION = 'direction';
    const DIR_ASC = 'ASC';
    const DIR_DESC = 'DESC';
    const DEFAULT_DIR = self::DIR_ASC;

    /**
     * @return string[]
     */
    static public function getAvailableDirections () {
        return [
            static::DIR_ASC,
            static::DIR_DESC
        ];
    }

    /**
     * @var string[]
     */
    protected $_conditions = [];

    /**
     * @var string[]
     */
    protected $_defaultOrder = [];

    /**
     * @var string[]
     */
    protected $_order = [];

    public function __construct (array $conditions = []) {
        $this->addConditions($conditions);
    }

    private function _checkAliasesExists ($aliases) {
        if (!is_array($aliases)) {
            $aliases = [
                $aliases
            ];
        }

        foreach ($aliases as $alias) {
            if (!array_key_exists($alias, $this->_conditions)) {
                throw new ApplicationException("Not initialized condition '{$alias}'");
            }
        }
    }

    public function addConditions (array $conditions) {
        $this->_conditions = [];
        $this->_order = [];

        foreach ($conditions as $alias => $sqlForm) {
            if (is_array($sqlForm)) {
                if (count($sqlForm) != 2) {
                    throw new ApplicationException(
                        'Condition of QueryOrder in array-form should have exact two values');
                }

                list($sqlForm, $direction) = $sqlForm;
            } else {
                $direction = self::DEFAULT_DIR;
            }

            if (is_numeric($alias)) {
                $alias = $sqlForm;
            }

            $this->addCondition($alias, $sqlForm, $direction);
        }
    }

    public function addCondition ($alias, $sqlForm = null, $direction = self::DEFAULT_DIR) {
        if (empty($sqlForm)) {
            $sqlForm = $alias;
        }

        if (array_key_exists($alias, $this->_conditions) && $this->_conditions[$alias][self::_STRUCT_SQL] != $sqlForm) {
            throw new ApplicationException("You have collision for QueryOrder condition '{$alias}'");
        }

        $this->_conditions[$alias] = [
            self::_STRUCT_SQL => $sqlForm,
            self::_STRUCT_DIRECTION => self::DEFAULT_DIR
        ];
        $this->setDirection($alias, $direction);

        $this->_order[] = $alias;
    }

    public function setDirection ($alias, $direction = self::DEFAULT_DIR) {
        $this->_checkAliasesExists($alias);

        if (!in_array($direction, static::getAvailableDirections())) {
            throw new ApplicationException("Unknown direction '{$direction}'");
        }

        $this->_conditions[$alias][self::_STRUCT_DIRECTION] = $direction;
    }

    public function setDefaultOrder (array $aliases) {
        $this->_checkAliasesExists($aliases);

        $this->_defaultOrder = $aliases;
    }

    /**
     * @return string[] List of conditions alises.
     */
    public function getConditionsOrder () {
        return $this->_order;
    }

    /**
     * Set order of condition in ORDER BY clause.
     *
     * @param array $aliases
     * @param string $updateDefault If {true} then default order will updated.
     */
    public function setOrder (array $aliases, $updateDefault = false) {
        $this->_checkAliasesExists($aliases);

        $this->_order = $aliases;

        if (!empty($updateDefault)) {
            $this->setDefaultOrder($aliases);
        }
    }

    /**
     * Move some conditions to the top of order.
     *
     * @param string[]|string $aliases Condition aliases array or one condition alias.
     */
    public function raiseConditionsInOrder ($aliases) {
        if (!is_array($aliases)) {
            $aliases = [
                $aliases
            ];
        }

        $this->_checkAliasesExists($aliases);

        $order = $aliases;

        foreach ($this->getConditionsOrder() as $alias) {
            if (in_array($alias, $order)) {
                continue;
            }

            $order[] = $alias;
        }

        $this->setOrder($order);
    }

    public function resetOrder () {
        $this->_order = $this->_defaultOrder;
    }

    /**
     * @return string SQL-clause ORDER BY or empty string.
     */
    public function getOrderBy () {
        $conds = [];

        foreach ($this->getConditionsOrder() as $alias) {
            $conds[] = sprintf('%s %s', $this->_conditions[$alias][self::_STRUCT_SQL],
                $this->_conditions[$alias][self::_STRUCT_DIRECTION]);
        }

        if (empty($conds)) {
            return '';
        }

        return 'ORDER BY ' . implode(', ', $conds) . ' NULLS LAST';
    }

    /**
     * @return string[string]
     */
    public function getOrderByForPhp () {
        $result = [];

        foreach ($this->getConditionsOrder() as $alias) {
            $dir = $this->_conditions[$alias][self::_STRUCT_DIRECTION];
            $result[$alias] = $dir == static::DIR_ASC ? SORT_ASC : SORT_DESC;
        }

        return $result;
    }
}
