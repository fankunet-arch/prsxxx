<?php
declare(strict_types=1);

/**
 * PRS API: 缺货段数据
 * GET /prs/api/query_stockouts.php?product_id=1&store_id=1&from=&to=
 */

ob_start();
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once(__DIR__ . '/../../../app/prs/config_prs/env_prs.php');
require_once(__DIR__ . '/../../../app/prs/bootstrap_compat.php');
require_once(__DIR__ . '/../../../app/prs/actions/query_action.php');

function respond_json(array $data, int $code = 200): void {
    http_response_code($code);
    $buf = ob_get_contents();
    if ($buf !== false && $buf !== '') { $data['stderr'] = trim(strip_tags($buf)); }
    ob_clean();
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pid = (int)($_GET['product_id'] ?? 0);
    $sid = (int)($_GET['store_id'] ?? 0);
    $from = $_GET['from'] ?? null;
    $to   = $_GET['to']   ?? null;

    if ($pid <= 0 || $sid <= 0) {
        respond_json(['ok' => false, 'message' => 'product_id/store_id required'], 400);
    }

    $action = new PRS_Query_Action();
    $rows = $action->stockouts($pid, $sid, $from, $to);
    respond_json(['ok' => true, 'rows' => $rows]);

} catch (Throwable $e) {
    respond_json(['ok' => false, 'message' => $e->getMessage(), 'type' => get_class($e)], 500);
}
