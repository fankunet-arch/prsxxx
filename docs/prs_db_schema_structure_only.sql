-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- 主机： mhdlmskp2kpxguj.mysql.db
-- 生成日期： 2025-12-12 13:15:45
-- 服务器版本： 8.4.6-6
-- PHP 版本： 8.1.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `mhdlmskp2kpxguj`
--
CREATE DATABASE IF NOT EXISTS `mhdlmskp2kpxguj` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `mhdlmskp2kpxguj`;

-- --------------------------------------------------------

--
-- 表的结构 `prs_import_batches`
--

DROP TABLE IF EXISTS `prs_import_batches`;
CREATE TABLE `prs_import_batches` (
  `id` bigint UNSIGNED NOT NULL,
  `store_id` bigint UNSIGNED NOT NULL,
  `date_local` date NOT NULL,
  `raw_payload_sha256` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ai_model` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `prs_price_observations`
--

DROP TABLE IF EXISTS `prs_price_observations`;
CREATE TABLE `prs_price_observations` (
  `id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `store_id` bigint UNSIGNED NOT NULL,
  `batch_id` bigint UNSIGNED NOT NULL,
  `date_local` date NOT NULL,
  `observed_at` datetime(6) NOT NULL,
  `price_per_kg_eur` decimal(10,3) DEFAULT NULL,
  `price_per_ud_eur` decimal(10,3) DEFAULT NULL,
  `unit_weight_g` int DEFAULT NULL,
  `status` enum('listed','delisted') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'listed',
  `source_line_fingerprint` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime(6) NOT NULL,
  `updated_at` datetime(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `prs_products`
--

DROP TABLE IF EXISTS `prs_products`;
CREATE TABLE `prs_products` (
  `id` bigint UNSIGNED NOT NULL,
  `name_zh` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name_es` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `base_name_es` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` enum('fruit','seafood','dairy','unknown') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unknown',
  `image_filename` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `default_unit_weight_g` int DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime(6) NOT NULL,
  `updated_at` datetime(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `prs_product_aliases`
--

DROP TABLE IF EXISTS `prs_product_aliases`;
CREATE TABLE `prs_product_aliases` (
  `id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `alias_text` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lang` enum('zh','es','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other',
  `created_at` datetime(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 替换视图以便查看 `prs_season_monthly_v2`
-- （参见下面的实际视图）
--
DROP VIEW IF EXISTS `prs_season_monthly_v2`;
CREATE TABLE `prs_season_monthly_v2` (
`days_with_obs` bigint
,`is_in_market_month` int
,`product_id` bigint unsigned
,`store_id` bigint unsigned
,`ym` varchar(7)
);

-- --------------------------------------------------------

--
-- 替换视图以便查看 `prs_stockout_segments_v2`
-- （参见下面的实际视图）
--
DROP VIEW IF EXISTS `prs_stockout_segments_v2`;
CREATE TABLE `prs_stockout_segments_v2` (
`gap_days` bigint
,`gap_end` date
,`gap_start` date
,`product_id` bigint unsigned
,`store_id` bigint unsigned
);

-- --------------------------------------------------------

--
-- 表的结构 `prs_stores`
--

DROP TABLE IF EXISTS `prs_stores`;
CREATE TABLE `prs_stores` (
  `id` bigint UNSIGNED NOT NULL,
  `store_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `district` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(12) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime(6) NOT NULL,
  `updated_at` datetime(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转储表的索引
--

--
-- 表的索引 `prs_import_batches`
--
ALTER TABLE `prs_import_batches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_prs_batch` (`store_id`,`date_local`,`raw_payload_sha256`),
  ADD KEY `idx_prs_batch_store_date` (`store_id`,`date_local`);

--
-- 表的索引 `prs_price_observations`
--
ALTER TABLE `prs_price_observations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_prs_obs_idem` (`product_id`,`store_id`,`date_local`,`source_line_fingerprint`),
  ADD KEY `fk_prs_obs_store` (`store_id`),
  ADD KEY `fk_prs_obs_batch` (`batch_id`),
  ADD KEY `idx_prs_obs_prod_store_date` (`product_id`,`store_id`,`date_local`);

--
-- 表的索引 `prs_products`
--
ALTER TABLE `prs_products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_prs_products_es_cat` (`name_es`,`category`),
  ADD KEY `idx_prs_products_cat_name` (`category`,`name_es`);

--
-- 表的索引 `prs_product_aliases`
--
ALTER TABLE `prs_product_aliases`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_prs_alias` (`alias_text`,`lang`),
  ADD KEY `idx_prs_alias_product` (`product_id`);

--
-- 表的索引 `prs_stores`
--
ALTER TABLE `prs_stores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_prs_store_name` (`store_name`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `prs_import_batches`
--
ALTER TABLE `prs_import_batches`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `prs_price_observations`
--
ALTER TABLE `prs_price_observations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `prs_products`
--
ALTER TABLE `prs_products`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `prs_product_aliases`
--
ALTER TABLE `prs_product_aliases`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `prs_stores`
--
ALTER TABLE `prs_stores`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------

--
-- 视图结构 `prs_season_monthly_v2`
--
DROP TABLE IF EXISTS `prs_season_monthly_v2`;

DROP VIEW IF EXISTS `prs_season_monthly_v2`;
CREATE ALGORITHM=UNDEFINED DEFINER=`mhdlmskp2kpxguj`@`%` SQL SECURITY DEFINER VIEW `prs_season_monthly_v2`  AS WITH   `days` as (select distinct `prs_price_observations`.`product_id` AS `product_id`,`prs_price_observations`.`store_id` AS `store_id`,`prs_price_observations`.`date_local` AS `date_local` from `prs_price_observations`), `m` as (select `days`.`product_id` AS `product_id`,`days`.`store_id` AS `store_id`,date_format(`days`.`date_local`,'%Y-%m') AS `ym`,count(0) AS `days_with_obs` from `days` group by `days`.`product_id`,`days`.`store_id`,`ym`) select `m`.`product_id` AS `product_id`,`m`.`store_id` AS `store_id`,`m`.`ym` AS `ym`,`m`.`days_with_obs` AS `days_with_obs`,(case when (`m`.`days_with_obs` >= 1) then 1 else 0 end) AS `is_in_market_month` from `m`  ;

-- --------------------------------------------------------

--
-- 视图结构 `prs_stockout_segments_v2`
--
DROP TABLE IF EXISTS `prs_stockout_segments_v2`;

DROP VIEW IF EXISTS `prs_stockout_segments_v2`;
CREATE ALGORITHM=UNDEFINED DEFINER=`mhdlmskp2kpxguj`@`%` SQL SECURITY DEFINER VIEW `prs_stockout_segments_v2`  AS WITH   `days` as (select distinct `prs_price_observations`.`product_id` AS `product_id`,`prs_price_observations`.`store_id` AS `store_id`,`prs_price_observations`.`date_local` AS `date_local` from `prs_price_observations`), `seq` as (select `days`.`product_id` AS `product_id`,`days`.`store_id` AS `store_id`,`days`.`date_local` AS `date_local`,lag(`days`.`date_local`) OVER (PARTITION BY `days`.`product_id`,`days`.`store_id` ORDER BY `days`.`date_local` )  AS `prev_date` from `days`) select `seq`.`product_id` AS `product_id`,`seq`.`store_id` AS `store_id`,(`seq`.`prev_date` + interval 1 day) AS `gap_start`,(`seq`.`date_local` - interval 1 day) AS `gap_end`,((to_days(`seq`.`date_local`) - to_days(`seq`.`prev_date`)) - 1) AS `gap_days` from `seq` where ((`seq`.`prev_date` is not null) and ((to_days(`seq`.`date_local`) - to_days(`seq`.`prev_date`)) > 1))  ;

--
-- 限制导出的表
--

--
-- 限制表 `prs_import_batches`
--
ALTER TABLE `prs_import_batches`
  ADD CONSTRAINT `fk_prs_batch_store` FOREIGN KEY (`store_id`) REFERENCES `prs_stores` (`id`) ON DELETE RESTRICT;

--
-- 限制表 `prs_price_observations`
--
ALTER TABLE `prs_price_observations`
  ADD CONSTRAINT `fk_prs_obs_batch` FOREIGN KEY (`batch_id`) REFERENCES `prs_import_batches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_prs_obs_product` FOREIGN KEY (`product_id`) REFERENCES `prs_products` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_prs_obs_store` FOREIGN KEY (`store_id`) REFERENCES `prs_stores` (`id`) ON DELETE RESTRICT;

--
-- 限制表 `prs_product_aliases`
--
ALTER TABLE `prs_product_aliases`
  ADD CONSTRAINT `fk_prs_alias_product` FOREIGN KEY (`product_id`) REFERENCES `prs_products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
