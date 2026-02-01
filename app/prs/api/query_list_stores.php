<?php
/**
 * PRS API - 门店列表
 * 文件路径: app/prs/api/query_list_stores.php
 */

if (!defined('PRS_ENTRY')) die('Access denied');



ob_start();
require_once PRS_LIB_PATH . '/query_controller.php';

try {
    $productId = (int)($_GET['product_id'] ?? 0);
    $controller = new PRS_Query_Controller();
    $rows = $controller->list_stores($productId);

    prs_json_response(true, ['rows' => $rows]);
} catch (Throwable $e) {
    prs_log("List stores error: " . $e->getMessage(), 'ERROR');
    prs_json_response(false, null, $e->getMessage(), 500);
}
