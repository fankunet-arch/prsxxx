<?php
/**
 * PRS API - 缺货段数据
 * 文件路径: app/prs/api/query_stockouts.php
 */

if (!defined('PRS_ENTRY')) die('Access denied');



ob_start();
require_once PRS_LIB_PATH . '/query_controller.php';

try {
    $pid = (int)($_GET['product_id'] ?? 0);
    $sid = (int)($_GET['store_id'] ?? 0);
    $from = $_GET['from'] ?? null;
    $to   = $_GET['to']   ?? null;

    if ($pid <= 0) { // store_id optional (0=all)
        prs_json_response(false, null, 'product_id required', 400);
    }

    $controller = new PRS_Query_Controller();
    $rows = $controller->stockouts($pid, $sid, $from, $to);

    prs_json_response(true, ['rows' => $rows]);
} catch (Throwable $e) {
    prs_log("Stockouts error: " . $e->getMessage(), 'ERROR');
    prs_json_response(false, null, $e->getMessage(), 500);
}
