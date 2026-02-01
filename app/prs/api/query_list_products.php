<?php
/**
 * PRS API - 产品列表
 * 文件路径: app/prs/api/query_list_products.php
 */

if (!defined('PRS_ENTRY')) die('Access denied');



ob_start();
require_once PRS_LIB_PATH . '/query_controller.php';

try {
    $page = (int)($_GET['page'] ?? 1);
    $size = (int)($_GET['size'] ?? 20);
    $q    = (string)($_GET['q'] ?? '');
    $category = (string)($_GET['category'] ?? '');
    $storeId = (int)($_GET['store_id'] ?? 0);

    $controller = new PRS_Query_Controller();
    $data = $controller->list_products($page, $size, $q, $category, $storeId);

    prs_json_response(true, $data);
} catch (Throwable $e) {
    prs_log("List products error: " . $e->getMessage(), 'ERROR');
    prs_json_response(false, null, $e->getMessage(), 500);
}
