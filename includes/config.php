<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Karachi');

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'property_erp');

define('APP_ROOT', realpath(__DIR__ . '/..'));

$docroot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? APP_ROOT));
$appdir  = str_replace('\\', '/', APP_ROOT);
$relative = '';
if ($docroot && strpos($appdir, $docroot) === 0) {
    $relative = substr($appdir, strlen($docroot));
}
define('BASE_URL', rtrim($relative, '/'));

define('APP_NAME', 'Real Estate ERP');

function setting($key, $default = '') {
    static $cache = null;
    global $mysqli;
    if ($cache === null) {
        $cache = [];
        if (isset($mysqli)) {
            $rows = db_all("SELECT setting_key, setting_value FROM settings");
            foreach ($rows as $r) {
                $cache[$r['setting_key']] = $r['setting_value'];
            }
        }
    }
    return isset($cache[$key]) && $cache[$key] !== '' ? $cache[$key] : $default;
}
