<?php
/**
 * PRS API - 名称解析
 * 文件路径: app/prs/api/query_resolve.php
 */

if (!defined('PRS_ENTRY')) die('Access denied');



ob_start();
require_once PRS_LIB_PATH . '/query_controller.php';

try {
    $pn = $_POST['product_name'] ?? ($_GET['product_name'] ?? '');
    $sn = $_POST['store_name']   ?? ($_GET['store_name']   ?? '');

    $controller = new PRS_Query_Controller();
    $ret = $controller->resolve_names((string)$pn, (string)$sn);

    $ok = (bool)($ret['product'] ?? null) && (bool)($ret['store'] ?? null);
    prs_json_response($ok, $ret, null, 200);
} catch (Throwable $e) {
    prs_log("Resolve error: " . $e->getMessage(), 'ERROR');
    prs_json_response(false, null, $e->getMessage(), 500);
}
