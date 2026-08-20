<?php
session_start();
date_default_timezone_set('Asia/Karachi');
$APP_ROOT = realpath(__DIR__);

$alreadyInstalled = false;
if (file_exists($APP_ROOT . '/includes/config.php')) {
    require_once $APP_ROOT . '/includes/config.php';
    @$test = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$test->connect_errno) {
        $alreadyInstalled = true;
    }
}

if ($alreadyInstalled) {
    $docroot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? $APP_ROOT));
    $appdir = str_replace('\\', '/', $APP_ROOT);
    $base = '';
    if ($docroot && strpos($appdir, $docroot) === 0) {
        $base = rtrim(substr($appdir, strlen($docroot)), '/');
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Real Estate ERP - Installed</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
    <div style="max-width:520px;margin:80px auto;padding:24px">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4 text-center">
                <i class="text-success" style="font-size:40px">&#10004;</i>
                <h3 class="mt-2">Already Installed</h3>
                <p class="text-muted mb-4">The database is connected and the system is ready.</p>
                <a class="btn btn-primary" href="<?= htmlspecialchars($base) ?>/login.php">Go to Login</a>
            </div>
        </div>
    </div>
    </body>
    </html>
    <?php
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = trim($_POST['db_host'] ?? 'localhost');
    $db_user = trim($_POST['db_user'] ?? 'root');
    $db_pass = (string)($_POST['db_pass'] ?? '');
    $db_name = 'property_erp';

    $conn = @new mysqli($db_host, $db_user, $db_pass);
    if ($conn->connect_errno) {
        $error = 'Connection failed: ' . $conn->connect_error;
    } else {
        $conn->set_charset('utf8mb4');
        $sqlFile = $APP_ROOT . '/property_erp_full.sql';
        if (!file_exists($sqlFile)) {
            $error = 'property_erp_full.sql not found in the project root.';
        } else {
            $sql = file_get_contents($sqlFile);
            $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql);
            $sql = preg_replace('/^\s*--.*$/m', '', $sql);
            $statements = preg_split('/;\s*(?:\r?\n|$)/', $sql);
            $errs = [];
            foreach ($statements as $stmt) {
                $stmt = trim($stmt);
                if ($stmt === '' || strtoupper(substr($stmt, 0, 5)) === 'NOTE;') {
                    continue;
                }
                if (!$conn->query($stmt)) {
                    $errs[] = $conn->error;
                    break;
                }
            }
            $conn->close();
            if ($errs) {
                $error = 'SQL error: ' . implode(' ', $errs);
            } else {
                $config = "<?php\n"
                    . "if (session_status() === PHP_SESSION_NONE) {\n    session_start();\n}\n\n"
                    . "date_default_timezone_set('Asia/Karachi');\n\n"
                    . "define('DB_HOST', " . var_export($db_host, true) . ");\n"
                    . "define('DB_USER', " . var_export($db_user, true) . ");\n"
                    . "define('DB_PASS', " . var_export($db_pass, true) . ");\n"
                    . "define('DB_NAME', " . var_export($db_name, true) . ");\n\n"
                    . "define('APP_ROOT', realpath(__DIR__ . '/..'));\n\n"
                    . "\$docroot = str_replace('\\\\', '/', realpath(\$_SERVER['DOCUMENT_ROOT'] ?? APP_ROOT));\n"
                    . "\$appdir  = str_replace('\\\\', '/', APP_ROOT);\n"
                    . "\$relative = '';\n"
                    . "if (\$docroot && strpos(\$appdir, \$docroot) === 0) {\n"
                    . "    \$relative = substr(\$appdir, strlen(\$docroot));\n"
                    . "} else {\n"
                    . "    \$scriptName = str_replace('\\\\', '/', \$_SERVER['SCRIPT_NAME'] ?? '/');\n"
                    . "    \$scriptDir  = str_replace('\\\\', '/', dirname(\$scriptName));\n"
                    . "    \$webDir     = str_replace('\\\\', '/', dirname(\$_SERVER['SCRIPT_FILENAME'] ?? APP_ROOT));\n"
                    . "    if (\$webDir && \$docroot && stripos(\$webDir, \$docroot) === 0) {\n"
                    . "        \$relative = rtrim(substr(\$webDir, strlen(\$docroot)), '/');\n"
                    . "    } elseif (\$scriptDir && \$scriptDir !== '/') {\n"
                    . "        \$relative = rtrim(\$scriptDir, '/');\n"
                    . "    }\n"
                    . "}\n"
                    . "define('BASE_URL', rtrim(\$relative, '/'));\n\n"
                    . "define('APP_NAME', 'Real Estate ERP');\n\n"
                    . "function setting(\$key, \$default = '') {\n"
                    . "    static \$cache = null;\n"
                    . "    global \$mysqli;\n"
                    . "    if (\$cache === null) {\n"
                    . "        \$cache = [];\n"
                    . "        if (isset(\$mysqli)) {\n"
                    . "            \$rows = db_all(\"SELECT setting_key, setting_value FROM settings\");\n"
                    . "            foreach (\$rows as \$r) {\n"
                    . "                \$cache[\$r['setting_key']] = \$r['setting_value'];\n"
                    . "            }\n"
                    . "        }\n"
                    . "    }\n"
                    . "    return isset(\$cache[\$key]) && \$cache[\$key] !== '' ? \$cache[\$key] : \$default;\n"
                    . "}\n";
                file_put_contents($APP_ROOT . '/includes/config.php', $config);
                foreach (['uploads/documents', 'uploads/projects', 'uploads/properties', 'uploads/users', 'uploads/agreements', 'uploads/maintenance'] as $dir) {
                    $p = $APP_ROOT . '/assets/' . $dir;
                    if (!is_dir($p)) {
                        @mkdir($p, 0777, true);
                    }
                }
                header('Location: login.php');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Real Estate ERP - Installer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #1e3c72, #2a5298); min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', Arial, sans-serif; }
        .install-card { max-width: 480px; width: 100%; }
    </style>
</head>
<body>
<div class="install-card">
    <div class="card shadow border-0">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold">Real Estate ERP <span class="text-muted small">- Installer</span></h5>
        </div>
        <div class="card-body p-4">
            <?php if ($error): ?>
                <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <p class="text-muted small mb-3">This will create the <strong>property_erp</strong> database, tables and seed data (default login: <code>admin</code> / <code>admin123</code>).</p>
            <form method="post">
                <div class="mb-3">
                    <label class="form-label">Database Host</label>
                    <input type="text" name="db_host" class="form-control" value="localhost" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Database User</label>
                    <input type="text" name="db_user" class="form-control" value="root" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Database Password</label>
                    <input type="password" name="db_pass" class="form-control" placeholder="Leave blank if none">
                </div>
                <button class="btn btn-primary w-100 mt-2"><i class="bi bi-download"></i> Install Now</button>
            </form>
        </div>
    </div>
    <p class="text-center text-white-50 small mt-3">Core PHP + MySQLi | No Framework</p>
</div>
</body>
</html>
