<?php
declare(strict_types=1);

/**
 * PRS Query Controller
 * - 产品/门店搜索（联想）
 * - 价格时序（day|week|month），€/kg = COALESCE(pkg, ud/udp)
 * - 月在市（当月>=1日有观测即在市）
 * - 缺货段（可视化）
 * - 名称解析（文本 → ID）
 * - [NEW] 产品列表 (list_products)
 * - [NEW] 门店列表 (list_stores)
 * - [FIX] list_products 参数绑定
 * - [FIX] products_search 返回 base_name_es
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
        // [FIX] 包含 base_name_es
        $stmt = $this->pdo->prepare("
            SELECT id, name_es, base_name_es, name_zh, category, image_filename
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

    /* ---------- [新增] 产品类别列表 ---------- */
    public function get_categories(): array
    {
        $sql = "
            SELECT DISTINCT category
            FROM prs_products
            WHERE category IS NOT NULL AND category != ''
            ORDER BY category ASC
        ";
        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [];
        return $rows;
    }

    /* ---------- [新增] 按类别获取产品 ---------- */
    public function get_products_by_category(string $category): array
    {
        $sql = "
            SELECT id, name_es, base_name_es, name_zh, category
            FROM prs_products
            WHERE category = ?
            ORDER BY name_es ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$category]);
        return $stmt->fetchAll() ?: [];
    }

    /* ---------- [新增] 产品列表 ---------- */
    public function list_products(int $page = 1, int $pageSize = 20, string $q = '', string $category = '', int $storeId = 0): array
    {
        $offset = ($page - 1) * $pageSize;
        $where = '1=1';
        $params = []; // 用于 COUNT 查询

        if ($q) {
            $qLike = '%'.$q.'%';
            // 搜索条件同时匹配完整的 name_es 和 name_zh
            $where = " (p.name_es LIKE ? OR (p.name_zh IS NOT NULL AND p.name_zh LIKE ?))";
            $params = [$qLike, $qLike];
        }

        // 添加类别筛选
        if ($category) {
            $where .= ($where !== '1=1' ? ' AND ' : '') . 'p.category = ?';
            $params[] = $category;
        }

        // 添加门店筛选
        if ($storeId > 0) {
            $where .= ($where !== '1=1' ? ' AND ' : '') . ' EXISTS (SELECT 1 FROM prs_price_observations obs WHERE obs.product_id = p.id AND obs.store_id = ?)';
            $params[] = $storeId;
        }

        // 统计总数（需要考虑搜索条件）
        $countSql = "SELECT COUNT(*) FROM prs_products p WHERE {$where}";
        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute($params);
        $totalCount = (int)$countStmt->fetchColumn();

        // 查询分页数据
        $sql = "
            SELECT
                p.id, p.name_es, p.base_name_es, p.name_zh, p.category,
                MAX(o.date_local) AS last_observed_date,
                p.created_at
            FROM prs_products p
            LEFT JOIN prs_price_observations o ON p.id = o.product_id
            WHERE {$where}
            GROUP BY p.id
            ORDER BY p.id DESC
            LIMIT ? OFFSET ?
        ";

        $stmt = $this->pdo->prepare($sql);
        $paramIndex = 1; // 用于绑定参数的索引计数器

        // 1. 绑定搜索参数 (如果存在)
        if ($q) {
            $qLike = '%'.$q.'%';
            $stmt->bindValue($paramIndex++, $qLike);
            $stmt->bindValue($paramIndex++, $qLike);
        }

        // 2. 绑定类别参数 (如果存在)
        if ($category) {
            $stmt->bindValue($paramIndex++, $category);
        }

        // 3. 绑定门店参数 (如果存在)
        if ($storeId > 0) {
            $stmt->bindValue($paramIndex++, $storeId, \PDO::PARAM_INT);
        }

        // 4. 绑定 LIMIT 和 OFFSET 参数 (总是最后两个)
        $stmt->bindValue($paramIndex++, $pageSize, \PDO::PARAM_INT);
        $stmt->bindValue($paramIndex++, $offset, \PDO::PARAM_INT);

        $stmt->execute();

        return [
            'total' => $totalCount,
            'page'  => $page,
            'pageSize' => $pageSize,
            'items' => $stmt->fetchAll() ?: []
        ];
    }

    /* ---------- [新增] 门店列表 ---------- */
    public function list_stores(int $productId = 0): array
    {
        if ($productId > 0) {
            // 如果指定了产品，只返回有该产品观测数据的门店
            $sql = "
                SELECT
                    s.id, s.store_name, s.created_at,
                    COUNT(DISTINCT o.date_local) AS days_observed,
                    COUNT(o.id) AS total_observations
                FROM prs_stores s
                JOIN prs_price_observations o ON s.id = o.store_id
                WHERE o.product_id = ?
                GROUP BY s.id
                ORDER BY s.id DESC
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$productId]);
        } else {
            $sql = "
                SELECT
                    s.id, s.store_name, s.created_at,
                    COUNT(DISTINCT o.date_local) AS days_observed,
                    COUNT(o.id) AS total_observations
                FROM prs_stores s
                LEFT JOIN prs_price_observations o ON s.id = o.store_id
                GROUP BY s.id
                ORDER BY s.id DESC
            ";
            $stmt = $this->pdo->query($sql);
        }
        return $stmt->fetchAll() ?: [];
    }


    /* ---------- 时序 ---------- */

    public function timeseries(int $productId, int $storeId, ?string $from = null, ?string $to = null, string $agg = 'day'): array
    {
        if (!in_array($agg, ['day','week','month'], true)) { $agg = 'day'; }

        $where = "product_id = :pid";
        // 如果 storeId > 0，则添加 store_id 过滤
        if ($storeId > 0) {
            $where .= " AND store_id = :sid";
        }

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
        if ($storeId > 0) {
            $stmt->bindValue(':sid', $storeId, \PDO::PARAM_INT);
        }
        if ($from) { $stmt->bindValue(':fromd', $from); }
        if ($to)   { $stmt->bindValue(':tod',   $to); }
        $stmt->execute();
        $rows = $stmt->fetchAll() ?: [];

        return ['agg' => $agg, 'rows' => $rows];
    }

    /* ---------- 月在市 ---------- */

    public function season_monthly(int $productId, int $storeId, ?string $fromYm = null, ?string $toYm = null): array
    {
        // 如果跨门店（storeId <= 0），进行聚合
        if ($storeId <= 0) {
            $sql = "SELECT ym, MAX(is_in_market_month) as is_in_market_month
                    FROM prs_season_monthly_v2
                    WHERE product_id = ?";
            $params = [$productId];
            if ($fromYm) { $sql .= " AND ym >= ?"; $params[] = $fromYm; }
            if ($toYm)   { $sql .= " AND ym <= ?"; $params[] = $toYm; }
            $sql .= " GROUP BY ym ORDER BY ym";
        } else {
            $sql = "SELECT ym, days_with_obs, is_in_market_month
                    FROM prs_season_monthly_v2
                    WHERE product_id = ? AND store_id = ?";
            $params = [$productId, $storeId];

            if ($fromYm) { $sql .= " AND ym >= ?"; $params[] = $fromYm; }
            if ($toYm)   { $sql .= " AND ym <= ?"; $params[] = $toYm; }
            $sql .= " ORDER BY ym";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    /* ---------- 缺货段 ---------- */

    public function stockouts(int $productId, int $storeId, ?string $from = null, ?string $to = null): array
    {
        // 跨门店查询暂不支持缺货段（或者返回空），因为逻辑较复杂
        if ($storeId <= 0) {
            return [];
        }

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
