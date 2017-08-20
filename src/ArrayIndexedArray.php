<?php
/**
 * Implementation of array-like object supporting arrays as keys. Support all next array-like usage:
 * access by [key], count, foreach.
 */
class ArrayIndexedArray implements ArrayAccess, Iterator, Countable, Serializable {
    /** @var array[] */
    private $_keys = [];
    /** @var mixed[] */
    private $_data = [];

    public function offsetSet($offset, $value) {
        assert('is_array($offset)');

        if (is_null($offset)) {
            throw new RuntimeException("You cannot add values to the object by \$var[] syntax");
        }

        $hash = $this->_getOffsetHash($offset);
        $this->_keys[$hash] = $offset;
        $this->_data[$hash] = $value;
    }

    public function offsetExists($offset) {
        assert('is_array($offset)');

        $hash = $this->_getOffsetHash($offset);
        return isset($this->_data[$hash]);
    }

    public function offsetUnset($offset) {
        assert('is_array($offset)');

        $hash = $this->_getOffsetHash($offset);
        unset($this->_keys[$hash]);
        unset($this->_data[$hash]);
    }

    public function &offsetGet($offset) {
        assert('is_array($offset)');

        $hash = $this->_getOffsetHash($offset);

        if (!isset($this->_data[$hash])) {
            $result = null;
            return $result;
        }

        return $this->_data[$hash];
    }

    public function key() {
        return key($this->_keys);
    }

    public function valid() {
        $hash = $this->key();

        return array_key_exists($hash, $this->_keys);
    }

    public function next() {
        return next($this->_keys);
    }

    public function rewind() {
        return reset($this->_keys);
    }

    public function current() {
        $hash = $this->key();

        return $this->_keys[$hash];
    }

    public function count() {
        return count($this->_keys);
    }

    public function serialize() {
        return json_encode([
            'keys' => serialize($this->_keys),
            'data' => serialize($this->_data),
        ]);
    }

    public function unserialize($serialized) {
        $rawData = json_decode($serialized, true);

        $this->_keys = unserialize($rawData['keys']);
        $this->_data = unserialize($rawData['data']);
        reset($this->_keys);
        reset($this->_data);
    }

    /**
     * @param array $offset
     * @return string
     */
    private function _getOffsetHash(array $offset) {
        ksort($offset);
        return md5(var_export($offset, true));
    }

    /**
     * @return mixed
     */
    public function getInfo() {
        $hash = $this->key();

        return $this->_data[$hash];
    }

    /**
     * @param array[] $keysFilter Keys are names of keys fields, values - filtering values.
     * @return self
     */
    public function find($keysFilter = []) {
        $result = new self();

        foreach ($this as $key) {
            foreach ($keysFilter as $filterName => $filterValue) {
                $nameFound = false;
                foreach ($key as $keyName => $keyValue) {
                    if ($keyName === $filterName) {
                        $nameFound = true;

                        if ($keyValue !== $filterValue) {
                            continue(3);
                        }
                    }
                }

                if (!$nameFound) {
                    continue(2);
                }
            }

            $result[$key] = $this->getInfo();
        }

        return $result;
    }

    /**
     * @return array[]
     */
    public function getFlattenArray() {
        $result = [];

        foreach ($this as $key) {
            $result[] = [
                'key' => $key,
                'value' => $this->getInfo(),
            ];
        }

        return $result;
    }
}
