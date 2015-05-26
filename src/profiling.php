<?php

/**
 * Trait for counting and displaying execution time of some inner logic.
 * Profiling interactoion with database for example.
 */
trait ExecutionTimes {

    /**
     *
     * @var float[]
     */
    private $_executionTimes = [];

    /**
     *
     * @return float[]
    */
    public function getExecutionTimes () {
        return $this->_executionTimes;
    }

    protected function _incrExecutionTime ($name, $startTime) {
        if (!array_key_exists($name, $this->_executionTimes)) {
            $this->_executionTimes[$name] = 0.0;
        }

        $this->_executionTimes[$name] += microtime(true) - $startTime;
    }

    public function getPrintableExecutionTimes () {
        $result = [];

        $result[] = sprintf('total: %.04f', $this->getTotalExecutionTime());

        foreach ($this->_executionTimes as $name => $value) {
            if (is_float($value)) {
                $result[] = sprintf('%s: %.04f', $name, $value);
            } else {
                $result[] = sprintf('%s: %d', $name, $value);
            }
        }

        return $result;
    }

    /**
     *
     * @return float
     */
    public function getTotalExecutionTime () {
        return array_sum($this->_executionTimes);
    }
}
