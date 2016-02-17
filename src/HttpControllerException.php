<?php

class HttpControllerException extends \ApplicationException {

    public function __construct ($message = null, $code = null, $previous = null) {
        if (is_null($code)) {
            $code = 500;
        }

        parent::__construct($message, $code, $previous);
    }
}
