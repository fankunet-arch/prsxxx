<?php
/**
 * PRS API - 按类别获取产品列表
 * 文件路径: app/prs/api/query_products_by_category.php
 */

if (!defined('PRS_ENTRY')) die('Access denied');



ob_start();
require_once PRS_LIB_PATH . '/query_controller.php';

try {
    $category = $_GET['category'] ?? '';

    if (empty($category)) {
        prs_json_response(false, null, 'Category parameter is required', 400);
        exit;
    }

    $controller = new PRS_Query_Controller();
    $products = $controller->get_products_by_category($category);

    prs_json_response(true, ['items' => $products]);
} catch (Throwable $e) {
    prs_log("Get products by category error: " . $e->getMessage(), 'ERROR');
    prs_json_response(false, null, $e->getMessage(), 500);
}
