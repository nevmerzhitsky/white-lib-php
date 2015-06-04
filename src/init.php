<?php

class ApplicationException extends Exception {}

class DatabaseException extends ApplicationException {}

if (!defined('APPBASE')) {
    define('APPBASE', __DIR__ . '/../../../');
}
if (!defined('WL_INCBASE')) {
    define('WL_INCBASE', __DIR__ . '/');
}

require_once WL_INCBASE . 'Config.php';
require_once WL_INCBASE . 'Navbar.php';
require_once WL_INCBASE . 'Pagination.php';
require_once WL_INCBASE . 'DB/QueryWhere.php';
require_once WL_INCBASE . 'Service/JsonService.php';

require_once WL_INCBASE . 'functions.php';
require_once WL_INCBASE . 'profiling.php';
