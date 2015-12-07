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
     * @param string $configPath
     * @param string|null $section
     * @return boolean
     */
    static public function initFromFile ($configPath, $section = null) {
        if (!file_exists($configPath)) {
            throw new ApplicationException("Config file '{$configPath}' does not exists");
        }

        if (is_null($section)) {
            $section = pathinfo($configPath)['filename'];
        }

        static::$_pathes[$section] = realpath($configPath);

        // @TODO First read a file with default values of options.
        $fileData = parse_ini_file($configPath, false, INI_SCANNER_NORMAL);

        if (!is_array($fileData)) {
            throw new ApplicationException("Some error while parsing config '{$section}'");
        }

        static::$_data[$section] = $fileData;

        return true;
    }

    /**
     * @param string $section
     * @param string $option
     * @param null|mixed $default
     * @return mixed
     */
    static public function get ($section, $option, $default = null) {
        static::_init();
        $section = trim($section);
        $option = trim($option);

        if (!array_key_exists($section, static::$_data)) {
            if (!is_null($default)) {
                return $default;
            }

            throw new ApplicationException("Config does not have '{$section}' section");
        }

        if (!array_key_exists($option, static::$_data[$section])) {
            if (!is_null($default)) {
                return $default;
            }

            throw new ApplicationException("Config does not have '{$section}.{$option}' option");
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
     * @param string $section
     * @param string $option
     * @return string
     */
    static public function getPath ($section, $option, $default = null) {
        $result = static::get($section, $option, $default);

        if (empty($result)) {
            return '';
        }

        // Stupid check a path is absolute. For Linux only.
        if ('/' !== substr($result, 0, 1)) {
            $result = dirname(static::$_pathes[$section]) . DIRECTORY_SEPARATOR . $result;
        }

        return $result;
    }

    /**
     * @param string $section
     * @param string $option
     * @return integer
     */
    static public function getInteger ($section, $option, $default = null) {
        $result = static::get($section, $option, $default);

        return intval($result, 0);
    }

    /**
     * @return boolean
     */
    static public function isDebugSqlEnabled () {
        return static::isDevEnv() && !empty($_REQUEST['debug_sql']);
    }

    /**
     * @return boolean
     */
    static public function isDebugProfilingEnabled () {
        if (static::app('profile_always', 0)) {
            return true;
        }

        return static::isDevEnv() && !empty($_REQUEST['debug_profile']);
    }

    /**
     * @return boolean
     */
    static public function isDevEnv () {
        return static::app('env') == ENV_DEVELOPMENT;
    }

    /**
     * @param string $section
     * @param string $option
     * @return scalar[]
     */
    static public function parseDsn ($section, $option, $default = null) {
        $parts = explode(';', static::get($section, $option, $default));
        $result = [];

        foreach ($parts as $part) {
            list($name, $value) = explode('=', $part, 2);

            if (strpos($name, ':') !== false) {
                $name = explode(':', $name, 2)[1];
            }

            $result[$name] = $value;
        }

        return $result;
    }
}
