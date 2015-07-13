<?php
namespace Service;

class JsonService {

    /**
     *
     * @var boolean
     */
    private $_autoEchoResponse = false;

    /**
     *
     * @var boolean
     */
    private $_gzipResponse = false;

    /**
     *
     * @var boolean
     */
    private $_supportJsonp = true;

    /**
     *
     * @var integer|null
     */
    private $_jsonEncodeOptions = null;

    public function __construct ($autoEchoResponse = true, $gzipResponse = true,
            $jsonEncodeOptions = null) {
        $this->_autoEchoResponse = !empty($autoEchoResponse);
        $this->_gzipResponse = !empty($gzipResponse);
        $this->setJsonEncodeOptions($jsonEncodeOptions);
    }

    /**
     *
     * @param integer|null $value
     */
    public function setJsonEncodeOptions ($value) {
        $this->_jsonEncodeOptions = intval($value);
        if (!$this->_jsonEncodeOptions) {
            $this->_jsonEncodeOptions = null;
        }
    }

    /**
     *
     * @param boolean $value
     */
    public function setSupportJsonp ($value) {
        $this->_supportJsonp = !empty($value);
    }

    public function handleRequest ($handlerFunc, $exceptionParser = null) {
        if (is_null($exceptionParser)) {
            $exceptionParser = [
                $this,
                '_defaultExceptionParser'
            ];
        }

        if (!is_callable($handlerFunc)) {
            throw new \ApplicationException('handlerFunc should be callable');
        }
        if (!is_callable($exceptionParser)) {
            throw new \ApplicationException('exceptionParser should be callable');
        }

        if ($this->_autoEchoResponse) {
            if ($this->_gzipResponse) {
                ob_start('ob_gzhandler');
            }
        }

        try {
            $response = $handlerFunc();
        } catch (\Exception $e) {
            $response = $exceptionParser($e);
        }

        if (is_null($response)) {
            return null;
        }

        $result = json_encode($response, $this->_jsonEncodeOptions);

        if ($result === false) {
            return null;
        }

        // Wrap for JSONP response.
        if ($this->_supportJsonp && !empty($_REQUEST['callback']) &&
                 preg_match('%^[\d\w\-_\.]+$%i', $_REQUEST['callback'])) {
            $result = "{$_REQUEST['callback']}({$result})";
        }

        if ($this->_autoEchoResponse) {
            header('Content-type: application/json; charset=utf-8');
            if (\Config::isDevEnv()) {
                header('Access-Control-Allow-Origin: *');
                header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
                header('Access-Control-Allow-Headers: Authorization');
            }

            echo $result;
        }

        return $result;
    }

    private function _defaultExceptionParser (\Exception $e) {
        $message = $e->getMessage();

        // Hide unsecure info.
        if (!\Config::isDevEnv() && $e instanceof \PDOException) {
            $message = 'Some database error.';
        }

        return [
            'status' => 'error',
            'message' => $message
        ];
    }
}
