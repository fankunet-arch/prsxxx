<?php
/**
 * PRS Bootstrap File
 * 文件路径: app/prs/bootstrap.php
 * 说明: 系统初始化，加载配置和核心库
 */

// 防止直接访问
if (!defined('PRS_ENTRY')) {
    die('Access denied');
}

// 1. 加载配置文件
require_once __DIR__ . '/config_prs/env_prs.php';

// 2. 定义路径常量
define('PRS_APP_PATH', PROJECT_ROOT . '/app/prs');
define('PRS_CONFIG_PATH', PRS_APP_PATH . '/config_prs');
define('PRS_LIB_PATH', PRS_APP_PATH . '/lib');
define('PRS_VIEW_PATH', PRS_APP_PATH . '/views');
define('PRS_ACTION_PATH', PRS_APP_PATH . '/actions');
define('PRS_API_PATH', PRS_APP_PATH . '/api');

// 3. 数据库连接在需要时才建立（通过 get_prs_db_connection() 函数）
// 这样可以避免在初始化阶段就因数据库问题导致整个系统崩溃

// 4. 加载核心业务库（如果需要）
// require_once PRS_LIB_PATH . '/prs_lib.php';

/**
 * 获取数据库连接
 * @return PDO
 * @throws PDOException
 */
function get_prs_db_connection() {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $cfg = cfg();

    try {
        $pdo = new PDO(
            $cfg['db_dsn'],
            $cfg['db_user'],
            $cfg['db_pass'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        $pdo->exec("SET time_zone = '+00:00'");
        $pdo->exec("SET NAMES utf8mb4");

        return $pdo;
    } catch (PDOException $e) {
        error_log('PRS DB Connection Error: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * JSON 响应函数
 * @param bool $ok 是否成功
 * @param mixed $data 数据
 * @param string|null $message 消息
 * @param int $code HTTP 状态码
 */
function prs_json_response(bool $ok, $data = null, ?string $message = null, int $code = 200): void {
    http_response_code($code);

    $response = ['ok' => $ok];

    if ($message !== null) {
        $response['message'] = $message;
    }

    if ($data !== null) {
        if (is_array($data)) {
            $response = array_merge($response, $data);
        } else {
            $response['data'] = $data;
        }
    }

    // 捕获任何输出缓冲区中的内容
    $stderr = '';
    while (ob_get_level() > 0) {
        $buf = ob_get_clean();
        if ($buf !== false && trim($buf) !== '') {
            $stderr .= $buf;
        }
    }

    if ($stderr !== '') {
        $response['stderr'] = trim(strip_tags($stderr));
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * 日志记录函数
 * @param string $message 日志消息
 * @param string $level 日志级别
 */
function prs_log(string $message, string $level = 'INFO'): void {
    $cfg = cfg();
    $logDir = $cfg['log_dir'] ?? __DIR__ . '/logs_prs';

    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }

    $logFile = $logDir . '/prs_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $logLine = "[{$timestamp}] [{$level}] {$message}\n";

    @file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
}
