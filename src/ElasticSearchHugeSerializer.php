<?php

class ElasticSearchHugeSerializer extends Elasticsearch\Serializers\SmartSerializer {

    /**
     * {@inheritDoc}
     * @see \Elasticsearch\Serializers\SmartSerializer::deserialize()
     */
    public function deserialize ($data, $headers) {
        if (isset($headers['content_type']) === true) {
            if (strpos($headers['content_type'], 'json') !== false) {
                return $this->decode($data);
            } else {
                //Not json, return as string
                return $data;
            }
        } else {
            //No content headers, assume json
            return $this->decode($data);
        }
    }

    /**
     * @param $data
     * @return array
     * @throws Exception
     */
    private function decode ($data) {
        if ($data === null || strlen($data) === 0) {
            return "";
        }

        $stream = tmpfile();
        fwrite($stream, $data);
        fseek($stream, 0, SEEK_SET);

        $listener = new InMemoryListener();

        try {
            $parser = new \JsonStreamingParser_Parser($stream, $listener);
            $parser->parse();
        } catch (Exception $e) {
            fclose($stream);
            throw $e;
        }
        fclose($stream);

        return $listener->get_json();
    }
}

/**
 * @link https://github.com/salsify/jsonstreamingparser/blob/master/example/InMemoryListener.php
 */
class InMemoryListener extends JsonStreamingParser\Listener\IdleListener {

    private $_result;

    private $_stack;

    private $_keys;

    public function get_json () {
        return $this->_result;
    }

    public function start_document () {
        $this->_stack = array();
        $this->_keys = array();
    }

    public function start_object () {
        $this->_start_complex_value('object');
    }

    public function end_object () {
        $this->_end_complex_value();
    }

    public function start_array () {
        $this->_start_complex_value('array');
    }

    public function end_array () {
        $this->_end_complex_value();
    }

    public function key ($key) {
        $this->_keys[] = $key;
    }

    public function value ($value) {
        $this->_insert_value($value);
    }

    private function _start_complex_value ($type) {
        // We keep a stack of complex values (i.e. arrays and objects) as we build them,
        // tagged with the type that they are so we know how to add new values.
        $current_item = array(
            'type' => $type,
            'value' => array()
        );
        $this->_stack[] = $current_item;
    }

    private function _end_complex_value () {
        $obj = array_pop($this->_stack);
        // If the value stack is now empty, we're done parsing the document, so we can
        // move the result into place so that get_json() can return it. Otherwise, we
        // associate the value
        if (empty($this->_stack)) {
            $this->_result = $obj['value'];
        } else {
            $this->_insert_value($obj['value']);
        }
    }
    // Inserts the given value into the top value on the stack in the appropriate way,
    // based on whether that value is an array or an object.
    private function _insert_value ($value) {
        // Grab the top item from the stack that we're currently parsing.
        $current_item = array_pop($this->_stack);
        // Examine the current item, and then:
        //   - if it's an object, associate the newly-parsed value with the most recent key
        //   - if it's an array, push the newly-parsed value to the array
        if ($current_item['type'] === 'object') {
            $current_item['value'][array_pop($this->_keys)] = $value;
        } else {
            $current_item['value'][] = $value;
        }
        // Replace the current item on the stack.
        $this->_stack[] = $current_item;
    }
}
