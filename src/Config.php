<?php

const ENV_DEVELOPMENT = 'development';

const ENV_PRODUCTION = 'production';

class Config {

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
            throw new ApplicationException("Config file '{$configPath}' does not exists");
        }

        if (is_null($section)) {
            $section = pathinfo($configPath)['filename'];
        }

        static::$_data[$section] = parse_ini_file($configPath, false,
                INI_SCANNER_NORMAL);

        return true;
    }

    /**
     *
     * @param string $section
     * @param string $option
     * @return scalar
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
     *
     * @return boolean
     */
    static public function isDebugSqlEnabled () {
        return static::get('app', 'env') == ENV_DEVELOPMENT &&
                 !empty($_REQUEST['debug_sql']);
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
