<?php
/**
 * PRS API - 产品搜索
 * 文件路径: app/prs/api/query_products_search.php
 */

if (!defined('PRS_ENTRY')) die('Access denied');



ob_start();
require_once PRS_LIB_PATH . '/query_controller.php';

try {
    $q = (string)($_GET['q'] ?? '');
    $limit = (int)($_GET['limit'] ?? 20);

    $controller = new PRS_Query_Controller();
    $list = $q === '' ? [] : $controller->products_search($q, $limit);

    prs_json_response(true, ['items' => $list]);
} catch (Throwable $e) {
    prs_log("Products search error: " . $e->getMessage(), 'ERROR');
    prs_json_response(false, null, $e->getMessage(), 500);
}
