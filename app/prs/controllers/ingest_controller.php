<?php
declare(strict_types=1);

/**
 * PRS Ingest Controller (Phase-A)
 * - 解析简单文本：头部（日期+店名）+ 多个明细块
 * - 支持分隔符：优先识别 "#@"，亦兼容 "||"、"##"；块间允许空行
 * - 键值：ud / udp / pkg / img / zh / cat / st
 * - 默认上市：只要读到一块数据，即 status=listed
 * - 幂等：同一 product_id+store_id+date_local + 指纹 唯一
 * - [FIX] 自动提取 base_name_es 以支持产品规格分组
 * 依赖：/app/prs/config_prs/env_prs.php 提供 cfg() 数组
 */

final class PRS_Ingest_Controller
{
    private \PDO $pdo;

    public function __construct()
    {
        $c = cfg(); // 你现有 env 的约定，返回数组
        $this->pdo = new \PDO(
            $c['db_dsn'],
            $c['db_user'],
            $c['db_pass'],
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC]
        );
        $this->pdo->exec("SET time_zone = '+00:00'");
        $this->pdo->exec("SET NAMES utf8mb4");
    }

    public function bulk(string $raw, bool $dryRun = false, ?string $aiModel = null): array
    {
        $norm = $this->normalize_payload($raw);
        [$header, $blocks] = $this->split_header_and_blocks($norm);

        [$dateLocal, $storeName, $delim] = $this->parse_header($header);
        if (!$dateLocal || !$storeName) {
            throw new \RuntimeException('Header parse failed: need date and store name.');
        }

        $storeId = $this->ensure_store($storeName);
        $batchSha = hash('sha256', $norm);
        $nowUtc   = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s.u');

        $this->pdo->beginTransaction();
        try {
            $batchId = $this->ensure_batch($storeId, $dateLocal, $batchSha, $aiModel, $nowUtc);

            $accepted = 0;
            $rejected = 0;
            $warnings = [];
            $details  = [];

            foreach ($blocks as $i => $blockRaw) {
                $blk = $this->parse_block($blockRaw, $delim);
                if ($blk === null) { continue; }

                // 必填：name_es
                $nameEs = $blk['name_es'] ?? null;
                if (!$nameEs) {
                    $rejected++;
                    $warnings[] = "Line#".($i+1).": missing name.";
                    continue;
                }

                // 归一化/计算
                $ud   = $this->to_decimal($blk['ud'] ?? null);
                $udpG = $this->parse_weight_g($blk['udp'] ?? null);
                $pkg  = $this->to_decimal($blk['pkg'] ?? null);
                $st   = $this->normalize_status($blk['st'] ?? 'listed');
                $img  = $blk['img'] ?? null;
                $zh   = $blk['zh'] ?? null;
                $cat  = $this->normalize_category($blk['cat'] ?? 'unknown');

                if ($pkg === null && $ud !== null && $udpG !== null && $udpG > 0) {
                    $pkg = round($ud / ($udpG / 1000), 3);
                }

                if ($pkg === null && $ud === null) {
                    $rejected++;
                    $warnings[] = "Line#".($i+1).": both price_per_kg and price_per_ud missing.";
                    continue;
                }

                $productId = $this->ensure_product($nameEs, $cat, $zh, $nowUtc);

                $finger = $this->fingerprint([
                    strtolower($nameEs),
                    (string)$zh,
                    $pkg !== null ? number_format((float)$pkg, 3, '.', '') : '',
                    $ud  !== null ? number_format((float)$ud,  3, '.', '') : '',
                    $udpG !== null ? (string)$udpG : '',
                    $st
                ]);

                $idempotent = $this->observ_exists($productId, $storeId, $dateLocal, $finger);

                if (!$dryRun && !$idempotent) {
                    $this->insert_observation([
                        'product_id' => $productId,
                        'store_id'   => $storeId,
                        'batch_id'   => $batchId,
                        'date_local' => $dateLocal,
                        'observed_at'=> $nowUtc,
                        'price_per_kg_eur' => $pkg,
                        'price_per_ud_eur' => $ud,
                        'unit_weight_g'    => $udpG,
                        'status'     => $st,
                        'image_url'  => $img,
                        'finger'     => $finger,
                        'now_utc'    => $nowUtc
                    ]);
                }

                // 告警：若 pkg 与 ud/udp 同时存在且差异>1%
                if ($blk['pkg'] !== null && $ud !== null && $udpG !== null && $udpG > 0) {
                    $derived = round($ud / ($udpG / 1000), 3);
                    if ($pkg !== null && $pkg > 0) {
                        $diff = abs($derived - $pkg) / max($pkg, 0.0001);
                        if ($diff > 0.01) {
                            $warnings[] = "Line#".($i+1).": pkg={$pkg} vs derived={$derived} (diff>".round($diff*100,2)."%).";
                        }
                    }
                }

                $accepted++;
                $details[] = [
                    'line' => $i+1,
                    'name_es' => $nameEs,
                    'name_zh' => $zh,
                    'category'=> $cat,
                    'price_per_kg' => $pkg,
                    'price_per_ud' => $ud,
                    'unit_weight_g'=> $udpG,
                    'status' => $st,
                    'idem_skipped' => $idempotent ? 1 : 0
                ];
            }

            if ($dryRun) { $this->pdo->rollBack(); }
            else         { $this->pdo->commit();   }

            return [
                'store' => $storeName,
                'date'  => $dateLocal,
                'delim' => $delim,
                'accepted' => $accepted,
                'rejected' => $rejected,
                'warnings' => $warnings,
                'details'  => $details,
                'dry_run'  => $dryRun ? 1 : 0
            ];
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /* ---------- DB helpers ---------- */

    private function ensure_store(string $storeName): int
    {
        $stmt = $this->pdo->prepare("SELECT id FROM prs_stores WHERE store_name=?");
        $stmt->execute([$storeName]);
        $row = $stmt->fetch();
        if ($row) { return (int)$row['id']; }

        $now = $this->now_utc();
        $stmt = $this->pdo->prepare("INSERT INTO prs_stores (store_name, is_active, created_at, updated_at) VALUES (?,1,?,?)");
        $stmt->execute([$storeName, $now, $now]);
        return (int)$this->pdo->lastInsertId();
    }

    private function ensure_batch(int $storeId, string $dateLocal, string $sha, ?string $aiModel, string $nowUtc): int
    {
        $stmt = $this->pdo->prepare("SELECT id FROM prs_import_batches WHERE store_id=? AND date_local=? AND raw_payload_sha256=?");
        $stmt->execute([$storeId, $dateLocal, $sha]);
        $row = $stmt->fetch();
        if ($row) { return (int)$row['id']; }

        $stmt = $this->pdo->prepare("INSERT INTO prs_import_batches (store_id, date_local, raw_payload_sha256, ai_model, created_at) VALUES (?,?,?,?,?)");
        $stmt->execute([$storeId, $dateLocal, $sha, $aiModel, $nowUtc]);
        return (int)$this->pdo->lastInsertId();
    }
    
    // [NEW/FIX] 剥离尺寸/包装后缀，得到基础名称
    private function derive_base_name(string $nameEs): string
    {
        $name = trim($nameEs);
        
        // 1. 移除重量/容量后缀 (e.g., 5 KG., 500 GR, 1,5, 2 KG.)
        // 匹配：空格 + 数字/逗号/点 + (KG|GR|L|LT|G) + 可选的标点/空格
        $name = preg_replace('/\s+[\d,\.]{1,}(\s*(KG|GR|L|LT|G)[.\s]*)+$/i', '', $name);
        // 匹配：空格 + 数字/逗号/点（如 Mandarina Hoja 1,5, Castana 500 GR）
        $name = preg_replace('/\s+[\d,\.]{1,3}$/i', '', $name); 
        
        // 2. 移除包装/形态后缀 (e.g., Bolsa, Bandeja, Granel, Band)
        $suffixes = ['Bolsa', 'Band', 'Bandeja', 'Granel', 'En Malla', 'Bolsita', 'Malla'];
        $pattern = '/\s+(' . implode('|', $suffixes) . ')\s*$/i';
        $name = preg_replace($pattern, '', $name);
        
        // 3. 移除特殊规格描述
        $name = preg_replace('/\s+De Mesa\s*$/i', '', $name); // Uva Blanca de Mesa -> Uva Blanca
        $name = preg_replace('/\s+Hoja\s*$/i', '', $name); // Mandarina Hoja -> Mandarina

        return trim($name);
    }

    private function ensure_product(string $nameEs, string $category, ?string $nameZh, string $nowUtc): int
    {
        $baseNameEs = $this->derive_base_name($nameEs);
        
        // 查询时，我们仍然使用完整的 name_es 和 category 来确定是否是同一 SKU
        $stmt = $this->pdo->prepare("SELECT id, base_name_es FROM prs_products WHERE name_es=? AND category=?");
        $stmt->execute([$nameEs, $category]);
        $row = $stmt->fetch();
        if ($row) { 
            // 如果已存在，但 base_name_es 可能是 NULL（历史数据），则尝试更新
            if ($row['base_name_es'] === null || $row['base_name_es'] !== $baseNameEs) {
                 $updateStmt = $this->pdo->prepare("UPDATE prs_products SET base_name_es=?, updated_at=? WHERE id=?");
                 $updateStmt->execute([$baseNameEs, $nowUtc, $row['id']]);
            }
            return (int)$row['id']; 
        }

        // 插入时，同时插入完整的 name_es 和计算后的 base_name_es
        $stmt = $this->pdo->prepare("INSERT INTO prs_products (name_es, base_name_es, name_zh, category, is_active, created_at, updated_at)
                                     VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$nameEs, $baseNameEs, $nameZh, $category, 1, $nowUtc, $nowUtc]);
        $pid = (int)$this->pdo->lastInsertId();

        // 建立默认别名（ES）
        $stmt = $this->pdo->prepare("INSERT IGNORE INTO prs_product_aliases (product_id, alias_text, lang, created_at) VALUES (?,?, 'es', ?)");
        $stmt->execute([$pid, $nameEs, $nowUtc]);
        if ($nameZh) {
            $stmt = $this->pdo->prepare("INSERT IGNORE INTO prs_product_aliases (product_id, alias_text, lang, created_at) VALUES (?,?, 'zh', ?)");
            $stmt->execute([$pid, $nameZh, $nowUtc]);
        }
        return $pid;
    }

    private function observ_exists(int $productId, int $storeId, string $dateLocal, string $finger): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM prs_price_observations WHERE product_id=? AND store_id=? AND date_local=? AND source_line_fingerprint=?");
        $stmt->execute([$productId, $storeId, $dateLocal, $finger]);
        return (bool)$stmt->fetch();
    }

	private function insert_observation(array $o): void
	{
		$stmt = $this->pdo->prepare(
			"INSERT INTO prs_price_observations
			(product_id, store_id, batch_id, date_local, observed_at,
			 price_per_kg_eur, price_per_ud_eur, unit_weight_g, status,
			 source_line_fingerprint, created_at, updated_at)
			 VALUES (?,?,?,?,?,?,?,?,?,?,?,?)"
		);
		$stmt->execute([
			$o['product_id'], $o['store_id'], $o['batch_id'], $o['date_local'], $o['observed_at'],
			$o['price_per_kg_eur'], $o['price_per_ud_eur'], $o['unit_weight_g'], $o['status'],
			$o['finger'], $o['now_utc'], $o['now_utc']
		]);
	}


    /* ---------- Parsing helpers ---------- */

    private function normalize_payload(string $s): string
    {
        $s = str_replace("\r\n", "\n", $s);
        $s = str_replace("\r", "\n", $s);
        return trim($s);
    }

    private function split_header_and_blocks(string $s): array
    {
        // 头部：取第一段非空行，直到空行结束
        $lines = preg_split('/\n/', $s);
        $headerLines = [];
        $i = 0;
        for (; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if ($line === '') { $i++; break; }
            $headerLines[] = $line;
        }
        $header = trim(implode(' ', $headerLines));

        // 余下作为块，按空行切分
        $rest = array_slice($lines, $i);
        $blocksRaw = preg_split('/(\n\s*\n)+/', implode("\n", $rest));
        $blocksRaw = array_values(array_filter(array_map('trim', $blocksRaw), fn($x) => $x !== ''));

        return [$header, $blocksRaw];
    }

    private function parse_header(string $header): array
    {
        // 自动识别分隔符
        $delim = $this->detect_delim($header);
        $tokens = $this->split_tokens($header, $delim);

        // 期望：第一个 token = 日期，第二个 token = 店名
        $date = null; $store = null;
        foreach ($tokens as $t) {
            if ($date === null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $t)) {
                $date = $t; continue;
            }
            if ($date !== null && $store === null && $t !== '') {
                $store = $t; break;
            }
        }
        return [$date, $store, $delim];
    }

    private function parse_block(string $raw, string $delim): ?array
    {
        // 按行合并为单行，再按分隔符拆 token；若找不到键值，就把第一个非kv当名称
        $line = trim(preg_replace('/\s+/', ' ', str_replace("\n", ' ', $raw)));
        if ($line === '') { return null; }

        // 若本块看不到 header 的分隔符，尝试再探测一次
        $usedDelim = strpos($line, $delim) !== false ? $delim : $this->detect_delim($line);
        $tokens    = $this->split_tokens($line, $usedDelim);

        $out = ['name_es' => null, 'ud' => null, 'udp' => null, 'pkg' => null, 'img' => null, 'zh' => null, 'cat' => null, 'st' => null];
        foreach ($tokens as $tok) {
            if ($tok === '') { continue; }
            if (strpos($tok, ':') === false && $out['name_es'] === null) {
                $out['name_es'] = trim($tok);
                continue;
            }
            [$k, $v] = array_pad(array_map('trim', explode(':', $tok, 2)), 2, null);
            if ($k === null || $v === null) { continue; }
            $k = strtolower($k);
            if (array_key_exists($k, $out)) {
                $out[$k] = $v;
            }
        }
        return $out['name_es'] ? $out : null;
    }

    private function detect_delim(string $s): string
    {
        // 候选按常见度排序
        $cands = ['#@', '||', '##', '|@', '@|'];
        $best  = '#@';
        $max   = 0;
        foreach ($cands as $d) {
            $cnt = substr_count($s, $d);
            if ($cnt > $max) { $max = $cnt; $best = $d; }
        }
        return $best;
    }

    private function split_tokens(string $s, string $delim): array
    {
        $parts = array_map('trim', explode($delim, $s));
        // 去掉尾部可能的空 token
        while (!empty($parts) && end($parts) === '') { array_pop($parts); }
        return $parts;
    }

    private function normalize_status(?string $s): string
    {
        if (!$s) { return 'listed'; }
        $s = strtolower(trim($s));
        if (in_array($s, ['listed','在市','上架','on'], true)) { return 'listed'; }
        if (in_array($s, ['delisted','下架','off'], true)) { return 'delisted'; }
        return 'listed';
    }

    private function normalize_category(?string $s): string
    {
        if (!$s) { return 'unknown'; }
        $s = strtolower(trim($s));
        if (in_array($s, ['fruit','fruta','水果'], true))   { return 'fruit'; }
        if (in_array($s, ['seafood','marisco','海鲜'], true)) { return 'seafood'; }
        if (in_array($s, ['dairy','lácteos','奶制品'], true)) { return 'dairy'; }
        return 'unknown';
    }

    private function to_decimal($v): ?float
    {
        if ($v === null) { return null; }
        $s = trim((string)$v);
        if ($s === '') { return null; }
        // 支持逗号小数
        $s = str_replace(',', '.', $s);
        if (!preg_match('/^-?\d+(\.\d+)?$/', $s)) { return null; }
        return (float)$s;
    }

    private function parse_weight_g($v): ?int
    {
        if ($v === null) { return null; }
        $s = strtolower(trim((string)$v));
        $s = str_replace(',', '.', $s);
        if (preg_match('/^\d+(\.\d+)?\s*kg$/', $s)) {
            return (int)round((float)$s * 1000);
        }
        if (preg_match('/^\d+(\.\d+)?\s*g$/', $s)) {
            return (int)round((float)$s);
        }
        if (preg_match('/^\d+(\.\d+)?$/', $s)) { // 无单位→按克
            return (int)round((float)$s);
        }
        return null;
    }

    private function fingerprint(array $fields): string
    {
        return hash('sha256', implode('|', $fields));
    }

    private function now_utc(): string
    {
        return (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s.u');
    }
}