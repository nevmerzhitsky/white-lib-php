<?php

const ENV_DEVELOPMENT = 'development';

const ENV_PRODUCTION = 'production';

class Config {

    private static $_pathes = [];

    private static $_data = [];

    static private function _init () {
        if (!empty(static::$_data)) {
            return false;
        }

        foreach (glob(APPBASE . 'config/*.ini') as $configPath) {
            static::initFromFile($configPath);
        }

        return true;
    }

    /**
     *
     * @param string $configPath
     * @param string|null $section
     * @return boolean
     */
    static public function initFromFile ($configPath, $section = null) {
        if (!file_exists($configPath)) {
            throw new ApplicationException(
                    "Config file '{$configPath}' does not exists");
        }

        if (is_null($section)) {
            $section = pathinfo($configPath)['filename'];
        }

        static::$_pathes[$section] = realpath($configPath);

        $fileData = parse_ini_file($configPath, false, INI_SCANNER_NORMAL);

        if (!is_array($fileData)) {
            throw new ApplicationException(
                    "Some error while parsing config '{$section}'");
        }

        static::$_data[$section] = $fileData;

        return true;
    }

    /**
     *
     * @param string $section
     * @param string $option
     * @return mixed
     */
    static public function get ($section, $option) {
        static::_init();
        $section = trim($section);
        $option = trim($option);

        if (!array_key_exists($section, static::$_data)) {
            throw new ApplicationException(
                    "Config does not have '{$section}' section");
        }
        if (!array_key_exists($option, static::$_data[$section])) {
            throw new ApplicationException(
                    "Config does not have '{$section}.{$option}' option");
        }

        return static::$_data[$section][$option];
    }

    /**
     * Syntactic sugar for reduce length of code for get config values.
     *
     * @param string $name Name of config section.
     * @param array $arguments
     * @return mixed
     */
    static public function __callStatic ($name, $arguments) {
        array_unshift($arguments, $name);

        return call_user_func_array([
            'static',
            'get'
        ], $arguments);
    }

    /**
     *
     * @param string $section
     * @param string $option
     * @return string
     */
    static public function getPath ($section, $option) {
        $result = static::get($section, $option);

        // Stupid check a path is absolute. For Linux only.
        if ('/' !== $result[0]) {
            $result = dirname(static::$_pathes[$section]) . DIRECTORY_SEPARATOR .
                     $result;
        }

        return $result;
    }

    /**
     *
     * @param string $section
     * @param string $option
     * @return integer
     */
    static public function getInteger ($section, $option) {
        $result = static::get($section, $option);

        return intval($result, 0);
    }

    /**
     *
     * @return boolean
     */
    static public function isDebugSqlEnabled () {
        return static::isDevEnv() && !empty($_REQUEST['debug_sql']);
    }

    /**
     *
     * @return boolean
     */
    static public function isDevEnv () {
        return static::app('env') == ENV_DEVELOPMENT;
    }

    /**
     *
     * @param string $section
     * @param string $option
     * @return scalar[]
     */
    static public function parseDsn ($section, $option) {
        $parts = explode(';', static::get($section, $option));
        $result = [];

        foreach ($parts as $part) {
            list ($name, $value) = explode('=', $part, 2);

            if (strpos($name, ':') !== false) {
                $name = explode(':', $name, 2)[1];
            }

            $result[$name] = $value;
        }

        return $result;
    }
}
