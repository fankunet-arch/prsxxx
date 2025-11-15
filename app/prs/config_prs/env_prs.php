<?php
declare(strict_types=1);

/**
 * PRS 环境配置
 * - 提供 cfg(): array
 * - DB 使用你提供的主机/端口/库/用户/密码
 */

if (!function_exists('cfg')) {
    function cfg(): array {
        return [
            // === 数据库连接 ===
            'db_dsn'  => 'mysql:host=mhdlmskp2kpxguj.mysql.db;port=3306;dbname=mhdlmskp2kpxguj;charset=utf8mb4',
            'db_user' => 'mhdlmskp2kpxguj',
            'db_pass' => 'BWNrmksqMEqgbX37r3QNDJLGRrUka',

            // 业务本地时区（自然日用它；DB 一律 UTC）
            'timezone_local' => 'Europe/Madrid',

            // 日志与产品图片（仅存文件名，前端从固定目录读）
            'log_dir' => __DIR__ . '/../logs_prs',
            'prs_images_base_url' => '/prs/assets/img/products/',
            'prs_images_base_dir' => dirname(__DIR__, 2) . '/dc_html/prs/assets/img/products',
        ];
    }
}
