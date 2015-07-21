<?php

class HttpControllerException extends \ApplicationException {

    public function __construct ($message = null, $code = null, $previous = null) {
        if (is_null($code)) {
            $code = 500;
        }

        parent::__construct($message, $code, $previous);
    }
}

class AbstractHttpController {

    const PARAMS_SOURCE_JSON_POST = 'post_json';

    /**
     *
     * @var string[]
     */
    protected static $_actions = [];

    protected $_post;

    protected $_json;

    /**
     *
     * @param string $action
     * @return void|mixed
     */
    public function doAction ($action) {
        $actionMethodName = $this->_addHttpMethod($action);

        if (!$actionMethodName) {
            return;
        }

        if (!$this->_prepareActionParams($action)) {
            return;
        }

        return call_user_func(
                [
                    $this,
                    $actionMethodName
                ]);
    }

    /**
     *
     * @param string $action
     * @return string|null
     */
    protected function _addHttpMethod ($action) {
        $httpMethod = $_SERVER['REQUEST_METHOD'];

        if ('OPTIONS' == $httpMethod) {
            // Magic for AJAX POST from AngularJS.
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Headers: Content-Type');
            return null;
        }

        $this->_checkActionAvailableByHttpMethod($action, $httpMethod);

        return strtolower($httpMethod) . $action;
    }

    /**
     *
     * @param string $action
     * @param string $httpMethod
     */
    protected function _checkActionAvailableByHttpMethod ($action, $httpMethod) {
        $methods = $this->_getActionMethods($action);

        if (empty($methods)) {
            throw new HttpControllerException('No handler for this action', 400);
        }

        if (in_array($httpMethod, $methods)) {
            return;
        }

        if (is_array($methods) && !empty($methods)) {
            header('Allow: ' . implode(',', $methods));
        }

        throw new HttpControllerException(
                'Unsupported HTTP method for this action', 405);
    }

    /**
     *
     * @param string $action
     * @return string[]
     */
    protected function _getActionMethods ($action) {
        if (!array_key_exists($action, static::$_actions)) {
            return [];
        }
        if (!array_key_exists('methods', static::$_actions[$action])) {
            return [];
        }

        return static::$_actions[$action]['methods'];
    }

    /**
     *
     * @param string $action
     * @return boolean
     */
    protected function _prepareActionParams ($action) {
        list ($source, $paramsMethod) = $this->_getRequestParams($action);

        if (static::PARAMS_SOURCE_JSON_POST == $source) {
            $this->_post = file_get_contents('php://input');
            $this->_json = json_decode($this->_post, true);
            $this->_logRequest();

            if (empty($this->_post) || empty($this->_json)) {
                throw new HttpControllerException(
                        'Unable to parse JSON in request body', 400);
            }
        }

        if (is_callable($paramsMethod)) {
            if (call_user_func($paramsMethod) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     *
     * @param string $action
     * @return [string, callback] First is alias of source for params in a
     *         request, second is callback to more specific params initializer.
     */
    protected function _getRequestParams ($action) {
        static $nullResult = [
            null,
            []
        ];

        if (!array_key_exists($action, static::$_actions)) {
            return $nullResult;
        }
        if (!array_key_exists('requestParams', static::$_actions[$action])) {
            return $nullResult;
        }

        $requestParams = static::$_actions[$action]['requestParams'];

        if (!is_array($requestParams)) {
            return $nullResult;
        }

        $source = array_shift($requestParams);

        if (count($requestParams) == 2 && is_null($requestParams[0])) {
            $requestParams[0] = $this;
        }

        return [
            $source,
            $requestParams
        ];
    }

    private function _logRequest () {
        $logPath = Config::getPath('app', 'controller_request_log', '');

        if (empty($logPath)) {
            return;
        }
        if (!file_exists($logPath) && !is_writable(dirname($logPath))) {
            return;
        }

        file_put_contents($logPath,
                sprintf('[%s] %s %s' . PHP_EOL, date('Y-m-d H:i:s'),
                        $_SERVER['REQUEST_URI'], $this->_post), FILE_APPEND);
    }
}
