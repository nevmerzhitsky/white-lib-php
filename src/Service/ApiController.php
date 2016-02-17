<?php
namespace Service;

class ApiController {

    public function dispatch ($controller, $action) {
        try {
            $controller = trim($controller);
            $action = trim($action);

            if (empty($controller) || empty($action)) {
                throw new \HttpControllerException('No controller or action in request', 400);
            }

            $controllerClassName = __NAMESPACE__ . "\\{$controller}Controller";

            if (!class_exists($controllerClassName)) {
                throw new \HttpControllerException('No handler for this controller name', 400);
            }

            /* @var $controller AbstractHttpController */
            $controller = new $controllerClassName();

            $result = $controller->doAction($action);

            return $result;
        } catch (\HttpControllerException $e) {
            if (function_exists('getDb') && getDb()->inTransaction()) {
                getDb()->rollBack();
            }

            error_log($e->getMessage());
            http_response_code($e->getCode());

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}
