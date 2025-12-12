<?php
declare(strict_types=1);

/**
 * PRS API: 批量导入
 * POST /prs/api/ingest_bulk.php
 * Params: payload, dry_run, ai_model
 */

ob_start();
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once(__DIR__ . '/../../../app/prs/config_prs/env_prs.php');
require_once(__DIR__ . '/../../../app/prs/bootstrap_compat.php');
require_once(__DIR__ . '/../../../app/prs/actions/ingest_action.php');

function respond_json(array $data, int $code = 200): void {
    http_response_code($code);
    $buf = ob_get_contents();
    if ($buf !== false && $buf !== '') { $data['stderr'] = trim(strip_tags($buf)); }
    ob_clean();
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $payload = $_POST['payload'] ?? file_get_contents('php://input');
    $dryRun  = isset($_POST['dry_run']) ? (int)$_POST['dry_run'] : (int)($_GET['dry_run'] ?? 0);
    $aiModel = $_POST['ai_model'] ?? ($_GET['ai_model'] ?? null);

    if (!is_string($payload) || trim($payload) === '') {
        respond_json(['ok' => false, 'message' => 'Empty payload.'], 400);
    }

    $action = new PRS_Ingest_Action();
    $ret = $action->bulk($payload, $dryRun === 1, $aiModel);
    respond_json(['ok' => true] + $ret);

} catch (Throwable $e) {
    respond_json(['ok' => false, 'message' => $e->getMessage(), 'type' => get_class($e)], 500);
}
