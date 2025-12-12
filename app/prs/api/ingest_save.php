<?php
/**
 * PRS Ingest API - 批量导入接口
 * 文件路径: app/prs/api/ingest_save.php
 * 说明: 处理数据导入请求（试运行/正式入库）
 */

// 防止直接访问
if (!defined('PRS_ENTRY')) {
    die('Access denied');
}



// 设置输出缓冲
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// 加载控制器
require_once PRS_LIB_PATH . '/ingest_controller.php';

try {
    // 获取参数
    $payload = $_POST['payload'] ?? file_get_contents('php://input');
    $dryRun  = isset($_POST['dry_run']) ? (int)$_POST['dry_run'] : (int)($_GET['dry_run'] ?? 0);
    $aiModel = $_POST['ai_model'] ?? ($_GET['ai_model'] ?? null);

    // 验证参数
    if (!is_string($payload) || trim($payload) === '') {
        prs_json_response(false, null, 'Empty payload', 400);
    }

    // 创建控制器实例
    $controller = new PRS_Ingest_Controller();

    // 执行导入
    $result = $controller->bulk($payload, $dryRun === 1, $aiModel);

    // 返回成功响应
    prs_json_response(true, $result);

} catch (Throwable $e) {
    prs_log("Ingest API error: " . $e->getMessage(), 'ERROR');
    prs_json_response(false, null, $e->getMessage(), 500);
}
