<?php
declare(strict_types=1);

/**
 * PRS Query Controller
 * - 产品/门店搜索（联想）
 * - 价格时序（day|week|month），€/kg = COALESCE(pkg, ud/udp)
 * - 月在市（当月>=1日有观测即在市）
 * - 缺货段（可视化）
 * - 名称解析（文本 → ID）
 */

final class PRS_Query_Controller
{
    private \PDO $pdo;

    public function __construct()
    {
        $c = cfg();
        $this->pdo = new \PDO(
            $c['db_dsn'],
            $c['db_user'],
            $c['db_pass'],
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC]
        );
        $this->pdo->exec("SET time_zone = '+00:00'");
        $this->pdo->exec("SET NAMES utf8mb4");
    }

    /* ---------- 搜索 ---------- */

    public function products_search(string $q, int $limit = 20): array
    {
        $qLike = '%'.$q.'%';
        $stmt = $this->pdo->prepare("
            SELECT id, name_es, name_zh, category, image_filename
            FROM prs_products
            WHERE name_es LIKE ? OR (name_zh IS NOT NULL AND name_zh LIKE ?)
            ORDER BY name_es ASC
            LIMIT ?
        ");
        $stmt->bindValue(1, $qLike);
        $stmt->bindValue(2, $qLike);
        $stmt->bindValue(3, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public function stores_search(string $q, int $limit = 20): array
    {
        $qLike = '%'.$q.'%';
        $stmt = $this->pdo->prepare("
            SELECT id, store_name
            FROM prs_stores
            WHERE store_name LIKE ?
            ORDER BY store_name ASC
            LIMIT ?
        ");
        $stmt->bindValue(1, $qLike);
        $stmt->bindValue(2, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    /* ---------- 文本名称解析（更稳：精确匹配优先；唯一候选次之） ---------- */

    public function resolve_names(?string $productName, ?string $storeName): array
    {
        $product = null; $store = null; $prodCandidates = []; $storeCandidates = [];

        // 产品解析
        $pn = trim((string)$productName);
        if ($pn !== '') {
            // 1) 精确命中（ES 或 ZH）
            $stmt = $this->pdo->prepare("
                SELECT id, name_es, name_zh, category FROM prs_products
                WHERE name_es = ? OR (name_zh IS NOT NULL AND name_zh = ?)
                LIMIT 1
            ");
            $stmt->execute([$pn, $pn]);
            $row = $stmt->fetch();
            if ($row) {
                $product = $row;
            } else {
                // 2) 模糊候选
                $like = '%'.$pn.'%';
                $stmt = $this->pdo->prepare("
                    SELECT id, name_es, name_zh, category FROM prs_products
                    WHERE name_es LIKE ? OR (name_zh IS NOT NULL AND name_zh LIKE ?)
                    ORDER BY name_es ASC
                    LIMIT 5
                ");
                $stmt->execute([$like, $like]);
                $rows = $stmt->fetchAll() ?: [];
                if (count($rows) === 1) { $product = $rows[0]; }
                else { $prodCandidates = $rows; }
            }
        }

        // 门店解析
        $sn = trim((string)$storeName);
        if ($sn !== '') {
            // 1) 精确命中
            $stmt = $this->pdo->prepare("SELECT id, store_name FROM prs_stores WHERE store_name = ? LIMIT 1");
            $stmt->execute([$sn]);
            $row = $stmt->fetch();
            if ($row) {
                $store = $row;
            } else {
                // 2) 模糊候选
                $like = '%'.$sn.'%';
                $stmt = $this->pdo->prepare("
                    SELECT id, store_name FROM prs_stores
                    WHERE store_name LIKE ?
                    ORDER BY store_name ASC
                    LIMIT 5
                ");
                $stmt->execute([$like]);
                $rows = $stmt->fetchAll() ?: [];
                if (count($rows) === 1) { $store = $rows[0]; }
                else { $storeCandidates = $rows; }
            }
        }

        return [
            'product' => $product,
            'store' => $store,
            'product_candidates' => $prodCandidates,
            'store_candidates' => $storeCandidates,
        ];
    }

    /* ---------- 时序 ---------- */

    public function timeseries(int $productId, int $storeId, ?string $from = null, ?string $to = null, string $agg = 'day'): array
    {
        if (!in_array($agg, ['day','week','month'], true)) { $agg = 'day'; }

        $where = "product_id = :pid AND store_id = :sid";
        if ($from) { $where .= " AND date_local >= :fromd"; }
        if ($to)   { $where .= " AND date_local <= :tod"; }

        $sqlBase = "
        SELECT date_local,
               COALESCE(price_per_kg_eur,
                        CASE WHEN price_per_ud_eur IS NOT NULL AND unit_weight_g IS NOT NULL AND unit_weight_g > 0
                             THEN price_per_ud_eur / (unit_weight_g/1000.0) END) AS eff_price
        FROM prs_price_observations
        WHERE {$where}";

        if ($agg === 'day') {
            $sql = "SELECT date_local,
                           ROUND(AVG(eff_price), 3) AS price_per_kg,
                           COUNT(*) AS n
                    FROM ({$sqlBase}) t
                    WHERE eff_price IS NOT NULL
                    GROUP BY date_local
                    ORDER BY date_local ASC";
        } elseif ($agg === 'week') {
            $sql = "SELECT DATE_FORMAT(date_local, '%x') AS iso_year,
                           DATE_FORMAT(date_local, '%v') AS iso_week,
                           MIN(date_local) AS anchor_date,
                           ROUND(AVG(eff_price), 3) AS price_per_kg,
                           COUNT(*) AS n
                    FROM ({$sqlBase}) t
                    WHERE eff_price IS NOT NULL
                    GROUP BY iso_year, iso_week
                    ORDER BY iso_year, iso_week";
        } else {
            $sql = "SELECT DATE_FORMAT(date_local, '%Y-%m') AS ym,
                           MIN(date_local) AS anchor_date,
                           ROUND(AVG(eff_price), 3) AS price_per_kg,
                           COUNT(*) AS n
                    FROM ({$sqlBase}) t
                    WHERE eff_price IS NOT NULL
                    GROUP BY ym
                    ORDER BY ym";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':pid', $productId, \PDO::PARAM_INT);
        $stmt->bindValue(':sid', $storeId, \PDO::PARAM_INT);
        if ($from) { $stmt->bindValue(':fromd', $from); }
        if ($to)   { $stmt->bindValue(':tod',   $to); }
        $stmt->execute();
        $rows = $stmt->fetchAll() ?: [];

        return ['agg' => $agg, 'rows' => $rows];
    }

    /* ---------- 月在市 ---------- */

    public function season_monthly(int $productId, int $storeId, ?string $fromYm = null, ?string $toYm = null): array
    {
        $sql = "SELECT ym, days_with_obs, is_in_market_month
                FROM prs_season_monthly_v2
                WHERE product_id = ? AND store_id = ?";
        $params = [$productId, $storeId];

        if ($fromYm) { $sql .= " AND ym >= ?"; $params[] = $fromYm; }
        if ($toYm)   { $sql .= " AND ym <= ?"; $params[] = $toYm; }
        $sql .= " ORDER BY ym";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    /* ---------- 缺货段 ---------- */

    public function stockouts(int $productId, int $storeId, ?string $from = null, ?string $to = null): array
    {
        $sql = "SELECT gap_start, gap_end, gap_days
                FROM prs_stockout_segments_v2
                WHERE product_id = ? AND store_id = ?";
        $params = [$productId, $storeId];

        if ($from) { $sql .= " AND gap_end   >= ?"; $params[] = $from; }
        if ($to)   { $sql .= " AND gap_start <= ?"; $params[] = $to; }
        $sql .= " ORDER BY gap_start";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }
}
