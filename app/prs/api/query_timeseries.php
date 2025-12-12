<?php
/**
 * PRS API - 时序数据
 * 文件路径: app/prs/api/query_timeseries.php
 */

if (!defined('PRS_ENTRY')) die('Access denied');



ob_start();
require_once PRS_LIB_PATH . '/query_controller.php';

try {
    $pid = (int)($_GET['product_id'] ?? 0);
    $sid = (int)($_GET['store_id'] ?? 0);
    $from = $_GET['from'] ?? null;
    $to   = $_GET['to']   ?? null;
    $agg  = $_GET['agg']  ?? 'day';

    if ($pid <= 0 || $sid <= 0) {
        prs_json_response(false, null, 'product_id/store_id required', 400);
    }

    $controller = new PRS_Query_Controller();
    $data = $controller->timeseries($pid, $sid, $from, $to, $agg);

    prs_json_response(true, $data);
} catch (Throwable $e) {
    prs_log("Timeseries error: " . $e->getMessage(), 'ERROR');
    prs_json_response(false, null, $e->getMessage(), 500);
}
