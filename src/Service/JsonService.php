<?php
namespace Service;

class JsonService {

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

        try {
            $response = $handlerFunc();
        } catch (\Exception $e) {
            $response = $exceptionParser($e);
        }

        header('Content-type: application/json; charset=utf-8');
        if (\Config::isDevEnv()) {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
            header('Access-Control-Allow-Headers: Authorization');
        }

        return json_encode($response);
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
