<?php
/**
 * PRS Central Router
 * 文件路径: app/prs/index.php
 * 说明: 中央路由控制器，统一处理所有请求
 */

// 防止直接访问
if (!defined('PRS_ENTRY')) {
    die('Access denied');
}

// 加载 bootstrap
require_once __DIR__ . '/bootstrap.php';

// 获取 action 参数
$action = $_GET['action'] ?? null;

// 如果没有指定 action，使用默认值
if ($action === null) {
    $action = 'dashboard'; // 默认首页
}

// 清理 action 参数，防止路径遍历攻击
$action = basename($action);

// 构建白名单：扫描 actions 目录
$allowed_actions = array_map(
    function ($file_path) {
        return pathinfo($file_path, PATHINFO_FILENAME);
    },
    glob(PRS_ACTION_PATH . '/*.php') ?: []
);

// 检查是否是 API 请求（通过前缀判断）
// API 请求：ingest_save, query_*, 等
$api_prefixes = ['query_', 'ingest_', 'api_'];
$is_api_request = false;
foreach ($api_prefixes as $prefix) {
    if (strpos($action, $prefix) === 0) {
        $is_api_request = true;
        break;
    }
}

// 构建文件路径
$action_file = PRS_ACTION_PATH . '/' . $action . '.php';
$api_file = PRS_API_PATH . '/' . $action . '.php';

// 路由逻辑
try {
    // 1. 优先检查 API 文件（如果是 API 请求）
    if ($is_api_request && file_exists($api_file)) {
        require_once $api_file;
        exit;
    }

    // 2. 检查 action 文件（页面请求）
    if (file_exists($action_file)) {
        // 验证是否在白名单中
        if (in_array($action, $allowed_actions, true)) {
            require_once $action_file;
            exit;
        } else {
            prs_log("Disallowed action requested: {$action}", 'WARNING');
        }
    }

    // 3. 如果都不存在，检查是否有对应的 API 文件
    if (file_exists($api_file)) {
        require_once $api_file;
        exit;
    }

    // 4. 404 - 未找到
    prs_log("Action file not found: {$action}", 'WARNING');
    http_response_code(404);

    // 判断是否是 AJAX 请求
    $is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    $accepts_json = isset($_SERVER['HTTP_ACCEPT']) &&
                    strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;

    if ($is_ajax || $accepts_json) {
        prs_json_response(false, null, 'Action not found', 404);
    }

    // 返回 HTML 404 页面
    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>404 - 页面未找到</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
                background: #f5f5f5;
                margin: 0;
                padding: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
            }
            .card {
                max-width: 520px;
                background: #fff;
                border: 1px solid #e0e0e0;
                border-radius: 8px;
                padding: 32px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            }
            .card h1 {
                margin-top: 0;
                font-size: 24px;
                color: #c62828;
            }
            .card p {
                color: #444;
                line-height: 1.6;
                margin: 16px 0;
            }
            .card a {
                color: #1565c0;
                text-decoration: none;
                font-weight: 600;
            }
            .card a:hover {
                text-decoration: underline;
            }
        </style>
    </head>
    <body>
        <div class="card">
            <h1>404 - 页面未找到</h1>
            <p>请求的操作 <code><?= htmlspecialchars($action) ?></code> 不存在或未被允许。</p>
            <p><a href="/prs/index.php?action=dashboard">返回首页</a></p>
        </div>
    </body>
    </html>
    <?php
    exit;

} catch (Throwable $e) {
    prs_log("Error processing action {$action}: " . $e->getMessage(), 'ERROR');
    http_response_code(500);

    // 判断是否是 AJAX 请求
    $is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    $accepts_json = isset($_SERVER['HTTP_ACCEPT']) &&
                    strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;

    if ($is_ajax || $accepts_json) {
        prs_json_response(false, null, 'Internal server error', 500);
    }

    // 返回 HTML 错误页面
    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>500 - 服务器错误</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
                background: #f5f5f5;
                margin: 0;
                padding: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
            }
            .card {
                max-width: 520px;
                background: #fff;
                border: 1px solid #e0e0e0;
                border-radius: 8px;
                padding: 32px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            }
            .card h1 {
                margin-top: 0;
                font-size: 24px;
                color: #c62828;
            }
            .card p {
                color: #444;
                line-height: 1.6;
                margin: 16px 0;
            }
            .card a {
                color: #1565c0;
                text-decoration: none;
                font-weight: 600;
            }
            .card a:hover {
                text-decoration: underline;
            }
        </style>
    </head>
    <body>
        <div class="card">
            <h1>500 - 服务器错误</h1>
            <p>处理请求时发生错误，请稍后再试。</p>
            <p><a href="/prs/index.php?action=dashboard">返回首页</a></p>
        </div>
    </body>
    </html>
    <?php
    exit;
}
