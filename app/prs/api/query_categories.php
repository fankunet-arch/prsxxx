<?php
/**
 * PRS API - 获取所有产品类别
 * 文件路径: app/prs/api/query_categories.php
 */

if (!defined('PRS_ENTRY')) die('Access denied');



ob_start();
require_once PRS_LIB_PATH . '/query_controller.php';

try {
    $controller = new PRS_Query_Controller();
    $categories = $controller->get_categories();

    prs_json_response(true, ['categories' => $categories]);
} catch (Throwable $e) {
    prs_log("Get categories error: " . $e->getMessage(), 'ERROR');
    prs_json_response(false, null, $e->getMessage(), 500);
}
