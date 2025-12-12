<?php
declare(strict_types=1);

/**
 * PRS API: 门店列表
 * GET /prs/api/query_list_stores.php
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
    $action = new PRS_Query_Action();
    $rows = $action->list_stores();
    respond_json(['ok' => true, 'rows' => $rows]);

} catch (Throwable $e) {
    respond_json(['ok' => false, 'message' => $e->getMessage(), 'type' => get_class($e)], 500);
}
