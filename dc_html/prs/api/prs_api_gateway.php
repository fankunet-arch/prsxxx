<?php
/**
 * PRS API Gateway (ingest + query)
 * Routes:
 *   POST ?res=ingest&act=bulk
 *   POST ?res=query&act=resolve            (product_name, store_name)
 *   GET  ?res=query&act=products_search&q=
 *   GET  ?res=query&act=stores_search&q=
 *   GET  ?res=query&act=timeseries&product_id=&store_id=&from=&to=&agg=day|week|month
 *   GET  ?res=query&act=season&product_id=&store_id=&from_ym=&to_ym=
 *   GET  ?res=query&act=stockouts&product_id=&store_id=&from=&to=
 */

declare(strict_types=1);

ob_start();
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once(__DIR__ . '/../../../app/prs/config_prs/env_prs.php');
require_once(__DIR__ . '/../../../app/prs/bootstrap_compat.php');
require_once(__DIR__ . '/../../../app/prs/controllers/ingest_controller.php');
require_once(__DIR__ . '/../../../app/prs/controllers/query_controller.php');

function respond_json(array $data, int $code = 200): void {
    http_response_code($code);
    $buf = ob_get_contents();
    if ($buf !== false && $buf !== '') { $data['stderr'] = trim(strip_tags($buf)); }
    ob_clean();
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$res = $_GET['res'] ?? '';
$act = $_GET['act'] ?? '';

try {
    if ($res === 'ingest' && $act === 'bulk') {
        $payload = $_POST['payload'] ?? file_get_contents('php://input');
        $dryRun  = isset($_POST['dry_run']) ? (int)$_POST['dry_run'] : (int)($_GET['dry_run'] ?? 0);
        $aiModel = $_POST['ai_model'] ?? ($_GET['ai_model'] ?? null);
        if (!is_string($payload) || trim($payload) === '') {
            respond_json(['ok' => false, 'message' => 'Empty payload.'], 400);
        }
        $ctl = new PRS_Ingest_Controller();
        $ret = $ctl->bulk($payload, $dryRun === 1, $aiModel);
        respond_json(['ok' => true] + $ret);
    }

    if ($res === 'query') {
        $ctl = new PRS_Query_Controller();

        // NEW: 解析“纯文本名称” → ID
        if ($act === 'resolve') {
            $pn = $_POST['product_name'] ?? ($_GET['product_name'] ?? '');
            $sn = $_POST['store_name']   ?? ($_GET['store_name']   ?? '');
            $ret = $ctl->resolve_names((string)$pn, (string)$sn);
            $ok = (bool)($ret['product'] ?? null) && (bool)($ret['store'] ?? null);
            respond_json(['ok' => $ok] + $ret, $ok ? 200 : 200);
        }

        if ($act === 'products_search') {
            $q = (string)($_GET['q'] ?? '');
            $list = $q === '' ? [] : $ctl->products_search($q);
            respond_json(['ok' => true, 'items' => $list]);
        }

        if ($act === 'stores_search') {
            $q = (string)($_GET['q'] ?? '');
            $list = $q === '' ? [] : $ctl->stores_search($q);
            respond_json(['ok' => true, 'items' => $list]);
        }

        if ($act === 'timeseries') {
            $pid = (int)($_GET['product_id'] ?? 0);
            $sid = (int)($_GET['store_id'] ?? 0);
            $from = $_GET['from'] ?? null;
            $to   = $_GET['to']   ?? null;
            $agg  = $_GET['agg']  ?? 'day';
            if ($pid <= 0 || $sid <= 0) {
                respond_json(['ok' => false, 'message' => 'product_id/store_id required'], 400);
            }
            $data = $ctl->timeseries($pid, $sid, $from, $to, $agg);
            respond_json(['ok' => true] + $data);
        }

        if ($act === 'season') {
            $pid = (int)($_GET['product_id'] ?? 0);
            $sid = (int)($_GET['store_id'] ?? 0);
            $fromYm = $_GET['from_ym'] ?? null;
            $toYm   = $_GET['to_ym']   ?? null;
            if ($pid <= 0 || $sid <= 0) {
                respond_json(['ok' => false, 'message' => 'product_id/store_id required'], 400);
            }
            $rows = $ctl->season_monthly($pid, $sid, $fromYm, $toYm);
            respond_json(['ok' => true, 'rows' => $rows]);
        }

        if ($act === 'stockouts') {
            $pid = (int)($_GET['product_id'] ?? 0);
            $sid = (int)($_GET['store_id'] ?? 0);
            $from = $_GET['from'] ?? null;
            $to   = $_GET['to']   ?? null;
            if ($pid <= 0 || $sid <= 0) {
                respond_json(['ok' => false, 'message' => 'product_id/store_id required'], 400);
            }
            $rows = $ctl->stockouts($pid, $sid, $from, $to);
            respond_json(['ok' => true, 'rows' => $rows]);
        }
    }

    respond_json(['ok' => false, 'message' => "Unknown route: res={$res}, act={$act}"], 404);
} catch (Throwable $e) {
    respond_json(['ok' => false, 'message' => $e->getMessage(), 'type' => get_class($e)], 500);
}
